<?php
  
  /*
      Nuclear OAuth Module
      =======================================
      altman.ryan, 2009 Fall

      general basis for OAuth compatibility

      Notes:
        This module is the basis for federated
	social-packet services between nodes.
  */

  class NuOAuth
  {
    // Generate nonce
    //
    public static function nonce( $ts )
    {
      return hash("md5", $ts . mt_rand());
    }

    // Generate signature based on a set of OAuth parameters
    //
    public function signature( $oauth_params, $resource, $rest_method, &$request_params=null, $param_filter='', $encode=true )
    {
      // open data
      $data = strtoupper($rest_method) . "&" . urlencode($resource) . "&";

      // build request params
      $sig_params = $oauth_params->signature_parameters();
      //print_r($sig_params);

      // append params, no overwrite
      if( $request_params != null && is_array($request_params) )
      {
	foreach( $request_params as $k=>$v )
	{
	  $uk = urlencode($k);
	  if( !isset($sig_params[$uk]) && 
	      !isType($param_filter, $k) && 
	      !isType('oauth_signature|oauth_token_secret', $k))
	    $sig_params[ $uk ] = $encode ? urlencode($v) : $v;
	}
      }

      // sort
      ksort( $sig_params, SORT_STRING );
      //print_r($sig_params);

      // key=value
      array_walk( $sig_params, create_function('&$i,$k', '$i = "{$k}={$i}";') );

      // delimit &
      $data .= implode( "&", $sig_params );

      //echo '<br />' . $data . '<br />';

      // sign with params
      return $oauth_params->sign( $data );
    }
  }


  /*

    NuOAuthParameters
  
    Specific container for oauth values
    capable of signing data based on params

  */ 
  class NuOAuthParameters
  {

    private $consumer_key;
    private $nonce;
    private $signature_method;
    private $timestamp;
    private $token;
    private $version;

    private $token_secret;
    private $consumer_secret;

    function __construct( $c_k, $c_s, $a_t=false, $a_s=false, $sig="HMAC-SHA1", $ts=false, $nonce=false, $version="1.0" )
    {
      $this->consumer_key = $c_k;
      $this->consumer_secret = $c_s;
      $this->token = $a_t;
      $this->token_secret = $a_s;
      $this->signature_method = strtoupper($sig);
      $this->timestamp = is_numeric($ts) ? $ts : time();
      $this->nonce = $nonce ? $nonce : NuOAuth::nonce( $this->timestamp );
      $this->version = $version;
    }

    private function _parameters( $keys, $encode=true )
    {
      $r = array();

      foreach( $keys as $p )
      {
	if( ($v = $this->$p) )
	  $r[ 'oauth_' . $p ] = $encode ? urlencode($v) : $v;
      }

      return $r;
    }

    private function _key()
    {
      $k = $this->consumer_secret . "&";
      
      if( $this->token_secret )
      {
	$k .= $this->token_secret;
      }

      return $k;
    }

    public function request_parameters()
    {
      return $this->_parameters( array("consumer_key", "nonce", "signature_method", "timestamp", "token", "version"), false );
    }

    public function signature_parameters()
    {
      return $this->_parameters( array("consumer_key", "nonce", "signature_method", "timestamp", "token", "version") );
    }

    public function sign( $data )
    {
      switch( $this->signature_method )
      {
	case 'HMAC-MD5':
	  $algo = "md5"; break;

	case 'HMAC-SHA1':
	default:
	  $algo = "sha1"; break;
      }

      return base64_encode( hash_hmac( $algo, urlencode( $data ), $this->_key(), true ) );
    }
  }


  /*

    Consumer
    basic oauth consumer data-class

  */
  class NuOAuthConsumer
  {
    protected $token;
    protected $secret;

    function __construct( $token=false, $secret=false )
    {
      if( $token ) $this->token = $token;
      if( $secret ) $this->secret = $secret;
    }

    function __get( $f )
    {
      switch( $f )
      {
	case 'token':
	case 'secret':
	  return $this->$f;
	
	default:
	  return null;
      }
    }

  }


  /*

    OAuth Request
    General Request from Nuclear to Another OAuth-enabled service

  */
  class NuOAuthRequest
  {
    public static function text( $oauth_params, $resource, $rest_method, $request_params=null )
    {
      $method_params = $oauth_params->request_parameters();
      $method_params['oauth_signature'] = NuOAuth::signature( $oauth_params, $resource, $rest_method, $request_params );

      // append params, no overwrite
      if( $request_params != null && is_array($request_params) )
      {
	foreach( $request_params as $k=>$v )
	{
	  if( !isset($method_params[$k]) )
	    $method_params[ $k ] = $v;
	}
      }

      // make call using RES, METH, PARAMS
      $data = NuFiles::curl( $resource, $rest_method, $method_params );

      return $data;
    }
  }


  //
  // TEST CODE
  //

  /*
  $c_k = md5("me1");
  $c_s = md5("me2");
  $a_t = sha1("user1" . md5("me2"));
  $a_s = sha1("userfjdkflj");

  $resource = "http://melative.com/api/fps/access_token";
  $method = "post";

  $extra_params = array('nuf_publisher'=>'user1@melative.com', 'nuf_subscriber'=>'user2@kuuki.org');

  $op = new NuOAuthParameters( $c_k, $c_s, $a_t, $a_s );
  $sig = NuOAuth::signature( $op, $resource, $method, $extra_params );

  echo "$sig\n";

  print_r($op->request_parameters());
  print_r($op->signature_parameters());
  

  echo "\n\nTEST FOR KNOWN PARAMS\n\n";

  $nonce = "3ed3690a7af43e85355264bb33cdca4e";
  $timestamp = 1253918226;

  $op = new NuOAuthParameters( $c_k, $c_s, $a_t, $a_s, "hmac-sha1", $timestamp, $nonce );
  $sig = NuOAuth::signature( $op, $resource, $method, $extra_params );

  echo "\nCURR: $sig , PREV: 3G0Y8MwdQRTEB5TkKf00lEin+T0=\n";
  /**/

?>
