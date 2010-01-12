<?php
	
	/*
		example api call
	*/

	require_once( 'abstract.callwrapper.php' );
        require_once( 'lib.nuuser.php' );
	require_once( 'lib.fields.php' );

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

			$password = new NuclearPassword( $GLOBALS['USER_CONTROL']['name'], $this->call->password );

			if( ID::checkUserPassword( $user_id, $password->token )==0 )
			{
				$o->message = "Set username requires valid user-password";
				return $o;
			}

                        //
                        // get id for new username
                        $name_id    = NuUser::nameID( $username );

                        //
                        // change the name in nu_user
                        $affect     = WrapMySQL::affected(
                                        "update nu_user set name={$name_id} where id={$user_id} limit 1;",
                                        "Unable to change nu_user");

                        //
                        // change the nuclear_username (possibly a view)
			$affect     = WrapMySQL::affected(
					"update nuclear_username SET name='{$username}' WHERE id={$user_id} limit 1;",
					"Unable to change username");

                        //
                        // change the nuclear_user name (possibly remove in future)
			$affect     = WrapMySQL::affected(
					"update nuclear_user SET name='{$username}' WHERE id={$user_id} LIMIT 1;",
					"Unable to change user");

			// more important than we know
			if( $affect>0 )
			{
				$new_pass = new NuclearPassword( $username, $this->call->password );
				WrapMySQL::affected(
					"UPDATE nuclear_userkey SET auth=UNHEX('{$new_pass}') WHERE id=$user_id LIMIT 1;",
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

	}

	return "postSetNuclearUsername";

?>
