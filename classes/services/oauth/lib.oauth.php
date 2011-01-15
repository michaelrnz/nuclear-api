<?php
    
    /**
     *
     * Nuclear OAuth module
     *
    **/

    require_once('/home/nuclear/OAuth.php');

    class NuOAuthToken extends OAuthToken
    {
        public $auth_data;

        function __construct($key, $secret, $data=null)
        {
            parent::__construct($key, $secret);
            $this->auth_data = $data;
        }
    }

    class NuOAuthDataStore extends OAuthDataStore
    {
        protected $db;

        function __construct()
        {
            $this->db = Database::getInstance();
        }

        function lookup_consumer( $consumer_key )
        {
            // use db to lookup nuclear data
            $q= "select token, secret, callback ".
                "from ". nu_db() ."oauth_consumer C ".
                "where token='{$consumer_key}' limit 1";

            $r= $this->db->single( $q, "OAuth Datastore Error" );

            if( $r )
                return new OAuthConsumer( $r['token'], $r['secret'], $r['callback'] );

            return null;
        }

        function lookup_token($consumer, $token_type, $token)
        {
            require_once('class.nuselect.php');

            $q= new NuSelect(nu_db() . "oauth_consumer C");
            $q->field( array("T.token", "T.secret") );

            if( $token_type == "access" )
            {
                $q->field("U.*");
                $q->join(nu_db() . "oauth_access T", "T.consumer=C.id");
                $q->join("NuclearAuthorized U", "U.id=T.user");
            }
            else
            {
                $q->join(nu_db() . "oauth_request T", "T.consumer=C.id");
            }

            $q->where("C.token='{$consumer->key}'");
            $q->where("T.token='{$token}'");
            $q->page(1,1,1,1,1);

            if( $q->select() )
            {
                $data = $q->hash();
                $oauth_token = $data['token'];
                $oauth_secret= $data['secret'];
                
                if( $token_type=='access' )
                {
                    unset($data['token']);
                    unset($data['secret']);
                }
                else
                {
                    $data = null;
                }

                return new NuOAuthToken( $oauth_token, $oauth_secret, $data );
            }
            
            // $r= single( $q, "OAuth Datastore Error" );

            return null;
        }

        function lookup_nonce($consumer, $token, $nonce, $timestamp)
        {
            $q= "select N.id ".
                "from ". nu_db() . "oauth_consumer C ".
                "join ". nu_db() . "oauth_nonce N ".
                "on N.consumer=C.id ".
                "where N.nonce='{$nonce}' ".
                "limit 1";

            $r= $this->db->single( $q, "OAuth Datastore Error" );

            return $r ? true : false;
        }

        function nu_token( $salt )
        {
            $token  = to_base( from_hex( hash("sha1", rand() ."-". $salt ."-". time()) ) );
            $secret = to_base( from_hex( hash("sha1", rand() ."-". $salt ."-". $token) ) );

            return new OAuthToken( $token, $secret );
        }

        function new_request_token($consumer, $callback = null)
        {
            $callback_value = $callback ? "'{$callback}'" : "C.callback";

            $token  = $this->nu_token( $consumer->secret );
            
            $q= "insert into ". nu_db() ."oauth_request (".
                    "select null, C.id as consumer, '{$token->key}', '{$token->secret}', {$callback_value}, null ".
                    "from ". nu_db() ."oauth_consumer C ".
                    "where C.token='{$consumer->key}' ".
                    "limit 1)";

            $this->db->execute( $q, "OAuth Datastore Error" );

            return $token;
        }

        function new_access_token($token, $consumer, $verifier = null)
        {
            // check authorized for user + join on access for previous tokens
            //
            $q= "select U.id as auth_id, U.user as auth_user, ".
                "R.id as req_id, R.consumer, A.user as access_user, ".
                "A.token as oauth_token, A.secret as oauth_token_secret ".
                "from oauth_authorized U ".
                "join oauth_request R on R.id=U.request ".
                "left join oauth_access A on A.consumer=R.consumer && A.user=U.user ".
                "where R.token='{$token->key}' limit 1";

            $r= $this->db->single( $q, "OAuth Datastore Error" );

            // nothing found
            //
            if( !$r )
                return null;

            // cleanup data
            //
            $auth_id        = $r['auth_id'];
            $req_id         = $r['req_id'];

            // found existing user-consumer tokens
            //
            if( $r['oauth_token'] && $r['oauth_token_secret'] )
            {
                $oauth_token = new OAuthToken( $r['oauth_token'], $r['oauth_token_secret'] );
            }
            else
            {
                // query data
                //
                $consumer_id    = $r['consumer'];
                $oauth_user     = $r['auth_user'];

                // create new
                //
                $oauth_token    = $this->nu_token( $consumer->secret ."-". $oauth_user );
                
                $q= "insert into ". nu_db() ."oauth_access (user, consumer, token, secret) ".
                    "values ({$oauth_user}, {$consumer_id}, '{$oauth_token->key}', '{$oauth_token->secret}')";

                $this->db->execute( $q, "OAuth Datastore Error" );
            }

            // clean request
            //
            $this->db->execute(
                "delete from oauth_request where id={$req_id} limit 1",
                "OAuth Datastore Error"
            );

            // clean authorized
            //
            $this->db->execute(
                "delete from oauth_authorized where id={$auth_id} limit 1",
                "OAuth Datastore Error"
            );

            return $oauth_token;
        }
    }

    require_once('interface.nuclear.php');

    class OAuthManager implements iSingleton
    {
        protected static $_instance;
        protected $db;

        protected function __construct()
        {
            $this->db = Database::getInstance();
        }

        public static function getInstance()
        {
            if( is_null(self::$_instance) )
                self::$_instance = new OAuthManager();

            return self::$_instance;
        }

        function authorize( $user_id, $token )
        {
            $q= "insert into ". nu_db() ."oauth_authorized (".
                    "select null, {$user_id} as user, R.id as request, null ".
                    "from ". nu_db() ."oauth_request R ".
                    "where R.token='{$token}' limit 1)";

            return $this->db->affected( $q, "OAuth Manager Error" );
        }

        function authorizeUser()
        {
        }
    }

?>
