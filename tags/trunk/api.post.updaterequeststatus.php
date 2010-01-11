<?php
	
	/*
		example api call
	*/

	require_once( 'abstract.callwrapper.php' );
	require( 'lib.friend.php' );

	class postUpdateRequestStatus extends CallWrapper
	{
		protected function initJSON()
		{
			//
			// include the json
			$o = new JSON( $this->time );

			/*
			include( 'handler.postRequestStatus.php' );
			if( class_exists('postRequestStatus',false) )
			{
				NuclearFriend::init();
				NuclearFriend::addEventListener('onSuccess', array('postVerifyHandler','success'));
			}
			*/

			if( NuclearFriend::updateRequestStatus( $GLOBALS['USER_CONTROL']['id'], $GLOBALS['USER_ID'], $this->getStatus() )>0 )
			{
				$o->valid = 1;
				$o->message "Request status was changed";
			}
			else
			{
				$o->valid = 0;
				$o->message "Request status was not changed";
			}

			return $o;
		}

		protected function initXML()
		{
			$o = $this->initJSON();
			$o->outputMessage = "No xml format for this method";
			return $o;
		}

		protected function getStatus()
		{
			return 'new';
		}
	}

	return postUpdateRequestStatus;

?>
