<?php

  require_once("abstract.callwrapper.php");
  require_once("lib.nufederated.php");

  class postFPSPublisher_Token extends CallWrapper
  {

    /*
	PARAMS
	domain
	nonce
	consumer_key
	consumer_secret
    */

    protected function initJSON()
    {
      $domain = $this->call->domain;
      $nonce  = $this->call->nonce;
      $consumer_key = $this->call->consumer_key;
      $consumer_secret=$this->call->consumer_secret;

      if( !$domain )
	throw new Exception("Missing domain", 4);

      if( !$nonce )
	throw new Exception("Missing nonce", 4);

      if( !$consumer_key )
	throw new Exception("Missing consumer_key", 4);

      if( !$consumer_secret )
	throw new Exception("Missing consumer_secret", 4);

      // now have access to publish to subscriber's inbox
      $o = new JSON($this->call->time);

      if( NuFederatedExternal::acceptPublisherKeys( $domain, $nonce, $consumer_key, $consumer_secret ) )
      {
	$o->status = "ok";
	$o->message= "Federated consumer keys accepted";
      }
      else
      {
	$o->status = "error";
	$o->message= "Federated consumer keys not accepted";
	$o->code = 11;
      }
	
      return $o;
    }
  }

  return postFPSPublisher_Token;

?>
