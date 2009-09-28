<?php
  
  /*
      Nuclear OAuthorize Module
      =======================================
      altman.ryan, 2009 Fall

      provides Services 4 aspects of OAuth
      access/user - federation/publisher

  */

  require_once('lib.nuoauth.php');

  class NuOAuthorize
  {

    public static function parameters( &$request )
    {

      $auth = array();
      foreach( array('oauth_consumer_key','oauth_nonce','oauth_signature_method','oauth_timestamp','oauth_token','oauth_version') as $p )
      {
	if( !isset($request[$p]) )
	  return array(false, "Missing {$p} from oauth parameters");

	$auth[$p] = $request[ $p ];
      }

      return $auth;
    }


    public static function user( $resource, $method, &$request, $param_filter='op|output|format' )
    {

      $auth         = self::parameters( $request );
      $consumer_key = str_replace("'","",$auth['oauth_consumer_key']);
      $token        = str_replace("'","",$auth['oauth_token']);
      $signature    = $request['oauth_signature'];

      if( !$signature )
	return array(false, "Missing signature from oauth parameters");

      //
      // get consumer secret
      $consumer = WrapMySQL::single(
		    "select * from nu_oauth_consumer as K ".
		    "where K.token='{$consumer_key} limit 1;",
		    "Error fetching Consumer token");
      
      if( !$consumer )
	return array(false, "Unauthorized Consumer");

      //
      // get publisher secret
      $user = WrapMySQL::single(
		"select K.* from nu_oauth_auth as K ".
		"where K.token='{$token} limit 1;",
		"Error fetching Authorization token");

      if( !$user )
	return array(false, "Unauthorized User Token");


      //
      // create Param object
      $oauth_params = new NuOAuthParameters( 
			    $consumer_key, 
			    $consumer['secret'], 
			    $token, 
			    $user['secret'],
			    $auth['oauth_signature_method'],
			    $auth['timestamp'],
			    $auth['nonce']);

      //
      // check signature
      if( $signature == NuOAuth::signature( $oauth_params, $resource, $method, $request, $param_filter ) )
	return $user;

      return array(false, "Unauthorized");
    }

    
    // FEDERATION

    //
    // Authorize a Publisher
    //
    public static function publisher( $resource, $method, &$request, $param_filter='op|output|format' )
    {

      $auth         = self::parameters( $request );
      $consumer_key = str_replace("'","",$auth['oauth_consumer_key']);
      $token        = str_replace("'","",$auth['oauth_token']);
      $signature    = $request['oauth_signature'];

      if( !$signature )
	return array(false, "Missing signature from oauth parameters");

      $consumer_table = 'nu_federated_publisher_domain';
      $token_table    = 'nu_federated_publisher_auth';
      $user_table     = 'nu_federated_user';
      $domain_table   = 'nu_federated_domain';

      //
      // get consumer-request relation
      $auth_data = WrapMySQL::single(
		    "select D.*, T.user, T.federated_user, U.name, C.token as consumer_key, C.secret as consumer_secret, T.token as token, T.secret as secret ".
		    "from {$consumer_table} as C ".
		    "left join {$user_table} as U on U.domain=C.domain ".
		    "left join {$token_table} as T on T.federated_user=U.id ".
		    "left join {$domain_table} as D on D.id=C.domain ".
		    "where C.token='{$consumer_token} && T.token='{$token}' limit 1;",
		    "Error fetching Tokens");
      
      if( !$auth_data )
	return array(false, "Unauthorized Consumer");

      if( !$auth_data['token'] )
	return array(false, "Unauthorized Token");

      //
      // create Param object
      $oauth_params = new NuOAuthParameters( 
			    $consumer_key, 
			    $auth_data['consumer_secret'], 
			    $token, 
			    $auth_data['secret'],
			    $auth['oauth_signature_method'],
			    $auth['timestamp'],
			    $auth['nonce']);

      //
      // check signature
      if( $signature == NuOAuth::signature( $oauth_params, $resource, $method, $request, $param_filter ) )
	return $auth_data;

      return array(false, "Unauthorized");
    }


    //
    // Authorize a Federation
    //
    public static function federation( $resource, $method, &$request, $param_filter='op|output|format' )
    {

      $auth         = self::parameters( $request );
      $consumer_key = str_replace("'","",$auth['oauth_consumer_key']);
      $token        = str_replace("'","",$auth['oauth_token']);
      $signature    = $request['oauth_signature'];

      if( !$signature )
	return array(false, "Missing signature from oauth parameters");

      $consumer_table = 'nu_federated_publisher_domain';
      $token_table    = 'nu_federated_auth_request';
      $domain_table   = 'nu_federated_domain';

      //
      // get consumer-request relation
      $auth_data = WrapMySQL::single(
		    "select D.*, C.token as consumer_key, C.secret as consumer_secret, T.token as token, T.secret as secret ".
		    "from {$consumer_table} as C ".
		    "left join {$token_table} as T on T.domain=C.domain ".
		    "left join {$domain_table} as D on D.id=C.domain ".
		    "where C.token='{$consumer_token}' && T.token='{$token}' limit 1;",
		    "Error fetching Tokens");
      
      if( !$auth_data )
	return array(false, "Unauthorized Consumer");

      if( !$auth_data['token'] )
	return array(false, "Unauthorized Token");

      //
      // create Param object
      $oauth_params = new NuOAuthParameters( 
			    $consumer_key, 
			    $auth_data['consumer_secret'], 
			    $token, 
			    $auth_data['secret'],
			    $auth['oauth_signature_method'],
			    $auth['timestamp'],
			    $auth['nonce']);

      //
      // check signature
      if( $signature == NuOAuth::signature( $oauth_params, $resource, $method, $request, $param_filter ) )
	return $auth_data;

      return array(false, "Unauthorized");
    }

  }


?>