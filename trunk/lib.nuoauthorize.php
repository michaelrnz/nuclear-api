<?php
  
  /*
      Nuclear OAuthorize Module
      =======================================
      altman.ryan, 2009 Fall

      provides Services 4 aspects of OAuth
      access/user - federation/publisher

  */

  require_once('lib.nuoauth.php');
  require_once("class.nuselect.php");

  //
  // Token Select query
  //
  abstract class FederatedTokenSelect extends NuSelect
  {
    function __construct($consumer_key, $token)
    {
      parent::__construct("nu_federated_auth T");
      $this->field(
	      array(
		'U.name', 'U.domain',
		'T.publisher', 'T.subscriber', 
		'T.token', 'T.secret'
	      ));

      $this->where("T.token='{$token}'");
      $this->where("U.domain='{$consumer_key}'");
    }
  }

  //
  // Publisher tokens
  // Who is a subscriber for Publisher with $token
  //
  class OAuthPublisherTokens extends FederatedTokenSelect
  {
    function __construct($consumer_key, $token)
    {
      parent::__construct($consumer_key, $token);
      $this->join("NuclearUser as U",		      "U.id=T.publisher");
    }
  }

  //
  // Subscriber tokens
  // Who is the publisher for Subcriber with $token
  //
  class OAuthSubscriberTokens extends FederatedTokenSelect
  {
    function __construct($consumer_key, $token)
    {
      parent::__construct($consumer_key, $token);
      $this->join("NuclearUser as U",		      "U.id=T.subscriber");
    }
  }
  
  //
  // Federated Access Request
  //
  class OAuthFederatedAccess extends NuSelect
  {
      function __construct( $consumer_key, $token )
      {
          parent::__construct('nu_federated_auth_request T');
          $this->field(
                    array(
                        'T.publisher', 'T.subscriber',
                        'T.token', 'T.secret',
                        'D.name as publisher_domain'
           ));
           
           $this->join("nu_domain D", "D.id=T.domain");
           $this->where("T.token='{$token}'");
      }
  }



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


    public static function user( $resource, $method, &$request, $param_filter='' )
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

    
    //
    // Federated OAuth Pub-Sub
    //
    protected static function federated( $mode, $resource, $method, &$request, $param_filter='' )
    {

      $auth            = self::parameters( $request );
      $consumer_key    = str_replace("'","",$auth['oauth_consumer_key']);
      $consumer_secret = $consumer_key; // the domain
      $token           = str_replace("'","",$auth['oauth_token']);
      $signature       = $request['oauth_signature'];

      if( !$signature )
	return array(false, "Missing signature from oauth parameters");

      //
      // get consumer-request relation
      if( $mode == 'publisher' )
      {
        $quth = new OAuthPublisherTokens($consumer_key, $token);
      }
      else if( $mode == 'subscriber' )
      {
        $quth = new OAuthSubscriberTokens($consumer_key, $token);
      }

      $auth_data = $quth->single("Error fetching tokens");

      if( !$auth_data )
	return array(false, "Unauthorized Consumer");

      if( !$auth_data['token'] )
	return array(false, "Unauthorized Token");

      //
      // create Param object
      $oauth_params = new NuOAuthParameters( 
			    $consumer_key, 
			    $consumer_secret, 
			    $token, 
			    $auth_data['secret'],
			    $auth['oauth_signature_method'],
			    $auth['oauth_timestamp'],
			    $auth['oauth_nonce']);

      //
      // check signature
      if( $signature == NuOAuth::signature( $oauth_params, $resource, $method, $request, $param_filter ) )
	return $auth_data;

      return array(false, "Unauthorized");
    }


    //
    // Authorize a Publisher
    //
    public static function publisher( $resource, $method, &$request, $param_filter='' )
    {
      return self::federated( 'publisher', $resource, $method, $request, $param_filter );
    }

    //
    // Authorize a Subscriber
    //
    public static function subscriber( $resource, $method, &$request, $param_filter='' )
    {
      return self::federated( 'subscriber', $resource, $method, $request, $param_filter );
    }


    //
    // Authorize a Federation Request ? fmp/access_token
    //
    public static function federation( $resource, $method, &$request, $param_filter='' )
    {

      $auth            = self::parameters( $request );
      $consumer_key    = str_replace("'","",$auth['oauth_consumer_key']);
      $consumer_secret = $consumer_key;
      $token           = str_replace("'","",$auth['oauth_token']);
      $signature       = $request['oauth_signature'];

      if( !$signature )
	return array(false, "Missing signature from oauth parameters");

      $quth = new OAuthFederatedAccess( $consumer_key, $token );
      $auth_data = $quth->single("Error fetching Tokens");
      
      if( !$auth_data )
	return array(false, "Unauthorized Consumer");

      if( !$auth_data['token'] )
	return array(false, "Unauthorized Token");

      //
      // create Param object
      $oauth_params = new NuOAuthParameters( 
			    $consumer_key, 
			    $consumer_secret, 
			    $token, 
			    $auth_data['secret'],
			    $auth['oauth_signature_method'],
			    $auth['oauth_timestamp'],
			    $auth['oauth_nonce']);

      //
      // check signature
      if( $signature == NuOAuth::signature( $oauth_params, $resource, $method, $request, $param_filter ) )
	return $auth_data;

      return array(false, "Unauthorized");
    }

  }


?>
