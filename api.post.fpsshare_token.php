<?php

  require_once("abstract.callwrapper.php");
  require_once("lib.nufederated.php");
  require_once("lib.nurelation.php");

  class postFederatedShare_Token extends CallWrapper
  {
    /*
	PARAMS
	publisher
	subscriber
	request_token
	request_token_secret
	request_signature
    */

    protected function initJSON()
    {
      $publisher = NuFederatedUsers::publisherID( $this->call->publisher );

      if( !$publisher )
	throw new Exception("Invalid publisher", 5);

      $subscriber = NuFederatedUsers::user( $this->call->subscriber );
      $subscriber_domain = NuFederatedUsers::domain( $this->call->subscriber );

      if( !$subscriber || !$subscriber_domain )
	throw new Exception("Invalid subscriber; user@domain", 5);

      $r_token = $this->call->request_token;
      $r_secret= $this->call->request_token_secret;

      if( !$r_token || !$r_secret )
	throw new Exception("Invalid request tokens", 5);

      // OAuth request for access_tokens usering request
      $consumer = new NuOpenConsumer( $subscriber_domain );

      $oauth_params = new NuOAuthParameters( $consumer->token, $consumer->secret, $r_token, $r_secret );

      // make request, get data
      $access_data = NuOAuthRequest::text( $oauth_params, "http://{$subscriber_domain}/api/fps/access_token.json", "POST" );

      file_put_contents($GLOBALS['CACHE'] . 'access_request.log', "$subscriber_domain $access_data\n", FILE_APPEND);

      // get json
      $resp = json_decode( $access_data );

      if( is_null($resp) )
	throw new Exception("Subscriber did not respond",6);

      if( !($oauth_token = $resp->oauth_token) )
	throw new Exception("Token was not returned", 4);

      if( !($oauth_token_secret = $resp->oauth_token_secret) )
	throw new Exception("Token secret was not returned", 4);

      // good, downmix subscriber
      $subscriber_id = NuFederatedUsers::subscriber( $this->call->subscriber, true );
        
        // need relation checking to set mutual
        $relation = NuRelation::check( $subscriber_id, $publisher );

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

      // insert federated relation (user, party, model, remote)
      NuRelation::update( $subscriber_id, $publisher, $model, true );
      NuFederatedIdentity::addSubscriberAuth( $subscriber_id, $publisher, safe_slash($oauth_token), safe_slash($oauth_token_secret));

      // now have access to publish to subscriber's inbox
      $o = new JSON($this->time);
      $o->status = "ok";
      $o->message = "Relation created";

      return $o;
    }
  }

  return postFederatedShare_Token;

?>
