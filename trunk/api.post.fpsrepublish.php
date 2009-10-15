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
	packet    // <fps packet
	publisher // should be known from FPS_AUTH 
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

    private function packetID( $publisher )
    {
      $packet_id  = intval($this->call->id);

      if( !$packet_id )
        throw new Exception("Invalid packet_id", 5);

      if( $this->local )
	return $packet_id;

      // get packet id by federation
      $id = NuPackets::localID( $publisher, $packet_id, $this->local );

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

    private function republish()
    {
      //
      // GET PUBLISHER
      //
      $publisher    = $this->publisherID();

      if( !$publisher || !is_numeric($publisher) )
        throw new Exception("Invalid publisher", 5);

      //
      // GET PACKET ID
      //
      $packet_id    = $this->packetID( $publisher );

      //
      // PACKET
      //
      $packet_data  = $this->packetData();
      $packet_xml   = $this->packetXML();
      $packet_head  = substr( $packet_data, strpos($packet_data,'<fp'), strpos($packet_data,'>')+1 );

      //
      // CHECK FOR DUPLICATE DATA
      //
      //if( NuPackets::hash( $publisher, sha1( $packet_data ) )==-1 )
        //throw new Exception("Duplicate packet detected", 11);

      $f_dir = "{$GLOBALS['CACHE']}fps/". ($packet_id % 47) . '/' . ($packet_id % 43) . '/';
      $old_packet = file_get_contents( $f_dir . "{$packet_id}.xml" );
      $old_packet_head = substr( $old_packet, 0, strpos($old_packet,'>')+1);

      //
      // CHECK FOR TIMESTAMP IN PACKET
      //
      /*
      if( preg_match('/ timestamp="(\d+)/', $packet_head, $ts ) )
      {
	$timestamp = $ts[1];
      }
      else if( preg_match('/ timestamp="(\d+)/', $old_packet_head, $ts ) )
      {
	$timestamp = $ts[1];
      }
      else
      {
	$timestamp = time();

	// append created_at
	$ts_node   = $packet_xml->createElement('created_at', gmdate('r',$timestamp));
	$packet_xml->documentElement->insertBefore( $ts_node, $packet_xml->documentElement->firstChild );
	$packet_xml->documentElement->setAttribute('timestamp', $timestamp);
      }
      */

      if( $this->local )
      {
	// append ID
	//$id_node   = $packet_xml->createElement('id', $packet_id);
	//$packet_xml->documentElement->insertBefore( $id_node, $packet_xml->documentElement->firstChild );

	// append USER
	$user_node = $packet_xml->createElement('user');
	$user_node->appendChild($packet_xml->createElement('id', $publisher));
	$user_node->appendChild($packet_xml->createElement('name', $GLOBALS['USER_CONTROL']['name']));
	$user_node->appendChild($packet_xml->createElement('domain', $GLOBALS['DOMAIN']));

	NuEvent::action('local_packet_user_xml', $user_node);

	$packet_xml->documentElement->appendChild($user_node);
      }


      //
      // LINK NAMESPACES 
      // namespace prefixes should be included in the POST
      //
      NuPacketNamespace::unlink( $packet_id );
      if( preg_match_all('/xmlns:(\w+)="(http:\/\/[^"]+?)"/', substr( $packet_data, 0, strpos($packet_data,'>') ), $xmlns ) )
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
	  NuPacketNamespace::link( $packet_id, $namespaces );
      }


      //
      // PACKET XML->DATA
      //
      $packet_data = str_replace('<?xml version="1.0"?>'."\n", '', $packet_xml->saveXML());


      //
      // STORAGE
      // hash storage, these should be retreivable
      // TODO possibly hook for storage?
      //
      NuPacketStorage::save($packet_id, $packet_data);

      //
      // PUBLISH TO SUBSCRIBERS
      //
      if( $this->local )
      {
	NuFederatedPublishing::dispatch( $publisher, $packet_id, $packet_data, true );
      }

      //
      // RETURN
      // 
      return $packet_id;

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
