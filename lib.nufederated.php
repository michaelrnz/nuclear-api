<?php

  require_once('lib.nuuser.php');
  require_once('lib.nuoauth.php');
  require_once('lib.nufiles.php');

  require_once('class.nuselect.php');




  //
  // Identification and Generation lib
  //

  class NuFederatedStatic
  {
    // Generate HASH token for Consumer
    //
    public function generateToken( $seed=false )
    {
      return hash("sha1", mt_rand() . microtime(true) . $seed);
    }

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
    // create Federated relation
    // publish->subscribe
    //
    public static function addFederatedAuth( $publisher, $subscriber, $token, $secret )
    {
      WrapMySQL::void(
        "insert into nu_federated_auth (publisher, subscriber, token, secret) ".
	"values ({$publisher}, {$subscriber}, '{$token}', '{$secret}');",
	"Error adding subscriber auth");
    }

    //
    // create Publisher relation
    // Publisher is a Federated User
    // Subscriber is local
    //
    public static function addPublisherAuth( $subscriber, $publisher, $token, $secret )
    {
        self::addFederatedAuth( $publisher, $subscriber, $token, $secret );
    }

    //
    // create Subscription relation
    // Subscriber is a Federated User
    // Publisher is local
    //
    public static function addSubscriberAuth( $subscriber, $publisher, $token, $secret )
    {
        self::addFederatedAuth( $publisher, $subscriber, $token, $secret );
    }
  }







  //
  // Select all subscribers from AUTH tuples
  //

  class NuFederatedSubscribers extends NuSelect
  {
    function __construct( $publisher )
    {
      parent::__construct( 'nu_federated_auth A' );
      $this->field( array("A.subscriber") );
      $this->where("A.publisher={$publisher}");
    }
  }


  //
  // Select subscriber AUTH tuples (remote)
  //

  class NuFederatedSubscriberKeys extends NuSelect
  {
    function __construct( $publisher )
    {
      if( !$publisher )
        throw new Exception("Invalid publisher (Subscribers)",5);

      parent::__construct("nu_federated_auth T");

      NuSelect::eventFilter( $this, "subscriber_query", array("fields"=>"premerge","joins"=>"postmerge","conditions"=>"postmerge") );

        $this->field(
                array(
                    'U.name', 'U.domain', 
                    'T.token', 'T.secret as token_secret', 
                    "'{$GLOBALS['DOMAIN']}' as consumer_key",
                    "'{$GLOBALS['DOMAIN']}' as consumer_secret"
                )
            );

      $this->join('NuclearUser U', 'U.id=T.subscriber');
      $this->where("T.publisher={$publisher}");
      $this->where("U.domain!='{$GLOBALS['DOMAIN']}'");
    }
  }

  class NuFederatedPublishing
  {

    //
    // queue a packet for dispatch
    //
    public static function queue( $local_id, $publisher, $global_id, $packet_data, $dmode='publish' )
    {
      $mode = isType('unpublish|republish|publish|notify', $dmode) ? $dmode : 'publish';
      
      $obj = new Object();
      $obj->publisher = $publisher;
      $obj->local_id    = $local_id;
      $obj->global_id   = $global_id;
      $obj->packet      = $packet_data;
      $obj->mode        = $dmode;
      
      require_once( 'class.scheduler.php' );
      
      return Scheduler::getInstance()->queue( "fmp_dispatch", $obj );
    }

    //
    // unqueue a packet for dispatch
    //
    public static function unqueue( $queue_id )
    {
        require_once( 'class.scheduler.php' );
        
        $obj    = Scheduler::getInstance()->unqueue( $queue_id, "fmp_dispatch" );
        
        return $obj;
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

      $subscribers  = new NuFederatedSubscriberKeys( $publisher );

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

      $fmp_params  = array(
		      "id"    => $packet_id,
		      "packet"=> $packet_data
		     );

      $subscribers  = new NuFederatedSubscriberKeys( $publisher );

      self::postSubscribers( '/api/fmp/'. $prefix .'publish.json', $subscribers, $fmp_params, $GLOBALS['CACHE'] . '/'. $prefix .'publishing.log' );
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

      $fmp_params  = array(
		      "id"    => $packet_id,
		     );

      $subscribers  = new NuFederatedSubscriberKeys( $publisher );

      self::postSubscribers( '/api/fmp/unpublish.json', $subscribers, $fmp_params, $GLOBALS['CACHE'] . '/unpublishing.log' );
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
	    file_put_contents( $log_file, time() . ": {$domain} {$access_resp}\n", FILE_APPEND );
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
        $user = trim(str_replace('http://', '', $user),"/ \r\n");
        if( $i = strrpos($user, '/') )
        $name = substr($user, $i+1);
                                        
        return str_replace(array("'","."), array("",""), $name);
    }

    //
    // user@domain
    public static function domain( $domain )
    {
        $domain = str_replace('http://', '', $domain);
        if( $i = strrpos($domain, '/') )
        $name = substr($domain, 0, $i);
                                        
        return trim(str_replace("'","", $name),"/ \r\n");
    }

    public static function publisherID( $local_user )
    {
      $name = NuUser::filterUser($local_user);
      return NuUser::userID( $name, $GLOBALS['DOMAIN'] );
    }

    public static function subscriber( $federated_user, $auto=false )
    {
      $user = NuUser::filterUser($federated_user);
      $domain = NuUser::filterDomain($federated_user);

      if( !$domain )
	throw new Exception("Federated user must have domain");

      return NuUser::userID( $user, $domain, true );
    }

    public static function id( $user, $domain, $auto=false )
    {
      $user   = str_replace("'","",$user);
      $domain = str_replace("'","",$domain);

      return NuUser::userID( $user, $domain, $auto );
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
