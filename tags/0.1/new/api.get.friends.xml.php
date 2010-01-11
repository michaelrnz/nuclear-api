<?php
	
	/*
		example api call
	*/

	require_once( 'abstract.callwrapper2.php' );

	class getFriends extends CallWrapper2
	{
		protected function build()
		{
			require('class.friendsxml.php');
			if( !$this->call->user_id )
			{
			  if( strlen($this->call->user)>0 )
			  {
			    throw new Exception("Invalid user", 3);
			  }
			  $this->call->user_id = $GLOBALS['USER_CONTROL']['id'];
			  $this->call->user = $GLOBALS['USER_CONTROL']['name'];
			}
			$xml = new FriendsXML( $this->call );

			return $xml->getObject();
		}
	}

	return getFriends;

?>
