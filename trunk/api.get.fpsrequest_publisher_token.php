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

      if( !$domain )
	throw new Exception("Missing domain", 4);

      $req = NuFederatedExternal::requestPublisherKeys( $domain );

      $o = new JSON($this->call->time);
      
      if( $req )
      {
	$o->status = "ok";
	$o->message = "Requested Keys from {$domain}";
      }
      else
      {
	$o->status = "error";
	$o->message = "Keys already in request for {$domain}";
      }

      return $o;
    }
  }

  return getFPSPublisher_Token;

?>
