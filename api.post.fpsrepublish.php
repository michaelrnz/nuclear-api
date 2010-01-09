<?php

  require_once("abstract.callwrapper.php");
  require_once("class.domdocumentexceptor.php");
  require_once("lib.nupackets.php");
  require_once("lib.nufederated.php");

  class postFederatedRepublish extends CallWrapper
  {
    /*
	PARAMS
	id	  // remote identification of packet
    */

    private function publisherID()
    {
      if( $GLOBALS['AUTH_TYPE'] == 'oauth_publisher' )
      {
        $this->local = false;
        return $GLOBALS['AUTH_RESP']['publisher'];
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

    private function packetID( $publisher )
    {
      $packet_id  = intval($this->call->id);

      if( !$packet_id )
        throw new Exception("Invalid packet_id", 5);

      // get packet id by federation
      $id = NuPackets::localID( $publisher, $packet_id, $this->local );

      // try proxied packet
      if( !$this->local && !$id )
      {
	$id = NuPackets::proxyID( $publisher, $packet_id );

	if( $id )
	  $this->proxy = true;
      }

      if( !$id )
	throw new Exception("Unidentified publisher packet");

      return $id;
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

    private function republishLocal( $publisher )
    {
      //
      // GET PACKET ID
      $packet_id    = $this->packetID( $publisher );
      $local_id     = $packet_id['id'];
      $global_id    = $packet_id['global_id'];

      //
      // PACKET
      $packet_data  = $this->packetData();
      $packet_xml   = $this->packetXML();

      //
      // CHECK FOR DUPLICATE DATA
      //if( NuPackets::hash( $publisher, sha1( $packet_data ) )==-1 )
      //  throw new Exception("Duplicate packet detected", 11);

      //
      // FILTER XML-DATA
      $packet_xml = NuEvent::filter('nu_fmp_publish_local', $packet_xml);

      //
      // STORAGE LOCAL
      NuPacketStorage::save($local_id, $packet_xml->saveXML());

      //
      // ID
      $id_node   = $packet_xml->createElement('id', $global_id);
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
      NuPacketNamespace::unlink( $packet_id );
      if( preg_match_all('/xmlns:(\w+)="(http:\/\/[^"]+?)"/', substr( $packet_data, 0, strpos($packet_data,'>') ), $xmlns ) )
      {
        $this->linkNS( $id, $xmlns );
      }

      //
      // PACKET XML->DATA
      $packet_data = trim(preg_replace('/<\?xml.+?\?>/', '', $packet_xml->saveXML()));

      //
      // QUEUE
      $qid = NuFederatedPublishing::queue( $local_id, $publisher, $global_id, $packet_data, 'republish' );

      //
      // ping dispatch
      NuFiles::ping( "http://" . $GLOBALS['DOMAIN'] . "/api/fmp/dispatch.json?id={$qid}" );

      //
      // HOOK
      NuEvent::action( 'nu_fmp_republished', $packet_xml, $local_id );

      //
      // RETURN
      return $local_id;
    }

    private function republishRemote( $publisher_id )
    {
      //
      // GET PACKET ID
      $packet_id    = $this->packetID( $publisher_id );
      $local_id     = $packet_id['id'];

      //
      // PACKET
      $packet_data  = $this->packetData();
      $packet_xml   = $this->packetXML();
      $packet_head  = substr( $packet_data, strpos($packet_data,'<fp'), strpos($packet_data,'>')+1 );

      //
      // CHECK FOR DUPLICATE DATA
      //if( NuPackets::hash( $publisher_id, sha1( $packet_data ) )==-1 )
      //  throw new Exception("Duplicate packet detected", 11);

      //
      // LINK NAMESPACES 
      // namespace prefixes should be included in the POST
      NuPacketNamespace::unlink( $local_id );
      if( preg_match_all('/xmlns:(\w+)="(http:\/\/[^"]+?)"/', substr( $packet_data, 0, strpos($packet_data,'>') ), $xmlns ) )
      {
        $this->linkNS( $local_id, $xmlns );
      }

      //
      // remove applicable fields
      foreach( array('id','timestamp') as $nn )
      {
        $node = $packet_xml->getElementsByTagName($nn);
	foreach( $node as $N )
	{
	  $packet_xml->documentElement->removeChild( $N );
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
	foreach( $node as $N )
	{
	  $user_node->item(0)->removeChild( $N );
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
      NuPacketStorage::save($local_id, $packet_xml->saveXML());

      //
      // HOOK
      NuEvent::action( 'nu_fmp_republished', $packet_xml, $local_id );

      //
      // RETURN
      return $local_id;
    }


    private function republish()
    {
      //
      // GET PUBLISHER
      //
      $publisher    = $this->publisherID();

      if( !$publisher || !is_numeric($publisher) )
        throw new Exception("Invalid publisher", 5);

      if( $this->local )
      {
        return $this->republishLocal( $publisher );
      }
      else
      {
        return $this->republishRemote( $publisher );
      }
    }


    protected function initJSON()
    {
      $result = $this->republish();

      // dispatching complete
      $o = new JSON($this->time);
      $o->status = "ok";
      $o->message = "Packet republished";
      $o->id = $result;

      return $o;
    }
  }

  return postFederatedRepublish;

?>
