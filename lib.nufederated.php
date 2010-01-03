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
    // domain as token/secret
    //
    public static function domain( $domain )
    {
      $d = safe_slash($domain);
      return array(
        'domain'=>NuUser::domainID($domain), 
	'token'=>$d, 
	'secret'=>$d);
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

      $tq = new NuSelect('_void_');
      $tq = NuEvent::filter('subscriber_query', $q);

      $q = new NuSelect('nu_federated_subscriber_auth as T');
      $q->fields      = $tq->fields;
      $q->joins	      = $tq->joins;
      $q->conditions  = $tq->conditions;

      $q->field(
	    array(
	      'N.name', 'D.name as domain', 
	      'T.token', 'T.secret as token_secret', 
	      "'{$GLOBALS['DOMAIN']}' as consumer_key",
	      "'{$GLOBALS['DOMAIN']}' as consumer_secret"
	    )
	  );

      $q->join('nu_user as F', 'F.id=T.federated_user');
      $q->join('nu_name as N', 'N.id=F.name');
      $q->join('nu_domain as D', 'D.id=F.domain');

      $q->where("T.user={$publisher}");
      $q->where("D.name!='{$GLOBALS['DOMAIN']}'");

      return $q;
    }

    //
    // queue a packet for dispatch
    //
    public static function queue( $local_id, $publisher, $global_id, $packet_data, $dmode='publish' )
    {
      $mode = isType('unpublish|republish|publish|notify', $dmode) ? $dmode : 'publish';
      $data = safe_slash($packet_data);

      return WrapMySQL::id(
        "insert into nu_packet_queue (publisher, global_id, local_id, mode, data) ".
	"values ({$publisher}, {$global_id}, {$local_id}, '{$mode}', '{$data}');"
      );
    }

    //
    // unqueue a packet for dispatch
    //
    public static function unqueue( $queue_id )
    {
      $q = new NuSelect('nu_packet_queue Q');
      $q->field('*');
      $q->where("id={$queue_id}");

      $data = $q->single();

      if( $data )
       WrapMySQL::void("delete from nu_packet_queue where id={$queue_id} limit 1;");

      return $data;
    }

    //
    // dispatch to federated /notify method
    //
    public static function notify( $publisher, $packet_data )
    {
      if( !$publisher || !is_numeric($publisher) )
	throw new Exception("Invalid publisher", 5);

      if( !strlen($packet_data) )
	throw new Exception("Missing packet data", 4);

      $fps_params  = array(
		      "packet"=> $packet_data
		     );

      $subscribers  = self::subscribers( $publisher );

      self::postSubscribers( '/api/fmp/notify.json', $subscribers, $fps_params, $GLOBALS['CACHE'] . '/notify.log' );
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
  // Open Consumer
  // pub/sub of auth requests
  // domain as keys
  //
  class NuOpenConsumer extends NuFederatedConsumer
  {
    function __construct( $auth_domain )
    {
      $tokens = NuFederatedStatic::domain( $auth_domain );
      parent::__construct( $auth_domain, $tokens['token'], $tokens['secret'] );
      $this->domain_id = $tokens['domain'];
    }
  }


?>
