<?php

  require_once("abstract.callwrapper.php");
  require_once("lib.nufederated.php");

  class postFederatedUnpublish extends CallWrapper
  {
    /*
	PARAMS
	publisher // should be known from FPS_AUTH 
	[id]	  // remote identification of packet
    */

    protected function initJSON()
    {
      $publisher    = $GLOBALS['FPS_AUTHORIZED']['federated_user'];

      if( !$publisher || !is_numeric($publisher) )
	throw new Exception("Invalid publisher", 5);

      $packet_id  = intval($this->call->id);

      if( !$packet_id )
	throw new Exception("Invalid packet_id", 5);

      // get local packet id
      $id = NuFederatedPacket::localId( $packet_id, $publisher );

      // must have local id
      if( !$id )
	throw new Exception("Packet does not exist on node", 11);

      // flush index
      NuFederatedPacket::unpublish( $id );

      // hash storage, these should be retreivable
      $f_dir = "{$GLOBALS['CACHE']}fps/". ($id % 47) . '/' . ($id % 43) . '/';

      // remove hard file
      @unlink( $f_dir . "{$id}.xml" );

      // dispatching complete
      $o = new JSON($this->time);
      $o->status = "ok";
      $o->message = "Packet unpublished";

      return $o;
    }
  }

  return postFederatedUnpublish;

?>
