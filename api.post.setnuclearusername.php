<?php
	
	/*
		example api call
	*/

	require_once( 'abstract.callwrapper.php' );
	require( 'lib.fields.php' );

	class postSetNuclearUsername extends CallWrapper
	{
		protected function initJSON()
		{
			$user_id = $GLOBALS['USER_CONTROL']['id'];
			$username = $this->call->username;

			//
			// include the json
			$o = new JSON( $this->time );

			if( Fields::isValid('username', $username) == 0 )
			{
				$o->message = "Invalid username";
				$o->valid = 0;

				return $o;
			}

			if( !$this->call->password )
			{
				$o->message = "Current password required";
				$o->valid = 0;

				return $o;
			}

			require_once('lib.keys.php');

			$password = Keys::password( $GLOBALS['USER_CONTROL']['name'], $this->call->password );

			if( ID::checkUserPassword( $user_id, $password )==0 )
			{
				$o->message = "Set username requires valid user-password";
				return $o;
			}

			$affect = WrapMySQL::affected(
					"UPDATE nuclear_username SET hash=SHA1(LOWER('{$username}')), name='{$username}' WHERE id=$user_id LIMIT 1;",
					"Unable to change username");

			$affect = WrapMySQL::affected(
					"UPDATE nuclear_user SET name='$username' WHERE id=$user_id LIMIT 1;",
					"Unable to change user");

			// more important than we know
			if( $affect>0 )
			{
				$newhash = Keys::password( $username, $this->call->password );
				WrapMySQL::affected(
					"UPDATE nuclear_userkey SET pass='{$newhash}' WHERE id=$user_id LIMIT 1;",
					"Unable to set user password, please use reset in unable to login");
			}

			if( $affect>0 )
			{
				$o->message = "User is now known as $username";
				$o->valid = 1;
			}
			else
			{
				$o->message = "No changes were made to username";
				$o->valid = 0;
			}
				
			return $o;
		}

		protected function initXML()
		{
			$o = $this->initJSON();
			$o->outputMessage = "No xml format for this method";
			return $o;
		}
	}

	return postSetNuclearUsername;

?>
