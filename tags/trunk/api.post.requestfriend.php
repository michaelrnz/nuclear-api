<?php
	
	/*
		example api call
	*/

	require_once( 'abstract.callwrapper.php' );
	require_once( 'lib.friend.php' );

	class postRequestFriend extends CallWrapper
	{
		protected function initJSON()
		{
			//
			// include the json
			$o = new JSON( $this->time );

			if( !($from = $GLOBALS['USER_CONTROL']['id']) )
				throw new Exception("RequestRelation, missing from-user");

			if( !($to = $GLOBALS['USER_ID']) )
				throw new Exception("RequestRelation, missing to-user");

			if( $to == $from )
				throw new Exception("Cannot request self as friend");

			if( $handler = (include 'handler.friends.php') )
			{
				if( method_exists($handler,'onRequest') )
				{
					NuclearFriend::init();
					NuclearFriend::addEventListener( 'onRequest', array($handler,'onRequest') );
				}
			}

			if( NuclearFriend::request( $to, $from, $this->call->reason ) )
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
			$o = $this->initJSON();
			$o->outputMessage = "No xml format for this method";
			return $o;
		}
	}

	return postRequestFriend;

?>
