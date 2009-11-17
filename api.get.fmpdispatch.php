<?php
    
    require_once( 'abstract.callwrapper.php' );

    class getFMPDispatch extends CallWrapper
    {
        /*
	  
	  Dispatch Ping
	  ==============
	  checks database for the packet_id to dispatch
	  allows asynchronous publishing

	  Params:
	  id (local packet id on the specific node)

	  Notes:
	  once the id is takeout of the db, is should be removed

	  Temp Storage:
	  (packet, user, data

	*/

	protected function initJSON()
	{
	  $packet_id = $this->call->id;

	  if( !is_numeric($packet_id) )
	    throw new Exception("Invalid packet id",4);

	  require_once('lib.nufederated.php');

	  $packet = NuFederatedPublishing::unqueue( $packet_id );

	  $o = new JSON($this->time);

	  if( is_array($packet) )
	  {

	    // dispatch packet
	    switch( $packet['mode'] )
	    {
	      case 'publish':
	        NuFederatedPublishing::dispatch( $packet['publisher'], $packet['global_id'], $packet['data'], false );
		break;

	      case 'republish':
	        NuFederatedPublishing::dispatch( $packet['publisher'], $packet['global_id'], $packet['data'], true );
		break;

	      case 'unpublish':
	        NuFederatedPublishing::undispatch( $packet['publisher'], $packet['global_id'] );
		break;
	    }

	    file_put_contents($GLOBALS['CACHE'] .'/dispatch.log', time() . ": {$packet_id} out for {$packet['mode']}\n", FILE_APPEND );

	    $o->status = "ok";
	    $o->message = "Packet mode: {$packet['mode']}";
	  }
	  else
	  {
	    $o->status = "error";
	  }

	  return $o;
	}
    }

    return getFMPDispatch;

?>
