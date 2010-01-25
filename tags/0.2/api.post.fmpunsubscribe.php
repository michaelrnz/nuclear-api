<?php

  require_once("abstract.callwrapper.php");
  require_once("lib.nufederated.php");
  require_once("lib.nurelation.php");
  require_once("class.nuselect.php");

  class postFederatedUnsubscribe extends CallWrapper
  {

    protected function authID()
    {
      $user = AuthorizedUser::getInstance();

      if( $user->auth_type == 'oauth_subscriber' )
      {
        $this->local == false;
        $auth           = new Object();
        $auth->id       = $user->id;
        $auth->name     = $user->name;
        $auth->domain   = $user->domain;
        return $auth;
      }
      else if( $user->isLocal() )
      {
        $this->local = true;
        $auth           = new Object();
        $auth->id       = $user->id;
        $auth->name     = $user->name;
        $auth->domain   = $user->domain;
        return $auth;
      }
      else
      {
        throw new Exception("Unauthorized publisher", 2);
      }
    }

    protected function process()
    {
      $subscriber  = $this->authID();

      if( $this->local )
      {
        return $this->unsubscribeLocal( $subscriber );
      }
      else
      {
        return $this->unsubscribeRemote( $subscriber );
      }

    }

    protected function unsubscribeLocal( $subscriber )
    {
      $publisher = new Object();
      $publisher->subscriber= $subscriber->id;
      $publisher->name = $this->call->publisher;

      if( !$publisher->name )
        throw new Exception("Missing publisher", 4);

      $publisher->domain  = str_replace("'","",NuFederatedUsers::domain( $publisher->name ));
      if( !$publisher->domain )
        throw new Exception("Missing domain", 4);

      $publisher->name = str_replace("'","",NuFederatedUsers::user( $publisher->name ));
      if( !NuUser::isValidName( $publisher->name ) )
        throw new Exception("Invalid publisher name", 5);

      // identify publisher-party
      $publisher->id = NuFederatedUsers::id( $publisher->name, $publisher->domain );
      if( !$publisher->id )
        throw new Exception("Publisher does not exist", 5);

      // check relation
      $model = NuRelation::check( $subscriber->id, $publisher->id );

      if( !isType("subscriber|mutual", $model) )
        throw new Exception("Not subscribed to publisher");

      // get auth
      $q = new NuSelect("nu_federated_auth A");
      $q->field( array("token","secret") );
      $q->where("publisher={$publisher->id} && subscriber={$subscriber->id}");
      $keys = $q->single();

      // remove relation
      $publisher->status = NuRelation::destroy( $subscriber->id, $publisher->id );

      // post to domain's unsubscribe method
      $consumer_key = $subscriber->domain;
      $auth_token   = $keys['token'];
      $auth_secret  = $keys['secret'];

      $params       = array(
        "publisher" => $publisher->name,
        "subscriber"=> "{$subscriber->name}@{$subscriber->domain}"
      );

      // create OAuth
      $oauth_params = new NuOAuthParameters( $consumer_key, $consumer_key, $auth_token, $auth_secret );
      
      // make request
      $oauth_resp   = NuOAuthRequest::text( $oauth_params, "http://{$publisher->domain}/api/fmp/unsubscribe.json", "POST", $params );

      // convert to json
      $json         = json_decode( $oauth_resp );

      // log resp
      file_put_contents( $GLOBALS['CACHE'] . '/unsubscribe.log', time() . ": {$oauth_resp}\n", FILE_APPEND );

      // remove publisher tokens
      WrapMySQL::void(
       "delete from nu_federated_auth ".
       "where publisher={$publisher->id} && subscriber={$subscriber->id}  ".
       "limit 1;"
      );

      NuEvent::action('nu_local_unsubscribe', $publisher);
      return $publisher;
    }

    protected function unsubscribeRemote( $subscriber )
    {
      $publisher = new Object();
      $publisher->subscriber= $subscriber;
      $publisher->name = $this->call->publisher;

      if( !$publisher->name )
        throw new Exception("Missing publisher", 4);

      $publisher->name = str_replace("'","",NuFederatedUsers::user( $publisher->name ));
      if( !NuUser::isValidName( $publisher->name ) )
        throw new Exception("Invalid publisher name", 5);

      $publisher->domain  = $GLOBALS['DOMAIN'];
      if( !$publisher->domain )
        throw new Exception("Missing domain", 4);

      // identify publisher-party
      $publisher->id = NuFederatedUsers::id( $publisher->name, $publisher->domain, false );
      if( !$publisher->id )
        throw new Exception("Publisher does not exist", 5);

      // remove relation
      $publisher->status = NuRelation::destroy( $subscriber->id, $publisher->id );

      // remove subscriber tokens
      WrapMySQL::void(
       "delete from nu_federated_auth ".
       "where publisher={$publisher->id} && subscriber={$subscriber->id} ".
       "limit 1;"
      );

      NuEvent::action('nu_remote_unsubscribe', $publisher);
      return $publisher;
    }


    protected function initJSON()
    {
      $status = $this->process();

      $resp   = new JSON($this->time);
      $resp->status    = "ok";
      $resp->message   = "Federated subscription destroyed";
      $resp->publisher = $status->name . '@' . $status->domain;
      
      return $resp;
    }
  }


  return "postFederatedUnsubscribe";

?>
