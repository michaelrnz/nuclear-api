<?php
	
	/*
		example api call
	*/

	require_once( 'abstract.callwrapper.php' );
	require_once( 'lib.keys.php' );

	class postRefreshAPIKey extends CallWrapper
	{
		protected function initJSON()
		{
			//
			// include the json
			$o = new JSON( $this->time );
			$o->valid = 0;

			$user_id = $GLOBALS['USER_CONTROL']['id'];
			$user    = $GLOBALS['USER_CONTROL']['name'];

			if( !$user_id )
			{
				$o->message = "Missing user authentication";
				return $o;
			}

			$password = Keys::password( $user, $this->call->password );

			if( ID::checkUserPassword( $user_id, $password )==0 )
			{
				$o->message = "RefreshAPI requires valid user-password";
				return $o;
			}

			if( $newkey = Keys::regenerate( $user_id, $user, $this->call->phrase ) )
			{
				$o->valid = 1;
				$o->key = $newkey;
			}

			return $o;
		}

		protected function initXML()
		{
			$this->initJSON();
		}
	}

	return postRefreshAPIKey;
?>
