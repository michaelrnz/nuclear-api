<?php

  require_once("abstract.callwrapper.php");
  require_once("lib.nurelation.php");
  require_once("lib.nufederated.php");

  class getFederatedAccess_Token extends CallWrapper
  {

    /*
	PARAMS
	none
	reads from FPS_REQUEST_AUTH global
    */

    protected function initJSON()
    {
      $req_data = AuthorizedUser::getInstance();

      if( is_null($req_data) )
        throw new Exception("Missing request auth");

      if( $req_data->auth_type != 'oauth_fmp' )
	throw new Exception("Unauthorized", 2);

      $subscriber_id = $req_data->subscriber;
      $publisher     = $req_data->publisher;
      $domain        = $req_data->publisher_domain;

      if( !$subscriber_id )
	throw new Exception("Missing subscriber", 4);

      if( !$domain )
	throw new Exception("Missing domain", 4);

      if( !$publisher )
	throw new Exception("Missing publisher", 4);

      // create federated user
      $publisher_id   = NuUser::userID( $publisher, $domain, true );

        $relation = NuRelation::check( $subscriber_id, $publisher_id );

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

      // create tokens
      $token          = NuFederatedStatic::generateToken( $publisher_id );
      $token_secret   = NuFederatedStatic::generateToken( $token );

      // insert federated relation (user, party, model, remote)
      NuRelation::update( $subscriber_id,$publisher_id, $model, true );
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

  return "getFederatedAccess_Token";

?>
