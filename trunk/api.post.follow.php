<?php
	
	/*
		example api call
	*/

	require_once( 'abstract.callwrapper.php' );
	require_once( 'lib.friend.php' );

	class postFollow extends CallWrapper
	{
		protected function initJSON()
		{
			//
			// include the json
			$o = new JSON( $this->time );

			if( !($from = $GLOBALS['USER_CONTROL']['id']) )
				throw new Exception("Follow, missing from-user");

			if( !($to = $GLOBALS['USER_ID']) )
				throw new Exception("Follow, missing to-user");

			if( $to == $from )
				throw new Exception("Cannot follow self");

			if( $handler = (include 'handler.follow.php') )
			{
				if( method_exists($handler,'onBeforeFollow') )
				{
					NuclearFriend::init();
					NuclearFriend::addEventListener( 'onBeforeFollow', array($handler,'onBeforeFollow') );
				}
				if( method_exists($handler,'onFollow') )
				{
					NuclearFriend::init();
					NuclearFriend::addEventListener( 'onFollow', array($handler,'onFollow') );
				}
			}

			if( NuclearFriend::follow( $to, $from, $this->call ) )
			{
				$o->valid = 1;
				$o->message = "Following";
			}
			else
			{
				$o->valid = 0;
				$o->message = "Unable to follow";
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

	return postFollow;

?>
