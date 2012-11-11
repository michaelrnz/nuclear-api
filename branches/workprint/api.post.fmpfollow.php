<?php
	
	require_once( 'api.class.userauthmethod.php' );

	class postFMPFollow extends apiUserAuthMethod
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
			$relation = NuRelation::check( $subscriber->id, $publisher->id );

			if( $relation == 'subscriber' )
			  throw new Exception("Already following publisher");

			if( $relation == 'publisher' )
			{
			  $model = 'mutual';
			}
			else if( is_null($relation) )
			{
			  $model = 'subscriber';
			}
			else
			{
			  throw new Exception("${relation} relation exists");
			}
			
			$a = NuRelation::update( $subscriber->id, $publisher->id, $model );
			if( !$a ) return false;


			//
			// FEDERATED CORE / LOCAL
			//
			require_once( 'lib.nufederated.php' );

			// create tokens
			$token          = NuFederatedStatic::generateToken( $publisher->id );
			$token_secret   = NuFederatedStatic::generateToken( $token );

			// insert federated relation
			NuFederatedIdentity::addFederatedAuth( $publisher->id, $subscriber->id, $token, $token_secret );

			return true;
		}

		protected function initJSON()
		{
			$resp = new JSON( $this->time );

			if( $this->follow() )
			{
				$resp->status  = "ok";
				$resp->message = "Following";
			}
			else
			{
				$resp->status  = "error";
				$resp->message = "Unable to follow";
			}

			return $resp;
		}

		protected function initXML()
		{
			require_once('class.xmlcontainer.php');

			$resp = new XMLContainer('1.0','utf-8',$this->time);
			$root = $resp->createElement('response');
			$root->setAttribute('request', 'fmp.follow');

			if( $this->follow() )
			{
			        $root->setAttribute('status', 'ok');
			        $root->appendChild( $resp->createElement("message", "Following") );
			}
			else
			{
			        $root->setAttribute('status', 'error');
			        $root->appendChild( $resp->createElement("message", "Unable to follow") );
			}

			$resp->appendRoot($root);

			return $resp;
		}
	}

	return postFMPFollow;

?>
