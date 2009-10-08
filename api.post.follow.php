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

			if( !($subscriber_id = $GLOBALS['USER_CONTROL']['id']) )
				throw new Exception("Follow, missing from-user");

			if( !($publisher_id = $GLOBALS['USER_ID']) )
				throw new Exception("Follow, missing to-user");

			if( $publisher_id == $subscriber_id )
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

			if( NuclearFriend::follow( $publisher_id, $subscriber_id, $this->call ) )
			{

			  //
			  // FEDERATED CORE / LOCAL
			  //
			  require_once( 'lib.nufederated.php' );

			  // create tokens
			  $token          = NuFederatedStatic::generateToken( $publisher_id );
			  $token_secret   = NuFederatedStatic::generateToken( $token );

			  // insert federated relation
			  NuFederatedIdentity::addPublisherAuth( $subscriber_id, $publisher_id, $token, $token_secret );
			  NuFederatedIdentity::addSubscriberAuth( $subscriber_id, $publisher_id, $token, $token_secret );

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
