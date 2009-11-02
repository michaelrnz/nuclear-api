<?php
	
	/*
		example api call
	*/

	require_once( 'abstract.callwrapper.php' );

	class getNuclearFollowing extends CallWrapper
	{
		protected function initJSON()
		{
			//
			// include the json
			$o = new JSON( $this->time );

			require( 'lib.friend.php' );

			$result = NuclearFriend::following( $GLOBALS['USER_CONTROL']['id'] );

			if( $result )
			{
				$friends = new Object();
				$fields = array('id','name','ts');

				while( $row = mysql_fetch_row( $result ) )
				{
					foreach( $fields as $f=>$v )
					{
						$friends->$v = $row[$f];
					}
				}

				$o->friends = $friends;
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
			require_once('class.friendsxml.php');

			//
			// get user
			//
			if( $this->call->uid )
			{
			  $this->call->user_id = $this->call->uid;
			}
			else if( $this->call->user )
			{
			  throw new Exception("User does not exist",3);
			}
			else
			{
			  $this->call->user_id = $GLOBALS['USER_CONTROL']['id'];
			  $this->call->user = $GLOBALS['USER_CONTROL']['name'];
			}

			$this->call->model = 'following';
			$xml = new FriendsXML( $this->call );

			return $xml->getObject();
		}
	}

	return getNuclearFollowing;

?>
