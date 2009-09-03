<?php
	
	/*
		example api call
	*/

	require_once( 'abstract.callwrapper.php' );
	require( 'lib.friend.php' );

	class postRequestAccept extends CallWrapper
	{
		protected function initJSON()
		{
			//
			// include the json
			$o = new JSON( $this->time );

			if( ! $this->call->user )
			{
				$o->valid = 0;
				$o->message = "Missing user";
				return $o;
			}

			if( !$GLOBALS['USER_ID'] )
			{
				$o->valid = 0;
				$o->message = "User does not exist";
				return $o;
			}

			if( $handler = (include 'handler.friends.php') )
			{
				if( method_exists($handler,'onAccept') )
				{
					NuclearFriend::init();
					NuclearFriend::addEventListener( 'onAccept', array($handler,'onAccept') );
				}
			}

			if( $r = NuclearFriend::accept( $GLOBALS['USER_CONTROL']['id'], $GLOBALS['USER_ID'] ) )
			{
				$o->valid = 1;
			}
			else
			{
				$o->valid = 0;
				$o->message = "There was no requester by that username";
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

	return postRequestAccept;

?>
