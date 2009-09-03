<?php
	
	/*
		nuclear.framework
		altman,ryan,2008

		LoginAPI call
		=======================================
			logins in, starts session
			returns session id
	*/

	require_once( 'abstract.callwrapper.php' );

	class postLogin extends CallWrapper
	{

		protected function initJSON()
		{
			// check for already logged
			if( isset($_SESSION['USER_CONTROL']) && $_SESSION['USER_CONTROL']['id']>0 ) throw new Exception("User already logged in", 3);

			//
			// include the lib
			require_once( 'lib.userlog.php' );

			// make return object
			$o = new JSON( $this->time );

			if( $ver = UserLog::in( $this->call ) )
			{
				$o->valid = 1;
				$o->msg = "You are now logged in.";
				$o->session = $ver;
			}
			else
			{
				$o->valid = 0;
				$o->msg = "Please check your credentials.";
			}

			return $o;

		}

		protected function initXML()
		{
			return $this->initJSON();
		}
	}

	return postLogin;

?>
