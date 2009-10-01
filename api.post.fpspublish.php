<?php

  require_once("abstract.callwrapper.php");
  require_once("lib.nufederated.php");

  class postFederatedPublish extends CallWrapper
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

      // check packet data hash for duplication
      if( NuFederatedPacket::insertHash( $publisher, sha1( $packet_data ) )==-1 )
	throw new Exception("Duplicate packet detected", 11);

      // create packet index from federated user
      $id = NuFederatedPacket::insertIndex( $packet_id, $publisher );

      // using id, insert packet id into subscriber boxes
      $a  = NuFederatedPacket::publish( $publisher, $id );

      // insert namespace
      if( $packet_ns )
	NuFederatedPacket::linkNamespace( $id, explode(',', $packet_ns) );

      // hash storage, these should be retreivable
      $f_dir = "{$GLOBALS['CACHE']}fps/". ($id % 47) . '/' . ($id % 43) . '/';
      mk_cache_dir($f_dir);
      file_put_contents( $f_dir . "{$id}.xml", $packet_data );

      // dispatching complete
      $o = new JSON($this->time);
      $o->status = "ok";
      $o->message = "Packet published to {$a} subscribers";

      return $o;
    }
  }

  return postFederatedPublish;

?>
