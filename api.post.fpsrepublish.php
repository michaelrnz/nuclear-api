<?php

  require_once("abstract.callwrapper.php");
  require_once("lib.nufederated.php");

  class postFederatedRepublish extends CallWrapper
  {
    /*
	PARAMS
	packet    // <fps packet
	publisher // should be known from FPS_AUTH 
	[id]	  // remote identification of packet
	[ns]	  // namespace of the packet
    */

    protected function initJSON()
    {
      $publisher    = $GLOBALS['FPS_AUTHORIZED']['federated_user'];

      if( !$publisher || !is_numeric($publisher) )
	throw new Exception("Invalid publisher", 5);

      $packet_id  = intval($this->call->id);

      if( !$packet_id )
	throw new Exception("Invalid packet_id", 5);

      $packet_data = $this->call->packet;

      if( !strlen($packet_data) )
	throw new Exception("Missing packet", 4);

      if( $this->call->ns )
	$packet_ns = $this->call->ns;

      // get local packet id
      $id = NuFederatedPacket::localId( $packet_id, $publisher );

      // must have local id
      if( !$id )
	throw new Exception("Packet does not exist on node", 11);

      // update namespace
      if( $packet_ns )
      {
	NuFederatedPacket::flushNamespace( $id );
	NuFederatedPacket::linkNamespace( $id, explode(',', $packet_ns) );
      }

      // hash storage, these should be retreivable
      $f_dir = "{$GLOBALS['CACHE']}fps/". ($id % 47) . '/' . ($id % 43) . '/';
      mk_cache_dir($f_dir);
      file_put_contents( $f_dir . "{$id}.xml", $packet_data );

      // dispatching complete
      $o = new JSON($this->time);
      $o->status = "ok";
      $o->message = "Packet republished";

      return $o;
    }
  }

  return postFederatedRepublish;

?>
