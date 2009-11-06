<?php

  require_once("abstract.callwrapper.php");
  require_once("class.domdocumentexceptor.php");
  require_once("lib.nupackets.php");
  require_once("lib.nufederated.php");

  class postFederatedPublish extends CallWrapper
  {
    /*

      PARAMS
      id    // remote identification of packet
      packet    // <fps packet

    */

    private function publisherID()
    {
      if( isset( $GLOBALS['FPS_AUTHORIZED'] ) )
      {
        $this->local = false;
        return $GLOBALS['FPS_AUTHORIZED']['federated_user'];
      }
      else if( isset( $GLOBALS['USER_CONTROL'] ) )
      {
        $this->local = true;
        return $GLOBALS['USER_CONTROL']['id'];
      }
      else
      {
        throw new Exception("Unauthorized publisher", 2);
      }
    }

    private function packetID()
    {
      if( $this->local )
      {
        $packet_id = -1;
      }
      else
      {
        $packet_id  = intval($this->call->id);

        if( !$packet_id )
          throw new Exception("Invalid packet_id", 5);
      }

      return $packet_id;
    }

    private function packetData()
    {
      //
      // CHECK PACKET DATA
      //
      if( !strlen($this->call->packet) )
        throw new Exception("Missing packet", 4);

      return $this->call->packet;
    }

    private function packetXML()
    {
      // check packet as valid XML
      try
      {
        $packet_xml = new DOMDocumentExceptor("1.0","utf-8");
	$packet_xml->preserveWhiteSpace = false;
	$packet_xml->formatOutput = true;
        $packet_xml->loadXML( $this->call->packet );
      }
      catch( Exception $e )
      {
        throw new Exception("Packet is not valid XML", 5);
      }

      return $packet_xml;
    }

    private function linkNS( $id, $xmlns )
    {
      $ns_count   = count($xmlns[0]);

      $namespaces = array();
      for( $a=0; $a<$ns_count; $a++ )
      {
        $prefix = $xmlns[1][$a];
	$uri    = $xmlns[2][$a];
	$namespaces[$prefix] = $uri;
      }

      if( count($namespaces)>0 )
        NuPacketNamespace::link( $id, $namespaces );
    }

    private function publishLocal( $publisher )
    {
      //
      // GET PACKET ID
      $packet_id    = $this->packetID();

      //
      // PACKET
      $packet_data  = $this->packetData();
      $packet_xml   = $this->packetXML();

      //
      // CHECK FOR DUPLICATE DATA
      if( NuPackets::hash( $publisher, sha1( $packet_data ) )==-1 )
        throw new Exception("Duplicate packet detected", 11);

      //
      // CHECK FOR TIMESTAMP IN PACKET
      if( preg_match('/<timestamp>(\d+)</timestamp>/', $packet_data, $ts ) )
      {
	$timestamp = $ts[1];
      }
      else
      {
	$timestamp = time();
      }

      //
      // FILTER XML-DATA
      $packet_xml = NuEvent::filter('nu_fmp_publish_local', $packet_xml);

      //
      // create local packet identification
      $id = NuPackets::index( $publisher, $timestamp, $this->local );

      //
      // STORAGE LOCAL
      NuPacketStorage::save($id, $packet_xml->saveXML());

      //
      // TIMESTAMP
      $ts_node   = $packet_xml->createElement('timestamp', $timestamp);
      $packet_xml->documentElement->insertBefore( $ts_node, $packet_xml->documentElement->firstChild );

      //
      // ID
      $id_node   = $packet_xml->createElement('id', $id);
      $packet_xml->documentElement->insertBefore( $id_node, $packet_xml->documentElement->firstChild );

      //
      // USER
      $user_node = $packet_xml->createElement('user');
      $user_node->appendChild($packet_xml->createElement('id', $publisher));
      $user_node->appendChild($packet_xml->createElement('name', $GLOBALS['USER_CONTROL']['name']));
      $user_node->appendChild($packet_xml->createElement('domain', $GLOBALS['DOMAIN']));

      //
      // FILTER USER NODE
      $user_node = NuEvent::filter('nu_fmp_publish_user_xml', $user_node);

      //
      // APPEND
      $packet_xml->documentElement->appendChild($user_node);

      //
      // CHECK FOR NAMESPACES 
      if( preg_match_all('/xmlns:(\w+)="(http:\/\/[^"]+?)"/', substr( $packet_data, 0, strpos($packet_data,'>') ), $xmlns ) )
      {
        $this->linkNS( $id, $xmlns );
      }

      //
      // PACKET XML->DATA
      $packet_data = trim(preg_replace('/<\?xml.+?\?>/', '', $packet_xml->saveXML()));

      //
      // PUBLISH
      // using id, insert packet id into subscriber boxes
      $a = NuPackets::publish( $publisher, $id );

      //
      // QUEUE
      NuFederatedPublishing::queue( $id, $publisher, $packet_data );

      //
      // ping dispatch
      NuFiles::ping( "http://" . $GLOBALS['DOMAIN'] . "/api/fmp/dispatch.json?id={$id}" );

      //
      // HOOK
      NuEvent::action( 'nu_fmp_published', $packet_xml, $id );

      //
      // RETURN
      return array($id, $a);
    }

    private function publishRemote( $publisher_id )
    {
      //
      // GET PACKET ID
      $packet_id    = $this->packetID();

      //
      // PACKET
      $packet_data  = $this->packetData();
      $packet_xml   = $this->packetXML();
      $packet_head  = substr( $packet_data, strpos($packet_data,'<fp'), strpos($packet_data,'>')+1 );

      //
      // CHECK FOR DUPLICATE DATA
      if( NuPackets::hash( $publisher_id, sha1( $packet_data ) )==-1 )
        throw new Exception("Duplicate packet detected", 11);

      //
      // CHECK FOR TIMESTAMP IN PACKET
      if( preg_match('/<timestamp>(\d+)</timestamp>/', $packet_data, $ts ) )
      {
	$timestamp = $ts[1];
      }
      else
      {
	$timestamp = time();
      }

      //
      // GET TRUE PUBLISHER
      $publisher = new Object();
      $publisher->proxy = false;

      $user_node = $packet_xml->getElementsByTagName('user');
      if( $user_node->length )
      {
        $user_name   = $user_node->item(0)->getElementsByTagName('name');
        $user_domain = $user_node->item(0)->getElementsByTagName('domain');

	if( !$user_name->length || !$user_domain->length )
	  throw new Exception("User node must contain name-domain");
	
	$publisher->name   = $user_name->item(0)->textContent;
	$publisher->domain = $user_domain->item(0)->textContent;

	if( strtolower($publisher->name) != strtolower($GLOBALS['FPS_AUTHORIZED']['name']) )
	  $proxy_published = true;
	else if( strtolower($publisher->domain) != strtolower($GLOBALS['FPS_AUTHORIZED']['domain']) )
	  $proxy_published = true;
	
	if( $proxy_published )
	{
	  $publisher->id = NuUser::userID( $publisher->name, $publisher->domain, false, true );
	  $publisher->proxy = true;
	}
      }

      if( $publisher->proxy == false )
      {
        $publisher->id = $publisher_id;
      }

      //
      // ID
      $id = NuPackets::index( $publisher->id, $timestamp, $this->local );

      //
      // LOG PROXY AUTH
      if( $publisher->proxy )
      {
	NuPackets::proxy( $publisher_id, $id );
      }

      //
      // HANDLE FEDERATED, USE TRUE PUBLISHER
      NuPackets::federate( $publisher->id, $packet_id, $id );

      //
      // LINK NAMESPACES 
      // namespace prefixes should be included in the POST
      if( preg_match_all('/xmlns:(\w+)="(http:\/\/[^"]+?)"/', substr( $packet_data, 0, strpos($packet_data,'>') ), $xmlns ) )
      {
        $this->linkNS( $id, $xmlns );
      }

      //
      // remove applicable fields
      foreach( array('id','timestamp') as $nn )
      {
        $node = $packet_xml->getElementsByTagName($nn);
	if( $node->length )
	{
	  $packet_xml->documentElement->removeChild( $node->item(0) );
	}
      }

      //
      // remove user
      $user_node = $packet_xml->getElementsByTagName('user');
      if( $user_node->length )
      {
      foreach( array('id','name','domain') as $nn )
      {
        $node = $user_node->item(0)->getElementsByTagName($nn);
	if( $node->length )
	{
	  $user_node->item(0)->removeChild( $node->item(0) );
	}
      }
      }

      //
      // FILTER XML-DATA
      $packet_xml = NuEvent::filter('nu_fmp_publish_remote', $packet_xml);

      //
      // REMOVE REDUNDANT NODE
      if( $user_node->length && !$user_node->item(0)->hasChildNodes() )
      {
        $packet_xml->documentElement->removeChild( $user_node->item(0) );
      }

      //
      // STORAGE REMOTE 
      NuPacketStorage::save($id, $packet_xml->saveXML());

      //
      // PUBLISH
      // using id, insert packet id into subscriber boxes
      $a = NuPackets::publish( $publisher_id, $id );

      //
      // HOOK
      NuEvent::action( 'nu_fmp_published', $packet_xml, $id );

      //
      // RETURN
      return array($id, $a);
    }

    //
    // PUBLISH - distinguish local/remote
    //
    private function publish()
    {
      //
      // GET PUBLISHER
      //
      $publisher    = $this->publisherID();

      if( !$publisher || !is_numeric($publisher) )
        throw new Exception("Invalid publisher", 5);

      if( $this->local )
      {
        return $this->publishLocal( $publisher );
      }
      else
      {
        return $this->publishRemote( $publisher );
      }
    }

    protected function initJSON()
    {
      $result = $this->publish();

      $o = new JSON($this->time);
      $o->status    = "ok";
      $o->id	    = $result[0];
      $o->message   = "Packet published to {$result[1]} subscribers";

      return $o;
    }

  }

  return postFederatedPublish;

?>
