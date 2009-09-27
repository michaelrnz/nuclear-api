<?php

  require_once("abstract.callwrapper.php");
  require_once("lib.nufederated.php");

  class getFPSPublisher_Token extends CallWrapper
  {

    /*
	PARAMS
	domain
	nonce
    */

    protected function initJSON()
    {
      $domain = $this->call->domain;
      $nonce  = $this->call->nonce;

      if( !$domain )
	throw new Exception("Missing domain", 4);

      if( !$nonce )
	throw new Exception("Missing nonce", 4);

      $subscriber_resp = NuFederatedExternal::providePublisherKeys( $domain, $nonce );

      $json = json_decode( $subscriber_resp );

      if( is_null($json) )
	throw new Exception("Subscriber did not accept keys", 11);

      // now have access to publish to subscriber's inbox
      $o = new JSON($this->call->time);
      $o->status = "ok";
      $o->message = "Keys sent";

      return $o;
    }
  }

  return getFPSPublisher_Token;

?>
