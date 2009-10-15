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

      return 0;
    }

    public static function lookup( $prefix )
    {
      return self::id( $prefix, false, false );
    }

    //
    // LINK
    //
    public static function link( $packet_id, $namespace )
    {
      $ns_id      = array();

      foreach( $namespace as $prefix=>$uri )
        $ns_id[]  = NuPacketNamespace::id( trim($prefix), trim($uri) );

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
        "nu_federated_packet_namespace (unlink)");
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
    public static function index( $publisher, $timestamp='NULL', $local=false )
    {
      $ts = $timestamp ? $timestamp : time();
      $id = WrapMySQL::id(
        "insert into nu_packet_index (publisher, ts) ".
        "values ({$publisher}, {$ts});",
        "nu_packet_index error");

      if( $local )
      {
	WrapMySQL::void(
	  "insert into nu_packet_inbox (subscriber, packet, ts) ".
	  "values ({$publisher}, {$id}, {$ts});"
        );
      }

      return $id;
    }

    //
    // REMOVE ID
    //
    public static function unindex( $publisher, $packet_id )
    {
      if( !$packet_id ) return;

      return WrapMySQL::affected(
              "delete from nu_packet_index where id={$packet_id} && publisher={$publisher} limit 1;",
              "nu_packet_index error (unpublish)"
             );
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

    public static function unfederate( $publisher, $federated_id )
    {
      return WrapMySQL::affected(
        "delete from nu_federated_packet ".
        "where id={$federated_id} && publisher={$publisher} ".
	"limit 1;",
        "nu_federated_packet (unfederate)");
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
	  "left join nu_user as U on U.id=R.user ".
	  "where R.party={$publisher} && ".
	  "U.domain=(select id from nu_domain where name='{$GLOBALS['DOMAIN']}')".
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
        "delete from nu_packet_inbox where packet={$packet_id};",
        "nu_packet_inbox error (unpublish)"
      );

      NuPacketNamespace::unlink( $packet_id );
    }

    //
    // ASSERT OWNERSHIP
    //
    public static function localID( $publisher, $packet_id, $local=true )
    {
      if( $local )
      {
	$table = 'nu_packet_index';
	$field = 'id';
      }
      else
      {
        $table = 'nu_federated_packet';
	$field = 'packet';
      }

      $idq = new NuQuery( $table );
      $idq->field( $field );

      $idq->where("id={$packet_id}");
      $idq->where("publisher={$publisher}");

      $rid = $idq->single();

      return $rid ? $rid[0] : false;
    }

  }

  class NuPacketStorage
  {
    public static function directory($id)
    {
      return "{$GLOBALS['CACHE']}fps/". ($id % 47) . '/' . ($id % 43) . '/';
    }

    public static function &read($id)
    {
      $f_dir = self::directory($id);

      $data = file_get_contents( $f_dir . "{$id}.xml" );
      return $data;
    }

    public static function save($id, &$data)
    {
      $f_dir = self::directory($id);
      mk_cache_dir($f_dir);

      $fn = $f_dir . "{$id}.xml";
      $tmp = $fn . "." . microtime(true);

      file_put_contents( $tmp, $data );
      rename( $tmp, $fn );
    }

    public static function unlink($id)
    {
      $f_dir = self::directory($id);
      @unlink($f_dir);
    }
  }

?>
