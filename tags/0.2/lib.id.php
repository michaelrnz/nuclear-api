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

		  if( !NuclearAuthToken::verify( $k, $u ) )
		    return false;

		  $q = "select NuclearAuthorized.* ".
		       "from NuclearAuthorized ".
		       "where NuclearAuthorized.name='{$u}' limit 1;";

		  return WrapMySQL::single( $q, "Unabled to query user auth key" );
		}


		// NOTICE userapi has been removed, use tokens

		//
		// get user authorizaton by Login u-p
		public static function userLoginByPassword( $u, $p )
		{
			$user = safe_slash($u);

		  $q = "select NuclearAuthorized.* ".
		       "from NuclearAuthorized ".
		       "inner join nuclear_userkey as K on K.id=NuclearAuthorized.id ".
		       "where NuclearAuthorized.name='{$user}' && K.auth=UNHEX('{$p}') limit 1;";

			return WrapMySQL::single( $q, "Unable to authenticate user by password" );
		}

		//
		// check user password valid
		public static function checkUserPassword( $id, $auth )
		{
			$q =   "SELECT id FROM nuclear_userkey
				WHERE nuclear_userkey.id=$id && nuclear_userkey.auth=UNHEX('$auth');";

			$r = WrapMySQL::q( $q, "Unable to check user password." );

			return mysql_num_rows( $r )>0;
		}
			

		//
		// get user id by name
		public static function userByName( $n )
		{

		  $q = "select NU.* ".
		       "from NuclearAuthorized NU ".
		       "where NU.name='{$n}' && NU.domain='{$GLOBALS['DOMAIN']}' limit 1;";

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
				  "SELECT NuclearAuthorized.* ".
				  "FROM NuclearAuthorized ".
				  "WHERE NuclearAuthorized.id=$id LIMIT 1;", "Unable to get user control");
		}


                /*
                    Loading LocalUser singletons
                */

                //
                // load local user
                public static function loadUserByName( $n )
                {
		    $q = "select NU.* ".
		         "from NuclearAuthorized NU ".
		         "where NU.name='{$n}' && NU.domain='{$GLOBALS['DOMAIN']}' limit 1;";

		    if( $data = WrapMySQL::single( $q, "Unable to query user by name" ) )
                    {
                        $local_user     = new LocalUser( $data['id'], $data['name'], $data['email'] );
                        return true;
                    }

                    return false;
                }

                //
                // load local user
                public static function loadUserByID( $id )
                {
                    if( !is_numeric($id) ) return false;

                    $q  = "select NuclearAuthorized.* ".
                          "from NuclearAuthorized ".
                          "where NuclearAuthorized.id={$id} limit 1;";

                    if( $data = WrapMySQL::single( $q, "Unable to query user by id" ) )
                    {
                        $local_user     = new LocalUser( $data['id'], $data['name'], $data['email'] );
                        return true;
                    }

                    return false;
                }

	}

?>
