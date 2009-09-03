<?php
	/*
		nuclear.framework
		Altman,Ryan 2008

		ID Library
		===============================
		base ID library, used in nuclear
		user id and control querying
	*/

	class ID
	{

		//
		// private query composer for ByName calls
		//
		protected static function _idByName( $n, $t )
		{
			$name = safe_slash($n);

			$q = "SELECT id FROM $t WHERE name='$name' LIMIT 1;";

			//
			// query wrap
			$r = WrapMySQL::q( $q, "Unable to query id by name" );

			// data row
			$idr = mysql_fetch_row($r);

			if( $idr )
			{
				return $idr[0];
			}
			return 0;
		}

		//
		// private query composer for ByID calls
		//
		protected static function _nameById( $id, $t )
		{
			if( !is_numeric($id) ) return false;

			$q = "SELECT name FROM ". safe_slash($t) ." WHERE id=$id LIMIT 1;";

			//
			// query wrap
			$r = WrapMySQL::q( $q, "Unable to query id by name" );

			// data row
			$idr = mysql_fetch_row($r);

			if( $idr )
			{
				return $idr[0];
			}
			return false;
		}

		//
		// get user auth by auth_key (NuAPI)
		public static function userByAuthKey( $u, $k )
		{
		  require_once('lib.keys.php');

		  if( !Keys::checkAuth( $u, $k ) )
		    return false;

		  $q = "select nuclear_user.name, nuclear_user.email, nuclear_user.domain, nuclear_system.*, (nuclear_system.level+0) as level_id ".
		       "from nuclear_username ".
		       "left join nuclear_system ON nuclear_system.id=nuclear_username.id ".
		       "left join nuclear_user ON nuclear_user.id=nuclear_username.id ".
		       "where nuclear_username.hash=SHA1(LOWER('{$u}')) limit 1;";
		       // second condition needed to make sure key is valid, although generating a random key is unlikely
		  
		  return WrapMySQL::single( $q, "Unabled to query user auth key" );
		}

		//
		// get user authorization by API key
		public static function userByAPI( $k )
		{
			// check and split key
			if( preg_match('/^([\+=_0-9A-Za-z]{22})([\+=_0-9A-Za-z]{22})$/', str_replace(' ','+',$k), $key_match)==0 )
				throw new Exception("Invalid API key format");

			// simple user control query
			$q = "SELECT nuclear_user.name, nuclear_user.email, nuclear_user.domain, nuclear_system.*, (nuclear_system.level+0) AS level_id FROM nuclear_userapi
				LEFT JOIN nuclear_system ON nuclear_system.id=nuclear_userapi.id
				LEFT JOIN nuclear_user ON nuclear_user.id=nuclear_userapi.id
				WHERE key0='{$key_match[1]}' && key1='{$key_match[2]}'
				LIMIT 1;";

			return WrapMySQL::single( $q, "Unable to query user api key" );
		}

		//
		// get user authorizaton by Login u-p
		public static function userLoginByPassword( $u, $p )
		{
			$user = safe_slash($u);

			$q =   "SELECT nuclear_username.*, nuclear_user.email, nuclear_user.domain, nuclear_system.*, (nuclear_system.level+0) AS level_id 
				FROM nuclear_username
				LEFT JOIN nuclear_userkey ON nuclear_userkey.id=nuclear_username.id
				LEFT JOIN nuclear_user ON nuclear_user.id=nuclear_username.id
				LEFT JOIN nuclear_system ON nuclear_system.id=nuclear_username.id
				WHERE nuclear_username.name='$user' && nuclear_userkey.pass='$p';";

			return WrapMySQL::single( $q, "Unable to authenticate user by password" );
		}

		//
		// check user password valid
		public static function checkUserPassword( $id, $p )
		{
			$q =   "SELECT id FROM nuclear_userkey
				WHERE nuclear_userkey.id=$id && nuclear_userkey.pass='$p';";

			$r = WrapMySQL::q( $q, "Unable to check user password." );

			return mysql_num_rows( $r )>0;
		}
			

		//
		// get user id by name
		public static function userByName( $n )
		{
			return self::_idByName( $n, 'username' );
		}

		//
		// get user id by name
		public static function userById( $id )
		{
			return self::_nameById( $id, 'username' );
		}

		public static function userControlById( $id )
		{
			if( !is_numeric($id) ) return false;
			return  WrapMySQL::single( "SELECT nuclear_user.name, nuclear_user.email, nuclear_user.domain, nuclear_system.*, (nuclear_system.level+0) AS level_id FROM nuclear_system LEFT JOIN nuclear_user ON nuclear_user.id=nuclear_system.id WHERE nuclear_system.id=$id LIMIT 1;", "Unable to get user control");
		}

	}

?>
