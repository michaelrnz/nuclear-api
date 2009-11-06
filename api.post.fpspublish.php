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
      publisher // should be known from FPS_AUTH 
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

    private function publish()
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
      $packet_id    = $this->packetID();

      //
      // PACKET
      //
      $packet_data  = $this->packetData();
      $packet_xml   = $this->packetXML();
      $packet_head  = substr( $packet_data, strpos($packet_data,'<fp'), strpos($packet_data,'>')+1 );

      //
      // CHECK FOR DUPLICATE DATA
      //
      if( NuPackets::hash( $publisher, sha1( $packet_data ) )==-1 )
        throw new Exception("Duplicate packet detected", 11);

      //
      // CHECK FOR TIMESTAMP IN PACKET
      //
      if( preg_match('/ timestamp="(\d+)/', $packet_head, $ts ) )
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

      // create local packet identification
      $id = NuPackets::index( $publisher, $timestamp, $this->local );

      //
      // HANDLE FEDERATED
      //
      if( !$this->local )
      {
	// log as a federated packet
	NuPackets::federate( $publisher, $packet_id, $id );
      }
      else
      {
	// append ID
	$id_node   = $packet_xml->createElement('id', $id);
	$packet_xml->documentElement->insertBefore( $id_node, $packet_xml->documentElement->firstChild );

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
	  NuPacketNamespace::link( $id, $namespaces );
      }


      //
      // PUBLISH
      // using id, insert packet id into subscriber boxes
      //
      $a = NuPackets::publish( $publisher, $id );


      //
      // PACKET XML->DATA
      //
      $packet_data = str_replace('<?xml version="1.0"?>'."\n", '', $packet_xml->saveXML());


      //
      // STORAGE
      // hash storage, these should be retreivable
      // TODO possibly hook for storage?
      //
      NuPacketStorage::save($id, $packet_data);

      //
      // PUBLISH TO SUBSCRIBERS
      //
      if( $this->local )
      {
	NuFederatedPublishing::dispatch( $publisher, $id, $packet_data );

	// store packet for dispatch

	// ping dispatch
	// NuFiles::ping( "http://" . $GLOBALS['DOMAIN'] . "/api/fmp/dispatch.json?id={$id}" );
      }

      //
      // RETURN
      // 
      return array($id, $a);
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
