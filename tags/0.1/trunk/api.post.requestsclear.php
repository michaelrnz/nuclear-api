<?php
	
	/*
		example api call
	*/

	require_once( 'abstract.callwrapper.php' );
	require( 'lib.friend.php' );

	class postRequestsClear extends CallWrapper
	{
		protected function initJSON()
		{
			//
			// include the json
			$o = new JSON( $this->time );

			if( $r = NuclearFriend::clearRequests( $GLOBALS['USER_CONTROL']['id'] ) )
			{
				$o->valid = 1;
				$o->message = "Cleared $r requests";
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

	return postRequestsClear;

?>
