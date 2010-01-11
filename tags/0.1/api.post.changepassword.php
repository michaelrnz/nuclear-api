<?php
	
	/*
		post.changePassword( $old_pass, $new_pass )
	*/

	require_once( 'abstract.callwrapper.php' );

	class postChangePassword extends CallWrapper
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
			$user=$_SESSION['username'];

			if( !($id && $user) )
			{
				$o->valid = 0;
				$o->message = "Invalid session data.";
			}

			if( UserPost::changePassword( $id, $user, $this->call->old_password, $this->call->new_password ) )
			{
				$o->valid = 1;
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

	return postChangePassword;
?>
