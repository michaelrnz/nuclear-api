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
		private function logout()
		{
			if( !isset($_SESSION['logged']) ) throw new Exception("User is not logged in");

			require_once( 'lib.userlog.php' );

			return UserLog::out();
		}

		protected function initJSON()
		{
			$logged = $this->logout();

			// include the json
			$o = new JSON( $this->time );

			if( $logged )
			{
				$o->status = "ok";
				$o->message = "You are now logged out.";
			}
			else
			{
				$o->status = "error";
				$o->message = "User was not logged out";
			}

			return $o;

		}

		protected function initXML()
		{
			$logged = $this->logout();

			require_once('class.xmlcontainer.php');

			$resp = new XMLContainer("1.0","utf-8",$this->time);

			$root = $resp->createElement("response");

			if( $logged )
			{
				$status = "ok";
				$message = "You are now logged out.";
			}
			else
			{
				$status = "error";
				$message = "User was not logged out";
			}

			$root->setAttribute("status", $status);
			$root->appendChild( $resp->createElement("message", $message) );

			$resp->appendChild($root);

			return $resp;
		}
	}

	return postLogout;

?>
