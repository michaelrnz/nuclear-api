<?php

  require_once("abstract.callwrapper.php");
  require_once("lib.nupackets.php");
  require_once("lib.nufederated.php");

  class postFederatedUnpublish extends CallWrapper
  {
    /*
	PARAMS
	id	  // remote identification of packet
	publisher // should be known from FPS_AUTH or LOCAL
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

      // get packet id by federation
      $id = NuPackets::localID( $publisher, $packet_id, $this->local );

      if( !$this->local && !$id )
      {
        // test for proxied
	$id = NuPackets::proxyID( $publisher, $packet_id );

	if( $id )
	  $this->proxy = true;
      }

      if( !$id )
	throw new Exception("Unidentified publisher packet");

      return $id;
    }


    private function unpublish()
    {
      //
      // GET PUBLISHER, what about proxy?
      //
      $publisher = $this->publisherID();

      if( !$publisher || !is_numeric($publisher) )
        throw new Exception("Invalid publisher", 5);

      //
      // GET PACKET ID (local)
      //
      $packet_id = $this->packetID( $publisher );

      //
      // TEST FOR REMOVING ONLY FEDERATED
      //
      /*
      if( $this->local && $this->call->federated_only )
      {
	NuFederatedPublishing::undispatch( $publisher, $packet_id );
	return $packet_id;
      }
      */

      //
      // REMOVE INDEX
      //
      $a = NuPackets::unindex( $publisher, $packet_id );

      if( !$a )
	throw new Exception("Unidentified publisher packet");

      //
      // UNFEDERATE
      //
      if( !$this->local )
      {
	NuPackets::unfederate( $publisher, $this->call->id );
      }

      //
      // UNPUBLISH
      //
      NuPackets::unpublish( $packet_id );


      //
      // REMOVE STORAGE
      //
      NuPacketStorage::unlink( $packet_id );

      //
      // HOOK ACTION
      //
      NuEvent::action('nu_fmp_unpublished', $packet_id);


      //
      // UNPUBLISH TO SUBSCRIBERS
      //
      if( $this->local )
      {
	// queue for dispatch
	NuFederatedPublishing::queue( $packet_id, $publisher, "_void_", "unpublish" );

        // ping dispatch
        NuFiles::ping( "http://" . $GLOBALS['DOMAIN'] . "/api/fmp/dispatch.json?id={$id}" );
      }

      return $packet_id;
    }


    protected function initJSON()
    {
      $result = $this->unpublish();

      // dispatching complete
      $o = new JSON($this->time);
      $o->status = "ok";
      $o->message = "Packet unpublished";
      $o->id = $result;

      return $o;
    }

  }

  return postFederatedUnpublish;

?>
