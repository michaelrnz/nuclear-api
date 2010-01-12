<?php
	
	/*
		nuclear.framework
		altman,ryan,2008

		Registration API
		===========================================
			generic user registration model
	*/

	require_once( 'abstract.callwrapper.php' );

	class postRegister extends CallWrapper
	{
		protected function initJSON()
		{
			require_once( 'class.userregistration.php' );
			require_once( 'lib.register.php' );

			//
			// include the json
			$o = new JSON( $this->time );

			$registration = new UserRegistration( $this->call, $this->call->verifyPass );

			if( $ver = Register::post( $registration ) )
			{
				$o->valid = 1;
				$o->message = "Check email for verification.";
				$o->json->hash = $ver;
			}
			else
			{
				$o->valid = 0;
				$keys = array_keys($GLOBALS['post_error']);
				$o->message = $GLOBALS['post_error'][$keys[0]];
			}

			return $o;
		}

		protected function initXML()
		{
			return $this->initJSON();
		}
	}

	return postRegister;

?>
