<?php
	/*
		nuclear.framework
		altman,ryan,2008

		Verification processing
		====================================
		based on
		Verification checking class
	*/

	require_once('class.eventlibrary.php');
	require_once('lib.nuuser.php');

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
			if( preg_match('/^[_\+=0-9A-Za-z]$/', $h)==0 ) $exc = "Invalid hash format";

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

			//
			// insert into nu_user
			$id = NuUser::add( $u, $GLOBALS['DOMAIN'], 0 );

			if( $id>0 )
			{
				try
				{
					// TMP PATCH
					WrapMySQL::void( 
					  "insert into nuclear_username (id,hash,name) values ($id, SHA1(LOWER('{$u}')), '{$u}')",
					  "Unabled to insert username", 9);

					$q = "INSERT INTO nuclear_user (id, name, email, ts) VALUES ($id, '$u', '". $verified['email'] ."', '". $verified['ts'] ."');";
					WrapMySQL::affected( $q, "Unable to insert user" . mysql_error(), 10 );

					$q = "INSERT INTO nuclear_userkey (id, auth) VALUES ($id, UNHEX('". $verified['auth'] ."'));";
					WrapMySQL::affected( $q, "Unabled to insert userkey" . mysql_error(), 11 );

					// NOTICE userapi has been removed, user tokens

					$q = "INSERT INTO nuclear_system (id) VALUES ($id);";
					WrapMySQL::affected( $q, "Unabled to insert system" . mysql_error(), 13 );

					//
					// remove the verification
					self::remove( $u, $h );

					//
					// fire onSuccess
					$o = new Object();
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
						case 10:
							mysql_query( "DELETE FROM nuclear_username WHERE id=$id LIMIT 1;" );
							break;
					}

					mysql_query( "DELETE FROM nu_user WHERE id=$id LIMIT 1;" );
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

	}

	Verification::init();

?>
