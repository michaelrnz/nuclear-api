<?php
	
	/*
		post.changePassword( $old_pass, $new_pass )
	*/

	require_once( 'abstract.callwrapper.php' );

	class postChangeEmail extends CallWrapper
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

			if( UserPost::changeEmail( $id, $user, $this->call->password, $this->call->new_email ) )
			{
				$o->valid = 1;
				$o->message = "Check email for verification.";
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

	return postChangeEmail;
?>
