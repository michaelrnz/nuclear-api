<?php


  class NuPacketNamespace
  {
    //
    // IDENTIFY
    //
    private static function id( $prefix, $uri=false, $auto=true )
    {
      $ns_t = "nu_federated_namespace";

      $v = safe_slash($prefix);
      $u = safe_slash($uri);
      $id = WrapMySQL::single(
             "select id from {$ns_t} where prefix='{$v}' limit 1;",
             "Error selecting namespace id");
      
      if( $id )
        return $id[0];
      
      if( $auto )
      {
        WrapMySQL::void(
          "insert into {$ns_t} (prefix,uri) values ('{$v}', '{$u}');",
          "Error inserting namespace");
    
        $id = mysql_insert_id();
          return $id;
      }

      return false;
    }

    //
    // LINK
    //
    public static function link( $packet_id, $namespace )
    {
      $ns_id      = array();

      foreach( $namespace as $prefix=>$uri )
        $ns_id[]  = self::namespace( trim($prefix), trim($uri) );

      WrapMySQL::void(
        "insert ignore into nu_federated_packet_namespace (packet,namespace) ".
        "values ({$packet_id}," . implode("), ({$packet_id},", $ns_id) . ");",
        "nu_federated_packet_namespace (link)");
    }

    //
    // UNLINK
    //
    public static function unlink( $packet_id )
    {
      WrapMySQL::void(
        "delete from nu_federated_packet_namespace where packet={$packet_id};",
        "nu_federated_packet_namespace (flush)");
    }

  }



  class NuPackets
  {
    //
    // HASH PACKET BY PUB
    // 
    public static function hash( $publisher, $hash )
    {
      try
      {
        WrapMySQL::void(
	  "insert into nu_packet_hash (publisher, hash) ".
	  "values ($publisher, '{$hash}');",
	  "Packet hash error", 15);
      }
      catch( Exception $e )
      {
        if( $e->getCode() == 15 )
	  return -1;
	else
	  throw $e;
      }

      return 1;
    }

    //
    // NEW ID
    //
    public static function index( $publisher, $timestamp='NULL' )
    {
      $ts = $timestamp ? $timestamp : time();
      return WrapMySQL::id(
        "insert into nu_packet_index (publisher, ts) ".
        "values ({$publisher}, {$ts});",
        "nu_packet_index error");
    }

    //
    // FEDERATE PACKET
    //
    public static function federate( $publisher, $federated_id, $packet )
    {
      return WrapMySQL::id(
        "insert into nu_federated_packet (id, publisher, packet) ".
        "values ({$federated_id}, {$publisher}, {$packet});",
        "nu_federated_packet error");
    }

    //
    // PUBLISH
    //
    public static function publish(  $publisher, $packet_id, $timestamp=false )
    {
      $ts = $timestamp ? $timestamp : time();
      return WrapMySQL::affected(
        "insert ignore into nu_packet_inbox (".
          "select user as subscriber, {$packet_id} as packet, {$ts} as ts ".
	  "from nu_relation as R ".
	  "where R.party={$publisher}".
        ");",
        "nu_packet_inbox error (publish)");
    }

    //
    // UNPUBLISH
    //
    public static function unpublish( $packet_id )
    {
      if( !$packet_id ) return;

      WrapMySQL::void(
        "delete from nu_packet_index where id={$packet_id} limit 1;",
        "nu_packet_index error (unpublish)"
      );

      WrapMySQL::void(
        "delete from nu_packet_inbox where packet={$packet_id};",
        "nu_packet_inbox error (unpublish)"
      );

      NuPacketNamespace::unlink( $packet_id );
    }

    //
    // LOCAL ID, FOR RE/UN
    //
    public static function localID( $publisher, $federated_id )
    {
      $id = WrapMySQL::single(
          "select id from nu_federated_packet ".
          "where id={$federated_id} && publisher={$publisher} limit 1;",
          "nu_federated_packet error (localID)");

      return $id ? $id[0] : false;
    }

  }

?>
