<?php
  
  /*
      NuUser Library
      ===============================
      nuclear
      altman.ryan 2009 fall
      ===============================

      Basic user indexing for both
      local and federated users.

  */

  class NuUser
  {

    //
    // identify 'name'
    private static function _nameID( $name_t, $name, $auto )
    {
      $n = safe_slash($name);
      $id = WrapMySQL::single(
             "select id from {$name_t} where name='{$n}' limit 1;",
	     "Error selecting {$name_t} id");
      
      if( $id )
	return $id[0];
      
      if( $auto )
      {
	WrapMySQL::void(
	     "insert into {$name_t} (name) values ('{$n}');",
	     "Error inserting {$name_t}");
	
	$id = mysql_insert_id();
	return $id;
      }

      return false;
    }

    //
    // identify domain
    public static function domainID( $domain, $auto=true )
    {
      return self::_nameID( 'nu_domain', $domain, $auto );
    }

    // identify name
    public static function nameID( $name, $auto=true )
    {
      return self::_nameID( 'nu_name', $name, $auto );
    }

    

    //
    // user@domain
    public static function filterUser( $user )
    {
      if( ($i = strpos($user, '@')) )
	$name = substr($user,0,$i);
      else
	$name = $user;
      
      return str_replace("'","",$name);
    }

    //
    // user@domain
    public static function filterDomain( $domain )
    {
      if( ($i = strpos($domain, '@')) )
	$name = substr($domain,$i+1);
      else
	$name = $user;
      
      return str_replace("'","",$name);
    }


    //
    // is valid
    public static function isValidName( $name )
    {
      return preg_match('/^[a-zA-Z0-9_\-]{3,64}$/', $name);
    }


    //
    // identify user
    public static function userID( $user, $domain, $domain_id, $auto=false )
    {

      /*
      //
      // check for new domain
      if( !$domain_id )
      {
	if( !$auto )
	  return false;

	return self::add( $user, $domain, 0 );
      }
      */

      if( !self::isValidName($user) )
        throw new Exception("Invalid federated username");

      //
      // try possible query
      $r = WrapMySQL::single(
	    "select U.id from nu_user as U ".
	    "left join nu_domain as D on D.id=U.domain ".
	    "left join nu_name as N on N.id=U.name ".
	    "where D.name='{$domain}' && N.name='{$user}' limit 1;",
	    "Error fetching user");

      if( !$r && $auto )
      {
	$id = self::add( $user, $domain, $domain_id );
	return $id;
      }

      return $r ? $r[0] : false;
    }


    //
    // add user (does not imply usership)
    public static function add( $name, $domain, $domain_id )
    {
      if( !$domain_id )
	$domain_id = self::domainID( $domain );

      $name_id  = self::nameID( $name );

      WrapMySQL::void(
	"insert into nu_user (domain, name) ".
	"values ({$domain_id}, '{$name_id}');",
	"Error adding user");

      return mysql_insert_id();
    }

  }

?>
