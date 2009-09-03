<?php
	
	/*
		nuclear.framework
		altman,ryan,2008

		Logout API
		====================================
			closes user session and data
	*/

	require_once( 'abstract.callwrapper.php' );

	class postLogout extends CallWrapper
	{
		protected function initJSON()
		{
			if( !isset($_SESSION['logged']) ) throw new Exception("User is not logged in");

			require_once( 'lib.userlog.php' );

			//
			// include the json
			$o = new JSON( $this->time );

			if( $ver = UserLog::out() )
			{
				$o->valid = 1;
				$o->message = "You are now logged out.";
			}
			else
			{
				$o->valid = 0;
			}

			return $o;

		}

		protected function initXML()
		{
			return $this->initJSON();
		}
	}

	return postLogout;

?>
