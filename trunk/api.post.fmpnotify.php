<?php

  require_once("abstract.callwrapper.php");
  require_once("class.domdocumentexceptor.php");
  require_once("lib.nupackets.php");
  require_once("lib.nufederated.php");

  class postFederatedNotify extends CallWrapper
  {
    /*

      PARAMS
      packet    // <fps packet

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

    private function notifyLocal( $publisher_id )
    {
      //
      // PACKET
      $packet_data  = $this->packetData();
      $packet_xml   = $this->packetXML();

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
      // TIMESTAMP
      $ts_node   = $packet_xml->createElement('timestamp', $timestamp);
      $packet_xml->documentElement->insertBefore( $ts_node, $packet_xml->documentElement->firstChild );

      //
      // USER
      $user_node = $packet_xml->createElement('user');
      $user_node->appendChild($packet_xml->createElement('id', $publisher_id));
      $user_node->appendChild($packet_xml->createElement('name', $GLOBALS['USER_CONTROL']['name']));
      $user_node->appendChild($packet_xml->createElement('domain', $GLOBALS['DOMAIN']));

      //
      // APPEND
      $packet_xml->documentElement->appendChild($user_node);

      //
      // PACKET XML->DATA
      $packet_data = trim(preg_replace('/<\?xml.+?\?>/', '', $packet_xml->saveXML()));

      //
      // PUBLISH
      // using id, insert packet id into subscriber boxes
      // $a = NuPackets::publish( $publisher, $local_id );

      $publisher = new Object();
      $publisher->id    = $publisher_id;
      $publisher->proxy = false;
      $publisher->local = true;

      //
      // HOOK
      NuEvent::action( 'nu_fmp_notify', $packet_xml, $publisher );

      //
      // QUEUE
      $qid = NuFederatedPublishing::queue( 0, $publisher_id, 0, $packet_data, 'notify' );

      //
      // ping dispatch
      NuFiles::ping( "http://" . $GLOBALS['DOMAIN'] . "/api/fmp/dispatch.json?id={$qid}" );

      //
      // RETURN
      return true;
    }

    private function notifyRemote( $publisher_id )
    {
      //
      // PACKET
      $packet_data  = $this->packetData();
      $packet_xml   = $this->packetXML();
      $packet_head  = substr( $packet_data, strpos($packet_data,'<fp'), strpos($packet_data,'>')+1 );

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
      $publisher->remote = true;

      $user_node = $packet_xml->getElementsByTagName('user');
      if( $user_node->length )
      {
        $user_name   = $user_node->item(0)->getElementsByTagName('name');
        $user_domain = $user_node->item(0)->getElementsByTagName('domain');

	if( !$user_name->length || !$user_domain->length )
	  throw new Exception("User node must contain name-domain");
	
	$publisher->name   = $user_name->item(0)->textContent;
	$publisher->domain = $user_domain->item(0)->textContent;

	if( strtolower($publisher->name) != strtolower($GLOBALS['AUTH_RESP']['name']) )
	  $proxy_published = true;
	else if( strtolower($publisher->domain) != strtolower($GLOBALS['AUTH_RESP']['domain']) )
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
      // HOOK
      NuEvent::action('nu_fmp_notify', $packet_xml, $publisher);

      //
      // PUBLISH
      // using id, insert packet id into subscriber boxes
      // $a = NuPackets::publish( $publisher_id, $local_id );

      //
      // RETURN
      return true;
    }

    //
    // PUBLISH - distinguish local/remote
    //
    private function notify()
    {
      //
      // GET PUBLISHER
      //
      $publisher    = $this->publisherID();

      if( !$publisher || !is_numeric($publisher) )
        throw new Exception("Invalid publisher", 5);

      if( $this->local )
      {
        return $this->notifyLocal( $publisher );
      }
      else
      {
        return $this->notifyRemote( $publisher );
      }
    }

    protected function initJSON()
    {
      $result = $this->notify();

      $o = new JSON($this->time);
      $o->status    = "ok";
      $o->message   = "Notification sent";

      return $o;
    }

  }

  return postFederatedNotify;

?>
