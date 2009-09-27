<?php

  require_once('lib.nuoauth.php');

  //
  // Identification and Generation lib
  //
  class NuFederatedStatic
  {
    public static function domain( $domain )
    {
      $domain_t = "nu_federated_domain";

      $d = safe_slash($domain);
      $id = WrapMySQL::single(
             "select id from {$domain_t} where domain='{$d}' limit 1;",
	     "Error selecting domain id");
      
      if( !$id )
      {
	WrapMySQL::void(
	     "insert into {$domain_t} (domain) values ('{$d}');",
	     "Error inserting domain");
	
	$id = mysql_insert_id();
      }

      return $id;
    }

    //
    // Generate HASH token for Consumer
    //
    public function generateToken( $seed=false )
    {
      return hash("sha1", $this->domain . mt_rand() . microtime(true) . $seed);
    }

    //
    // Select domain ID,TOKEN,SECRET for local Consumer
    //
    public static function subscriberDomain( $domain )
    {
      return self::consumerDomain($domain, 'subscriber');
    }

    //
    // Select domain ID,TOKEN,SECRET for remote Consumer
    //
    public static function publisherDomain( $domain )
    {
      return self::consumerDomain($domain, 'publisher');
    }

    //
    // Select domain ID,TOKEN,SECRET for Consumer
    // Expects nu_federated schema
    //
    private static function consumerDomain( $domain, $type )
    {
      $d = safe_slash($domain);

      $q = "select K.* from nu_federated_domain as D ".
           "right join nu_federated_{$type}_domain as K on K.domain=D.id".
	   "where D.domain='{$d}' limit 1";
      
      return WrapMySQL::single($q, "Error selecting Consumer tokens");
    }

  }

  //
  // External for local-remote exchanges
  // 1) requesting publisher keys
  // 2) sharing request tokens - NI
  // 3) requesting access tokens - NI
  //
  class NuFederatedExternal
  {
    //
    // IS FLAG REQUESTED
    //
    private static function isFlagged( $domain )
    {
      $flag = $GLOBALS['CACHE'] .'/'. $domain;
      if( !file_exists( $flag ) )
	return false;
      return @file_get_contents( $flag );
    }

    //
    // FLAG REQUESTED
    //
    private static function flagRequested( $domain, $nonce )
    {
      $flag = $GLOBALS['CACHE'] .'/'. $domain;
      if( file_exists( $flag ) )
	return false;
      else
	return @file_put_contents( $flag, $nonce );
    }

    //
    // UNFLAG REQUESTED
    //
    private static function unflagRequested( $domain )
    {
      $flag = $GLOBALS['CACHE'] .'/'. $domain;
      @unlink( $flag );
    }

    //
    // REQUEST KEYS
    //
    public static function requestPublisherKeys( $domain )
    {
      $nonce = mt_rand();
      if( self::flagRequested( $domain, $nonce ) )
      {
	$uri = "http://{$domain}/api/fps/publisher_token?nonce={$nonce}&domain=" . urlencode($GLOBALS['APPLICATION_DOMAIN']);
	NuFiles::curl( $uri, "get" );
      }
      else // another request is in progress
      {
      }
    }

    //
    // ACCEPT KEYS
    // used by /api/fps/publisher_token
    //
    public static function acceptPublisherKeys( $domain, $nonce, $token, $secret )
    {
      if( $nonce == self::isFlagged( $domain ) )
      {
	// get domain identification
	$domain_id = NuFederatedID::domain( $domain );

	// clean tokens
	$tok_v = safe_slash($token);
	$sec_v = safe_slash($secret);

	// insert keys
	WrapMySQL::void(
	 "insert into nu_federated_publisher_domain (domain, token, secret) ".
	 "values ({$domain_id}, '{$tok_v}', '{$sec_v}');",
	 "Error adding publisher keys");

	// unflag
	self::unflagRequested( $domain );

	return true;
      }

      return false;
    }

    //
    // PROVIDE KEYS
    // used by /api/fps/publisher_token
    // NOTE: keys are generally random sha1
    //
    public static function providePublisherKeys( $domain, $nonce )
    {

      // generate new key
      $token = NuFederatedStatic::generateToken( $domain );
      $secret= NuFederatedStatic::generateToken( $nonce );

      // get domain identification
      $domain_id = NuFederatedID::domain( $domain );

      // clean tokens
      $tok_v = safe_slash($token);
      $sec_v = safe_slash($secret);

      // insert keys
      WrapMySQL::void(
	"insert into nu_federated_subscriber_domain (domain, token, secret) ".
	 "values ({$domain_id}, '{$tok_v}', '{$sec_v}');",
	 "Error adding subscriber keys");

      $uri = "http://{$domain}/api/fps/publisher_token.json";

      $post_data = array(
	"domain"=> urlencode($GLOBALS['APPLICATION_DOMAIN']),
	"nonce"=>  urlencode($nonce),
	"consumer_key"=> $token,
	"consumer_secret"=>$secret
      );

      return NuFiles::curl( $uri, "post", $post_data );

    }

  }

  class NuFederatedUsers
  {
    //
    // user@domain
    public static function user( $user )
    {
      if( ($i = strpos($user, '@')) )
	$name = substr($user,0,$i-1);
      else
	$name = $user;
      
      return str_replace("'","",$name);
    }

    //
    // user@domain
    public static function domain( $domain )
    {
      if( ($i = strpos($domain, '@')) )
	$name = substr($domain,$i);
      else
	$name = $user;
      
      return str_replace("'","",$name);
    }

    public static function publisherID( $local_user )
    {
      $name = self::user($local_user);
      $r = WrapMySQL::single(
	    "select id from nuclear_username where name='{$name}';",
	    "Error fetching publisherID");
      return $r;
    }

    public static function subscriber( $federated_user, $auto=false )
    {
      $user = self::user($federated_user);
      $domain = self::user($federated_user);

      if( !$domain )
	throw new Exception("Federated user must have domain");

      $r = WrapMySQL::single(
	    "select U.id from nu_federated_user as U ".
	    "right_join nu_federated_domain as D on D.id=U.domain ".
	    "where U.name='{$name}' && D.domain='{$domain}';",
	    "Error fetching subscriberID");
      
      if( !$r && $auto )
      {
	$id = self::addSubscriber( $user, $domain );
      }

      return $r;
    }

    public static function addSubscriber( $user, $domain )
    {
      $domain_id = NuFederatedID::domain( $domain );

      WrapMySQL::void(
	"insert into nu_federated_user (domain, name) ".
	"values ({$domain_id}, '{$user}');",
	"Error adding subscriber");

      return mysql_insert_id();
    }

  }

  //
  // FederatedConsumer
  //
  abstract class NuFederatedConsumer extends NuOAuthConsumer
  {
    protected $domain;

    function __construct( $auth_domain, $token=false, $secret=false )
    {
      parent::__construct( $token, $secret );
      $this->domain = $auth_domain;
    }

    function __get( $f )
    {
      switch( $f )
      {
	case 'domain':
	  return $this->$f;
	
	case 'domainID':
	  return NuFederatedID::domain( $this->domain );
	
	default:
	  return parent::__get($f);
      }
    }

  }

  //
  // Publisher 
  // source of auth requests
  //
  class NuFederatedPublisher extends NuFederatedConsumer
  {
    function __construct( $auth_domain, $request=false )
    {
      // get publisher's tokens on local node
      if( !($tokens = NuFederatedStatic::publisherDomain( $auth_domain )) && $request )
      {
	// no local tokens, request
	NuFederatedExternal::requestPublisherKeys( $auth_domain );
	$tokens = NuFederatedStatic::publisherDomain( $auth_domain );
      }

      if( !$tokens )
	throw new Exception("Invalid NuFederatedPublisher missing token/secret");

      parent::__construct( $auth_domain, $tokens['token'], $tokens['secret'] );
    }
  }


  //
  // Subscriber 
  // destination of auth requests
  //
  class NuFederatedSubscriber extends NuFederatedConsumer
  {
    function __construct( $auth_domain )
    {
      $tokens = NuFederatedStatic::subscriberDomain( $auth_domain );
      parent::__construct( $auth_domain, $tokens['token'], $tokens['secret'] );
    }
  }


?>
