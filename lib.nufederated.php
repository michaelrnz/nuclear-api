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

  }

  class NuFederatedPublishing
  {

    //
    // query the list of subscribers from db
    //
    private static function subscribers( $publisher )
    {
      if( !$publisher ) return null;

      $tq = new NuQuery('_void_');
      $tq = NuEvent::filter('subscriber_query', $q);

      $q = new NuQuery('nu_federated_subscriber_auth as T');
      $q->fields      = $tq->fields;
      $q->joins	      = $tq->joins;
      $q->conditions  = $tq->conditions;

      $q->field(
	    array(
	      'N.name', 'D.name as domain', 
	      'T.token', 'T.secret as token_secret', 
	      'C.token as consumer_key', 'C.secret as consumer_secret'
	    )
	  );

      $q->join('nu_user as F', 'F.id=T.federated_user');
      $q->join('nu_name as N', 'N.id=F.name');
      $q->join('nu_federated_subscriber_domain as C', 'C.domain=F.domain');
      $q->join('nu_domain as D', 'D.id=F.domain');

      $q->where("T.user={$publisher}");
      $q->where("D.name!='{$GLOBALS['DOMAIN']}'");

      return $q;
    }

    //
    // queue a packet for dispatch
    //
    public static function queue( $packet_id, $publisher, $packet_global, $packet_data, $dmode='publish' )
    {
      $mode = isType('unpublish|republish|publish', $dmode) ? $dmode : 'publish';
      $data = safe_slash($packet_data);

      WrapMySQL::void(
        "insert into nu_packet_queue (id, publisher, global_id, mode, data) ".
	"values ({$packet_id}, {$publisher}, {$packet_global}, '{$mode}', '{$data}');"
      );
    }

    //
    // unqueue a packet for dispatch
    //
    public static function unqueue( $packet_id )
    {
      $q = new NuQuery('nu_packet_queue Q');
      $q->field('*');
      $q->where("id={$packet_id}");

      $data = $q->single();

      if( $data )
       WrapMySQL::void("delete from nu_packet_queue where id={$packet_id} limit 1;");

      return $data;
    }

    //
    // dispatch to federated /publish method
    //
    public static function dispatch( $publisher, $packet_id, $packet_data, $republish=false )
    {
      if( $republish ) $prefix = 're';

      if( !$publisher || !is_numeric($publisher) )
	throw new Exception("Invalid publisher", 5);

      if( !$packet_id || !is_numeric($packet_id) )
	throw new Exception("Invalid packet id", 5);

      if( !strlen($packet_data) )
	throw new Exception("Missing packet data", 4);

      $fps_params  = array(
		      "id"    => $packet_id,
		      "packet"=> $packet_data
		     );

      $subscribers  = self::subscribers( $publisher );

      self::postSubscribers( '/api/fps/'. $prefix .'publish.json', $subscribers, $fps_params, $GLOBALS['CACHE'] . '/'. $prefix .'publishing.log' );
    }

    //
    // undispatch to federated /publish method
    //
    public static function undispatch( $publisher, $packet_id )
    {

      if( !$publisher || !is_numeric($publisher) )
	throw new Exception("Invalid publisher", 5);

      if( !$packet_id || !is_numeric($packet_id) )
	throw new Exception("Invalid packet id", 5);

      $fps_params  = array(
		      "id"    => $packet_id,
		     );

      $subscribers  = self::subscribers( $publisher );

      self::postSubscribers( '/api/fps/unpublish.json', $subscribers, $fps_params, $GLOBALS['CACHE'] . '/unpublishing.log' );
    }

    private static function postSubscribers( $api_method, &$subscribers, &$params, $log_file=false )
    {
      $domain = false;

      if( $subscribers->select() )
      {
        while( $subscriber = $subscribers->hash() )
	{
	  // publish once per domain
	  if( $domain == $subscriber['domain'] ) continue;
	  
	  // assign domain
	  $domain = $subscriber['domain'];

	  // create OAuth
	  $oauth_params = new NuOAuthParameters(
				$subscriber['consumer_key'],
				$subscriber['consumer_secret'],
				$subscriber['token'],
				$subscriber['token_secret']);
	  
	  // publish to domain
	  $access_resp  = NuOAuthRequest::text( $oauth_params, "http://{$domain}{$api_method}", "POST", $params );

	  // log resp
	  if( $log_file )
	    file_put_contents( $log_file, time() . ": {$access_resp}\n", FILE_APPEND );
	}
      }
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
      return NuUser::userID( $name, $GLOBALS['DOMAIN'], false );
    }

    public static function subscriber( $federated_user, $auto=false )
    {
      $user = NuUser::filterUser($federated_user);
      $domain = NuUser::filterDomain($federated_user);

      if( !$domain )
	throw new Exception("Federated user must have domain");

      return NuUser::userID( $user, $domain, false, true );
    }

    public static function id( $user, $domain, $domain_id, $auto=false )
    {
      $user   = str_replace("'","",$user);
      $domain = str_replace("'","",$domain);

      return NuUser::userID( $user, $domain, $domain_id, $auto );
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
