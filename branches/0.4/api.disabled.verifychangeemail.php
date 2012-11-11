<?php
	
	/*
		post.changePassword( $old_pass, $new_pass )
	*/

	require_once( 'abstract.callwrapper.php' );

	class postVerifyChangeEmail extends CallWrapper
	{
		protected function initJSON()
		{
			//
			// include userpost lib
			require_once('lib.userpost.php');

			//
			// include the json
			$o = new JSON( $this->time );

			//
			// requires session
			$id = $_SESSION['id'];

			if( !$id )
			{
				$o->valid = 0;
				$o->message = "Invalid session data.";
			}

			if( UserPost::verifyChangeEmail( $id, $this->call->hash ) )
			{
				$o->valid = 1;
				$o->message = "Email has been updated.";
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

	return postVerifyChangeEmail;
?>
