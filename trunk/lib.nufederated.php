<?php

  require_once('lib.nuuser.php');
  require_once('lib.nuoauth.php');
  require_once('lib.nufiles.php');




  //
  // Identification and Generation lib
  //
  class NuFederatedStatic
  {
    //
    // Generate HASH token for Consumer
    //
    public function generateToken( $seed=false )
    {
      return hash("sha1", mt_rand() . microtime(true) . $seed);
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

      $q = "select K.* from nu_domain as D ".
           "right join nu_federated_{$type}_domain as K on K.domain=D.id ".
	   "where D.name='{$d}' limit 1;";
      
      $r = WrapMySQL::single($q, "Error selecting Consumer tokens");
      return $r;
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
	$uri = "http://{$domain}/api/fps/publisher_token.json?nonce={$nonce}&domain=" . urlencode($GLOBALS['DOMAIN']);

	$json_txt = NuFiles::curl( $uri, "get" );
	$json = json_decode( $json_txt );

	// remove flag
	if( !is_object( $json ) || is_null($json->status) || $json->status == "error" )
	{
	  self::unflagRequested( $domain );
	  return -1;
	}

	file_put_contents($GLOBALS['CACHE'] .'/'. 'publisher.request.log', "$domain: $json_txt\n", FILE_APPEND);

	return 1;
      }
      else // another request is in progress
      {
	return 0;
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
	$domain_id = NuUser::domainID( $domain );

	// clean tokens
	$tok_v = safe_slash($token);
	$sec_v = safe_slash($secret);

	try
	{
	  // insert keys
	  WrapMySQL::void(
	    "insert into nu_federated_publisher_domain (domain, token, secret) ".
	    "values ({$domain_id}, '{$tok_v}', '{$sec_v}');",
	    "Error adding publisher keys");
	}
	catch( Exception $e )
	{
	  file_put_contents($GLOBALS['CACHE'] .'/'. 'publisher.accept.log', "$domain: {$e->getMessage()}\n", FILE_APPEND);
	}

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
      $domain_id = NuUser::domainID( $domain );

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
	"domain"=> urlencode($GLOBALS['DOMAIN']),
	"nonce"=>  urlencode($nonce),
	"consumer_key"=> $token,
	"consumer_secret"=>$secret
      );

      $json_txt = NuFiles::curl( $uri, "post", $post_data );

      // log request
      file_put_contents($GLOBALS['CACHE'] .'/'. 'subscriber.requested.log', "$domain: $json_txt\n", FILE_APPEND);

      $json = json_decode( $json_txt );

      // remove domain if 
      if( is_null( $json ) || $json->status == "error" )
      {
	WrapMySQL::void(
	  "delete from nu_federated_subscriber_domain where domain={$domain_id} limit 1;"
	);

	return false;
      }

      return true;
    }

  }











  class NuFederatedIdentity
  {
    //
    // create Publisher relation
    // Publisher is a Federated User
    // Subscriber is local
    //
    public static function addPublisherAuth( $subscriber, $publisher, $token, $secret )
    {
      WrapMySQL::void(
        "insert into nu_federated_publisher_auth (user, federated_user, token, secret) ".
	"values ($subscriber, $publisher, '{$token}', '{$secret}');",
	"Error adding publisher auth");
    }

    //
    // create Subscription relation
    // Subscriber is a Federated User
    // Publisher is local
    //
    public static function addSubscriberAuth( $subscriber, $publisher, $token, $secret )
    {
      WrapMySQL::void(
        "insert into nu_federated_subscriber_auth (user, federated_user, token, secret) ".
	"values ($publisher, $subscriber, '{$token}', '{$secret}');",
	"Error adding subscriber auth");
    }
  }







  class NuFederatedRelation
  {
    //
    // query the list of subscribers from db
    //
    public static function subscribers( $publisher )
    {
      if( !$publisher ) return null;

      return WrapMySQL::q(
	      "select T.user ".
	      "from nu_federated_publisher_auth as T ".
	      "where T.federated_user={$publisher};",
	      "Error querying subscribers");
    }

    //
    // query the list of subscribers from db
    //
    public static function federatedSubscribers( $publisher )
    {
      if( !$publisher ) return null;

      return WrapMySQL::q(
	      "select F.name, D.domain, T.token, T.secret as token_secret, C.token as consumer_key, C.secret as consumer_secret ".
	      "from nu_federated_subscriber_auth as T ".
	      "left join nu_federated_user as F on F.id=T.federated_user ".
	      "left join nu_federated_subscriber_domain as C on C.domain=F.domain ".
	      "left join nu_federated_domain as D on D.id=F.domain ".
	      "where T.user={$publisher};",
	      "Error querying subscribers");
    }
  }









  class NuFederatedPacket
  {
    private static function namespace( $prefix, $uri=false, $auto=true )
    {
      $ns_t = "nu_federated_namespace";

      $v = safe_slash($prefix);
      $u = safe_slash($uri);
      $id = WrapMySQL::single(
             "select id from {$ns_t} where prefix='{$v}' limit 1;",
	     "Error selecting namespace id");
      
      if( $id )
	return $id[0];
      
      if( $auto )
      {
	WrapMySQL::void(
	     "insert into {$ns_t} (prefix,uri) values ('{$v}', '{$u}');",
	     "Error inserting namespace");
	
	$id = mysql_insert_id();
	return $id;
      }

      return false;
    }

    public static function insertHash( $publisher, $hash )
    {
      try
      {
	WrapMySQL::void(
	"insert into nu_federated_packet_hash (federated_user, hash) ".
	"values ($publisher, '{$hash}');",
	"Packet hash error", 15);
      }
      catch( Exception $e )
      {
	if( $e->getCode() == 15 )
	  return -1;
	else
	  throw $e;
      }

      return 1;
    }


    public static function insertIndex( $federated_id, $federated_user )
    {
      return WrapMySQL::id(
		"insert into nu_federated_packet_index (federated_id, federated_user) ".
		"values ({$federated_id}, {$federated_user});",
		"Error inserting packet");
    }


    public static function publish(  $federated_user, $packet_id )
    {
      return WrapMySQL::affected(
	       "insert ignore into nu_federated_inbox (".
	       "select user, {$packet_id} as packet from nu_federated_publisher_auth where federated_user={$federated_user}".
	       ");",
	       "Error dispatching to inbox");
    }

    public static function unpublish( $packet_id )
    {
      if( !$packet_id ) return;

      WrapMySQL::void(
	"delete from nu_federated_packet_index where id={$packet_id} limit 1;",
	"Error unpublishing from index"
      );

      WrapMySQL::void(
	"delete from nu_federated_inbox where packet={$packet_id};",
	"Error unpublishing from inbox"
      );

      self::flushNamespace( $packet_id );
    }

    public static function localId( $federated_id, $federated_user )
    {
      $id = WrapMySQL::single(
	      "select id from nu_federated_packet_index ".
	      "where federated_id={$federated_id} && federated_user={$federated_user} limit 1;",
	      "Error getting local packet ID");

      return $id ? $id[0] : false;
    }

    public static function linkNamespace( $packet_id, $namespace )
    {
      $ns_id	  = array();

      foreach( $namespace as $prefix=>$uri )
	$ns_id[]  = self::namespace( trim($prefix), trim($uri) );

      WrapMySQL::void(
	"insert ignore into nu_federated_packet_namespace (packet,namespace) ".
	"values ({$packet_id}," . implode("), ({$packet_id},", $ns_id) . ");",
	"Error linking packet-namespace");
    }

    public static function flushNamespace( $packet_id )
    {
      WrapMySQL::void(
        "delete from nu_federated_packet_namespace where packet={$packet_id};",
	"Error flushing packet-namespace");
    }

  }





















  class NuFederatedUsers
  {
    //
    // user@domain
    public static function user( $user )
    {
      if( ($i = strpos($user, '@')) )
	$name = substr($user,0,$i);
      else
	$name = $user;
      
      return str_replace("'","",$name);
    }

    //
    // user@domain
    public static function domain( $domain )
    {
      if( ($i = strpos($domain, '@')) )
	$name = substr($domain,$i+1);
      else
	$name = $user;
      
      return str_replace("'","",$name);
    }

    public static function publisherID( $local_user )
    {
      $name = NuUser::filterUser($local_user);
      $r = WrapMySQL::single(
	    "select id from nuclear_username where name='{$name}';",
	    "Error fetching publisherID");
      return $r ? $r[0] : false;
    }

    public static function subscriber( $federated_user, $auto=false )
    {
      $user = NuUser::filterUser($federated_user);
      $domain = NuUser::filterDomain($federated_user);

      if( !$domain )
	throw new Exception("Federated user must have domain");

      $r = WrapMySQL::single(
	    "select U.id from nu_federated_user as U ".
	    "right join nu_federated_domain as D on D.id=U.domain ".
	    "where U.name='{$user}' && D.domain='{$domain}';",
	    "Error fetching subscriberID");
      
      if( !$r && $auto )
      {
	$id = self::addFederatedUser( $user, $domain );
	return $id;
      }

      return $r ? $r[0] : false;
    }

    public static function id( $user, $domain, $domain_id, $auto=false )
    {
      $r = WrapMySQL::single(
	    "select U.id from nu_federated_user as U ".
	    "where U.name='{$user}' && U.domain={$domain_id};",
	    "Error fetching federated user");

      if( !$r && $auto )
      {
	$id = self::addFederatedUser( $user, $domain, $domain_id );
	return $id;
      }

      return $r ? $r[0] : false;
    }

    public static function addFederatedUser( $user, $domain, $domain_id=false )
    {
      if( !$domain_id )
	$domain_id = NuUser::domainID( $domain );

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
	  if( !$this->domain_id )
	    $this->domain_id = NuUser::domainID( $this->domain );
	  return $this->domain_id;
	
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
	if( NuFederatedExternal::requestPublisherKeys( $auth_domain ) < 1 )
	  throw new Exception("Unable to request Publisher keys");

	$tokens = NuFederatedStatic::publisherDomain( $auth_domain );
      }

      if( !$tokens )
	throw new Exception("Invalid NuFederatedPublisher missing token/secret");

      parent::__construct( $auth_domain, $tokens['token'], $tokens['secret'] );

      $this->domain_id = $tokens['domain'];
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
