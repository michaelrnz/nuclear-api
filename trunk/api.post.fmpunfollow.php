<?php
	
	require_once( 'api.class.userauthmethod.php' );

	class postFMPUnfollow extends apiUserAuthMethod
	{

	        private function unfollow()
		{
		        $subscriber = $this->getAuth();
			$publisher  = $this->getUser();

			if( $publisher->id == $subscriber->id )
				throw new Exception("Cannot unfollow self");

			//
			// RELATION (user,party,model,remote)
			//
			require_once( 'lib.nurelation.php' );
			$relation = NuRelation::check( $subscriber->id, $publisher->id );

			if( is_null($relation) )
			  throw new Exception("Relation does not exist");

			$a = NuRelation::destroy( $subscriber->id, $publisher->id );

			if( !$a ) return false;

			//
			// FEDERATED CORE / LOCAL
			//
			WrapMySQL::void(
			  "delete from nu_federated_auth ".
			  "where publisher={$publisher->id} && subscriber={$subscriber->id} ".
			  "limit 1;"
			);

			return true;
		}

		protected function initJSON()
		{
			$resp = new JSON( $this->time );

			if( $this->unfollow() )
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

			if( $this->unfollow() )
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
