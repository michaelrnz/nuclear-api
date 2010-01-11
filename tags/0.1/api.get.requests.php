<?php
	
	/*
		example api call
	*/

	require_once( 'abstract.callwrapper.php' );
	require( 'lib.friend.php' );

	class getRequests extends CallWrapper
	{
		protected function initJSON()
		{
			//
			// include the json
			$o = new JSON( $this->time );

			$user_id = $GLOBALS['USER_CONTROL']['id'];

			$result = NuclearFriend::requests( $GLOBALS['USER_CONTROL']['id'], $this->call->status );

			if( $result )
			{
				$fields = array('user_from','status','name','reason','ts');
				$requests = new Object();
				while( $row = mysql_fetch_array( $result ) )
				{
					foreach( $fields as $f )
					{
						$requests->$f = $row[$f];
					}
				}
				$o->requests = $requests;
			}
			return $o;
		}

		protected function initXML()
		{
			require('class.requestsxml.php');
			$this->call->user_id = $GLOBALS['USER_CONTROL']['id'];
			$xml = new RequestsXML( $this->call );

			return $xml->getObject();
		}
	}

	return getRequests;

?>
