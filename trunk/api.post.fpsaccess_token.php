<?php

  require_once("abstract.callwrapper.php");
  require_once("lib.nufederated.php");

  class postFPSAccess_Token extends CallWrapper
  {

    /*
	PARAMS
	none
	reads from FPS_REQUEST_AUTH global
    */

    protected function initJSON()
    {
      $req_data = $GLOBALS['FPS_REQUEST_AUTH'];

      if( !$req_data )
	throw new Exception("Missing request auth");

      $subscriber_id = $req_data['subscriber'];
      $publisher     = $req_data['publisher'];
      $domain_id     = $req_data['id'];

      if( !$subscriber_id )
	throw new Exception("Missing subscriber", 4);

      if( !$domain_id )
	throw new Exception("Missing domain", 4);

      if( !$publisher )
	throw new Exception("Missing publisher", 4);

      // create federated user
      $publisher_id   = NuFederatedUsers::id( $publisher, $req_data['domain'], $domain_id, true );

      // create tokens
      $token          = NuFederatedStatic::generateToken( $publisher_id );
      $token_secret   = NuFederatedStatic::generateToken( $token );

      // insert federated relation
      NuFederatedIdentity::addPublisherAuth( $subscriber_id, $publisher_id, $token, $token_secret );
      
      // remove request auth
      WrapMySQL::void(
        "delete from nu_federated_auth_request where subscriber={$subscriber_id} && publisher='{$publisher}' limit 1;"
      );

      // now have access to publish to subscriber's inbox
      $o = new JSON($this->call->time);
      $o->status	     = "ok";
      $o->message	     = "Authorization included";
      $o->oauth_token	     = $token;
      $o->oauth_token_secret = $token_secret;

      return $o;
    }
  }

  return postFPSAccess_Token;

?>