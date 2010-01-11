<?php
	
	/*
		nuclear.framework
		altman,ryan,2008

		Verification API
		================================================
			generic verification model
	*/

	require_once( 'abstract.callwrapper.php' );

	class postVerify extends CallWrapper
	{
		protected function initJSON()
		{
			//
			// include the json
			$o = new JSON( $this->time );
			
			require_once( 'lib.verification.php' );

			//
			// 3rd party handler for successful verification
			include( 'handler.postVerify.php' );
			if( class_exists('postVerifyHandler',false) )
			{
				Verification::addEventListener('onSuccess', array('postVerifyHandler','success'));
			}

			//
			// proceed with process
			if( ($id = Verification::post( $this->call )) )
			{
				$o->valid = 1;
				$o->id = $id;
				$o->msg = "You may now proceed to login";
			}
			else
			{
				$o->valid = 0;
				$o->msg = "Your verification was invalid or expired, please register again";
			}

			return $o;

		}

		protected function initXML()
		{
			return $this->initJSON();
		}
	}

	return postVerify;
?>
