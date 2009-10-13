<?php
	
	/*
		nuclear.framework
		altman,ryan,2008

		UserLog library
		================================
			user login/out methods
			depends on sessions

	*/

	require_once('class.eventlibrary.php');
	require_once('lib.keys.php');

	class UserLog extends EventLibrary
	{
		protected static $driver;

		//
		// log user in, process api call
		//
		public static function in( $o )
		{
			$u = str_replace("+"," ",$o->user);
			$p = $o->password;

			// check for both fields
			if( !($u && $o ) ) throw new Exception("Missing user or pass");

			// generate pass
			$pass = Keys::password( $u, $p );

			// check for login
			require_once('lib.id.php');
			$index = ID::userLoginByPassword($u, $pass);

			if(isset($index[0]))
			{
				//
				// log user into the db
				$session = self::inSuccess( $index );

				//
				// fire success, with result
				$o->id = $index[0];
				self::fire( 'LoginSuccess', $o );

				return $session;
			}

			return false;
		}

		//
		// insert user instance into db table
		//
		public static function inSuccess( $result )
		{
			$id=$result[0];
			$username=$result[1];
			$email=$result[2];

			//
			// new session
			// Sessions::goSession();
			session_start();
			setcookie( $GLOBALS['APPLICATION_SESSION'], session_id() );

			//
			// SESSION ip set
			//$sq = "INSERT IGNORE INTO nuclear_logged (user, session, ip) VALUES ($id, '". $session ."', ". Sessions::ipToINT( $_SESSION['IP'] ) .");";
			//WrapMySQL::affected( $sq, "Unable to log session into db");

			//
			// session variables
			$_SESSION['logged']='1';
			$_SESSION['id']=$id;
			$_SESSION['email']=$email;
			$_SESSION['username']=$username;

			//
			// allow user control
			$_SESSION['USER_CONTROL']=$result;

			return session_id();
		}

		//
		// log user out
		//
		public static function out()
		{
			//
			// session data for events
			$session_data = $_SESSION;

			//
			// kill session in system
			$session = Sessions::killSession();

			//
			// check for no session
			if( $session === "" ) throw new Exception("Empty session");

			//
			// remove session from db
			$sq = "DELETE FROM nuclear_logged WHERE session = '$session' LIMIT 1;";
			$r = WrapMySQL::affected( $sq );

			if( $r>0 )
			{
				self::fire( 'LogoutSuccess', $session_data );
			}

			return true;

		}

	}

	UserLog::init();

?>
