<?php
	/*
		nuclear.framework
		altman,ryan,2008

		Key Generation, API
		====================================
	*/

	class Keys
	{
		private static function packhash( $hash, $value )
		{
			return str_replace('/','_', base64_encode( hash($hash, $value, true) ) );
		}

		public static function auth( $user_name, $timestamp=false, $app_secret=false )
		{
		  $ts = $timestamp ? $timestamp : time() + 315360000;
		  $secret = $app_secret;

		  if( !$secret && isset($GLOBALS['APPLICATION_AUTH_SECRET']) )
		  {
		    $secret = $GLOBALS['APPLICATION_AUTH_SECRET'];
		  }
		  else
		  {
		    throw new Exception("Unable to securely generate AUTH; missing application secret",4);
		  }

		  return self::packhash( "sha1", $user_name . '/' . $secret . '/' . $ts ) . '-' . $ts;
		}

		public static function checkAuth( $user_name, $key, $app_secret=false )
		{
		  $ts_index = strrpos($key, '-');
		  $ts = intval( substr( $key, $ts_index+1 ) );

		  // check expiration
		  if( $ts < time() )
		    return false;

		  // check for valid auth
		  if( $key === self::auth( $user_name, $ts, $app_secret ) )
		    return true;

		  return false;
		}

		public static function generate( $up )
		{
			// userpass + salt
			$values = array( $up, rand(), microtime(true) );

			// shuffle values, 9 variations
			shuffle( $values );

			// generate key
			$key = self::packhash( "sha256", implode('', $values) );

			// split key into 2 sections
			preg_match('/^(.{22})(.{22}$)/', $key, $key_array);

			// return
			return array_slice( $key_array, 1 );
		}

		public static function password( $u, $p )
		{
			$key = self::packhash( "sha1", strtolower($u).$p );
			return $key;
		}

		public static function regenerate( $id, $user, $phrase )
		{
			require('lib.fields.php');

			if( !Fields::isValidPassword( $phrase ) )
				throw new Exception("Invalid phrase format (6-64)");

			$pass2 = self::password( $user, $phrase );
			$new_key = self::generate( $user . $pass2 );

			if( WrapMySQL::affected( "UPDATE nuclear_userapi SET key0='{$new_key[0]}', key1='{$new_key[1]}' WHERE id=$id LIMIT 1;" ) )
			{
				return implode("", $new_key);
			}
			return false;
		}
	}
?>
