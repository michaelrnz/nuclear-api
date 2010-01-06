<?php
	
	require_once( 'api.class.userauthmethod.php' );

	class postFMPUnfollow extends apiUserAuthMethod
	{

	        private function follow()
		{
		        $subscriber = $this->getAuth();
			$publisher  = $this->getUser();

			if( $publisher->id == $subscriber->id )
				throw new Exception("Cannot follow self");

			//
			// RELATION (user,party,model,remote)
			//
			require_once( 'lib.nurelation.php' );
			$a = NuRelation::destroy( $subscriber->id, $publisher->id );

			if( !$a ) return false;

			//
			// FEDERATED CORE / LOCAL
			//
			/*
			require_once( 'lib.nufederated.php' );

			// create tokens
			$token          = NuFederatedStatic::generateToken( $publisher->id );
			$token_secret   = NuFederatedStatic::generateToken( $token );

			// insert federated relation
			NuFederatedIdentity::addPublisherAuth( $subscriber->id, $publisher->id, $token, $token_secret );
			NuFederatedIdentity::addSubscriberAuth( $subscriber->id, $publisher->id, $token, $token_secret );
			*/

			WrapMySQL::void(
			  "delete from nu_federated_subscriber_auth ".
			  "where user={$publisher->id} && federated_user={$subscriber->id} ".
			  "limit 1;"
			);

			WrapMySQL::void(
			  "delete from nu_federated_publisher_auth ".
			  "where user={$subscriber->id} && federated_user={$publisher->id} ".
			  "limit 1;"
			);

			return true;
		}

		protected function initJSON()
		{
			$resp = new JSON( $this->time );

			if( $this->follow() )
			{
				$resp->status  = "ok";
				$resp->message = "Unfollowed";
			}
			else
			{
				$resp->status  = "error";
				$resp->message = "Unable to unfollow";
			}

			return $resp;
		}

		protected function initXML()
		{
			require_once('class.xmlcontainer.php');

			$resp = new XMLContainer('1.0','utf-8',$this->time);
			$root = $resp->createElement('response');
			$root->setAttribute('request', 'fmp.unfollow');

			if( $this->follow() )
			{
			        $root->setAttribute('status', 'ok');
			        $root->appendChild( $resp->createElement("message", "Unfollowed") );
			}
			else
			{
			        $root->setAttribute('status', 'error');
			        $root->appendChild( $resp->createElement("message", "Unable to unfollow") );
			}

			$resp->appendRoot($root);

			return $resp;
		}
	}

	return postFMPUnfollow;

?>
