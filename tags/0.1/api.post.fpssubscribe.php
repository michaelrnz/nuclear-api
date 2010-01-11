<?php

  require_once("abstract.callwrapper.php");
  require_once("lib.nufederated.php");
  require_once("lib.nurelation.php");

  class postFPSSubscribe extends CallWrapper
  {

    /*
	PARAMS
	publisher
    */

    protected function initJSON()
    {
      $subscriber     = $GLOBALS['USER_CONTROL']['name'];
      $subscriber_id  = $GLOBALS['USER_CONTROL']['id'];
      $publisher      = str_replace("'","",NuFederatedUsers::user( $this->call->publisher ));
      $domain	      = str_replace("'","",NuFederatedUsers::domain( $this->call->publisher ));

      if( !$publisher )
	throw new Exception("Missing publisher", 4);

      if( !$domain )
	throw new Exception("Missing domain", 4);

      if( !NuUser::isValidName( $publisher ) )
        throw new Exception("Invalid publisher name", 5);

      // build Publisher/Consumer
      $consumer	  = new NuOpenConsumer( $domain );
      $domain_id  = $consumer->domainID;

      if( !$domain_id )
	throw new Exception("Publisher domain is not identified", 0);
        
        // check existing relation
        $publisher_id = NuUser::userID( $publisher, $domain, $domain_id );

        if( $publisher_id > 0 )
        {
            $relation = NuRelation::check( $subscriber_id, $publisher_id  );

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
        }


      // create request keys
      $request_token  = NuFederatedStatic::generateToken();
      $request_secret = NuFederatedStatic::generateToken();

      // create request relation
      WrapMySQL::void(
	"insert into nu_federated_auth_request (subscriber, publisher, domain, token, secret) ".
	"values ({$subscriber_id}, '{$publisher}', {$domain_id}, '{$request_token}', '{$request_secret}');",
	"Error inserting request auth");

      // post to domain's SHARE_TOKEN method
      $uri    = "http://{$domain}/api/fps/share_token.json";
      $params = array(
	"publisher"   => "{$publisher}@{$domain}",
	"subscriber"  => "{$subscriber}@{$GLOBALS['DOMAIN']}",
	"request_token"		=> "{$request_token}",
	"request_token_secret"	=> "{$request_secret}"
      );

      $json_resp  = NuFiles::curl( $uri, "POST", $params );

      $json = json_decode( $json_resp );

      if( is_null( $json ) )
      {
	WrapMySQL::void(
	  "delete from nu_federated_auth_request where subscriber={$subscriber_id} && publisher='{$publisher}' limit 1;"
	);
	throw new Exception("Publisher domain did not respond properly", 11);
      }

      if( $json->status == "error" )
      {
	WrapMySQL::void(
	  "delete from nu_federated_auth_request where subscriber={$subscriber_id} && publisher='{$publisher}' limit 1;"
	);
	throw new Exception("Publisher error: {$json->message}", 11);
      }

      $o = new JSON($this->time);
      $o->status    = "ok";
      $o->message   = "Federated subscription created";
      
      return $o;
    }
  }

  return postFPSSubscribe;

?>
