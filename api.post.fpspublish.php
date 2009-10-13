<?php

  require_once("abstract.callwrapper.php");
  require_once("class.domdocumentexceptor.php");
  require_once("lib.nufederated.php");

  class postFederatedPublish extends CallWrapper
  {
    /*
	PARAMS
	id	  // remote identification of packet
	publisher // should be known from FPS_AUTH 
	packet    // <fps packet
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

      // check packet as valid XML
      try
      {
	$packet_xml = new DOMDocumentExceptor("1.0","utf-8");
	$packet_xml->loadXML( $packet_data );
      }
      catch( Exception $e )
      {
	throw new Exception("Packet is not valid XML", 5);
      }

      // check packet data hash for duplication
      if( NuFederatedPacket::insertHash( $publisher, sha1( $packet_data ) )==-1 )
	throw new Exception("Duplicate packet detected", 11);

      // create packet index from federated user
      $id = NuFederatedPacket::insertIndex( $packet_id, $publisher );

      //
      // insert namespaces
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
	  NuFederatedPacket::linkNamespace( $id, $namespaces );
      }

      // using id, insert packet id into subscriber boxes
      $a  = NuFederatedPacket::publish( $publisher, $id );

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
