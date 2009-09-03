<?php
	
	/*
		example api call
	*/

	require_once( 'abstract.callwrapper.php' );
	require( 'lib.friend.php' );

	class postFriendRemove extends CallWrapper
	{
		protected function initJSON()
		{
			//
			// include the json
			$o = new JSON( $this->time );

			$r = NuclearFriend::remove( $GLOBALS['USER_CONTROL']['id'], $GLOBALS['USER_ID'] );

			if( $r>0 )
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

	return postFriendRemove;

?>
