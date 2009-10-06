<?php
	/*
		nuclear.framework
		altman,ryan,2008

		Verification processing
		====================================
		based on
		Verification checking class
	*/

	require_once('lib.text.php');
	require_once('class.eventlibrary.php');

	class Verification extends EventLibrary
	{
		protected static $driver;
		
		public static function post( $d )
		{
			if( !($h = str_replace(' ','+',$d->hash)) ) $exc = "Missing hash";
			if( !($u = str_replace("'","",$d->user)) ) $exc = "Missing user";
			/// should not contain '

			//
			// fixed length hash
			if( preg_match('/^[_\+=0-9A-Za-z]{44}$/', $h)==0 ) $exc = "Invalid hash format";

			$verification = WrapMySQL::q( "SELECT * FROM nuclear_verify WHERE hash='$h' && user='$u' LIMIT 1;", "Unable to verify user" . mysql_error() );

			if( !$verification || mysql_num_rows( $verification )!=1 )
			{
				$exc = "No matching verification data";
			}

			//
			// throw exception, error
			if( $exc ) throw new Exception($exc);

			//
			// get array data
			$verified = mysql_fetch_array( $verification );
			$pass = $verified['pass'];

			//
			// api hash generate
			require( 'lib.keys.php' );

			// recoverable and possibly generated on other applications
			//$api_key = Keys::generate( $u . $pass );

			//
			// insert into username
			$q = "INSERT INTO nuclear_username (hash,name) VALUES (SHA1(LOWER('{$u}')),'{$u}');";
			$r = WrapMySQL::affected( $q, "Unabled to insert username" . mysql_error() );
			$id = mysql_insert_id();

			//echo "$id:$u:$h:$r";

			if( $r==1 )
			{
				try
				{
					$q = "INSERT INTO nuclear_user (id, name, email, domain, ts) VALUES ($id, '$u', '". $verified['email'] ."', '". $verified['domain'] ."', '". $verified['ts'] ."');";
					WrapMySQL::affected( $q, "Unable to insert user" . mysql_error(), 10 );

					$q = "INSERT INTO nuclear_userkey (id, pass, verify) VALUES ($id, '". $verified['pass'] ."', '". $verified['hash'] ."');";
					WrapMySQL::affected( $q, "Unabled to insert userkey" . mysql_error(), 11 );

					// NOTICE userapi has been removed, user tokens
					//$q = "INSERT INTO nuclear_userapi (id, key0, key1) VALUES ($id, '" . implode("','", $api_key) . "');";
					//WrapMySQL::affected( $q, "Unabled to insert userapi" . mysql_error(), 12 );

					$q = "INSERT INTO nuclear_system (id) VALUES ($id);";
					WrapMySQL::affected( $q, "Unabled to insert system" . mysql_error(), 13 );

					//
					// remove the verification
					self::remove( $u, $h );

					//
					// fire onSuccess
					$o = new Object();
					//$o->api_key = $api_key;
					$o->user_id = $id;

					self::fire( 'Success', $o );

					return $id;
				}
				catch( Exception $e )
				{
					//
					// unroll insertions
					switch( $e->getCode() )
					{
						case 14:
							mysql_query( "DELETE FROM nuclear_system WHERE id=$id LIMIT 1;" );
						case 13:
							mysql_query( "DELETE FROM nuclear_userapi WHERE id=$id LIMIT 1;" );
						case 12:
							mysql_query( "DELETE FROM nuclear_userkey WHERE id=$id LIMIT 1;" );
						case 11:
							mysql_query( "DELETE FROM nuclear_user WHERE id=$id LIMIT 1;" );
							break;
					}
				}
			}

			//
			// fire onFail
			self::fire( 'Failure', $d );

			return false;
		}

		public static function remove($u,$h)
		{
			$q= "DELETE FROM nuclear_verify WHERE user='$u' && hash='$h' LIMIT 1;";
			return WrapMySQL::affected( $q );
		}

		public static function domain( $c )
		{
			$user = str_replace("'","",$c->user);
			
			if( strlen( $user )>3 )
			{
				$q = "SELECT nuclear_user.id, nuclear_user.name, nuclear_user.domain, nuclear_system.verified, nuclear_userkey.api, nuclear_userkey.verify 
					FROM nuclear_user 
					LEFT JOIN nuclear_system ON nuclear_system.id=nuclear_user.id 
					LEFT JOIN nuclear_userkey ON nuclear_userkey.id=nuclear_user.id
					WHERE nuclear_user.name='$user';";

				//
				// query
				$r = WrapMySQL::q( $q, "Unable to query user domain" . mysql_error() );

				//
				// check exists
				if( mysql_num_rows( $r )==0 )
					throw new Exception("User does not exist");

				$row = mysql_fetch_array( $r );

				//
				// check verified
				if( $row['verified'] == 'yes' )
					throw new Exception("User domain already verified");

				//
				// scrape domain key
				$check = self::checkDomain( self::userDomain($row['name'],$row['domain']), base64_encode($row['verify']) );

				if( $check )
				{
					//
					// update verified
					mysql_query("UPDATE nuclear_system SET verified='yes' WHERE id={$row['id']} LIMIT 1;");

					//
					// return
					return true;
				}
			}

			//
			// fire failure
			$o = new Object();
			$o->message = "Failed";

			self::fire('Failure', $o);

			return false;
		}

		private static function userDomain( $u, $d )
		{
			return "http://$d/$u.taiga";
		}

		private static function checkDomain( $url, $key )
		{
			require_once('lib.files.php');

			//
			// hit domain within first 2048 bytes
			$hay = Files::uri( $url, 2048 );

			//
			// search response for key
			if( strpos( $hay, $key )!==false )
				return true;

			return false;
		}
	}

	Verification::init();

?>
