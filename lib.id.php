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

		  $q = "select N.name, U.email, S.*, (S.level+0) as level_id ".
		       "from nu_user ".
		       "inner join nuclear_user as U on U.id=nu_user.id ".
		       "left join nu_name as N on N.id=nu_user.name ".
		       "left join nuclear_system as S ON S.id=U.id ".
		       "where N.name='{$u}' limit 1;";

		       // second condition needed to make sure key is valid, although generating a random key is unlikely
		  
		  return WrapMySQL::single( $q, "Unabled to query user auth key" );
		}

		// NOTICE userapi has been removed, use tokens
		//
		// get user authorization by API key
		/*
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
		*/

		//
		// get user authorizaton by Login u-p
		public static function userLoginByPassword( $u, $p )
		{
			$user = safe_slash($u);

		  $q = "select N.name, U.email, S.*, (S.level+0) as level_id ".
		       "from nu_user ".
		       "inner join nuclear_userkey as K on K.id=nu_user.id ".
		       "left join nuclear_user as U on U.id=K.id ".
		       "left join nu_name as N on N.id=nu_user.name ".
		       "left join nuclear_system as S ON S.id=K.id ".
		       "where N.name='{$user}' && K.pass='{$p}' limit 1;";

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

		  $q = "select nu_user.id, N.name, D.name as domain, U.email, S.level, (S.level+0) as level_id ".
		       "from nu_user ".
		       "left join nu_domain as D on D.id=nu_user.domain ".
		       "left join nu_name as N on N.id=nu_user.name ".
		       "inner join nuclear_user as U on U.id=nu_user.id ".
		       "left join nuclear_system as S ON S.id=U.id ".
		       "where D.name='{$GLOBALS['DOMAIN']}' && N.name='{$n}' limit 1;";

		  return WrapMySQL::single( $q, "Unable to query user" );
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
			return  WrapMySQL::single( 
				  "SELECT N.name, U.email, S.*, (S.level+0) AS level_id ".
				  "FROM nu_user ".
				  "LEFT JOIN nu_name as N on N.id=nu_user.name ".
				  "LEFT JOIN nuclear_system as S on S.id=nu_user.id ".
				  "LEFT JOIN nuclear_user as U ON U.id=nu_user.id ".
				  "WHERE nu_user.id=$id LIMIT 1;", "Unable to get user control");
		}

	}

?>
