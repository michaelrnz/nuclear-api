<?php
    
    /**
     *
     * Consumer Manager (nuclear)
     *
    **/

    class ConsumerManager implements iSingleton
    {
        protected static $_instance;
        protected $db;

        function __construct()
        {
            $this->db = Database::getInstance();
        }

        
        /*
         * iSingleton
        */
        public static function getInstance()
        {
            if( is_null(self::$_instance) )
                self::$_instance = new ConsumerManager();

            return self::$_instance;
        }


        /*
         * generate token 
        */
        public function generateToken( $salt=0x00 )
        {
            $key    = to_base( from_hex( hash("sha256", rand() ."-". $salt ."-". time()) ) );
            $secret = to_base( from_hex( hash("sha256", rand() ."-". $salt ."-". $token) ) );

            return (object) array("key"=>$key, "secret"=>$secret);
        }


        /*
         * register an application
        */
        public function register( $owner, $name, $domain, $callback )
        {
            $values = array( 
                $owner, 
                "'". safe_slash($name) ."'", 
                "'". safe_slash($domain) ."'", 
                "'". safe_slash($callback) ."'"
            );

            $token = $this->generateToken( $name . $domain );

            $values[] = "'{$token->key}'";
            $values[] = "'{$token->secret}'";

            $q= "insert into ". nu_db() . "oauth_consumer ".
                "(owner, name, domain, callback, token, secret) ".
                "values (". implode(",", $values) .")";

            $id = $this->db->id( $q, "Consumer registration error" );
            $token->id = $id;

            return $token;
        }


        /*
         * update an application
        */
        public function update( $id, $owner, $name=false, $domain=false, $callback=false )
        {
            $update = array();


            if( strlen($name) )
                $update[] = "name='". safe_slash($name) ."'";

            if( strlen($domain) )
                $update[] = "domain='". safe_slash($domain) ."'";

            if( strlen($callback) )
                $update[] = "callback='". safe_slash($callback) ."'";

            $q= "update ". nu_db() . "oauth_consumer ".
                "set ". implode(", ", $update) ." ".
                "where id={$id} && owner={$owner} limit 1";

            $this->db->execute( $q, "Consumer update error" );

            return array_pop( $this->ownerClients( $owner, false, $id ) );
        }


        /*
         * refresh keys
        */
        public function refresh( $id, $salt )
        {
            $token = $this->generateToken( $salt );

            $q= "update ". nu_db() . "oauth_consumer ".
                "set token='{$token->key}', secret='{$token->secret}' ".
                "where id={$id} limit 1";

            $this->db->execute( $q, "Consumer refresh error" );

            return $token;
        }


        /*
         * list by owner
        */
        public function ownerClients( $owner, $secret=false, $id=false )
        {
            $q = new NuSelect(nu_db() . "oauth_consumer C");
            $q->field( array(
                "id",
                "name",
                "domain",
                "callback",
                "token",
                "created_at"
            ) );
            $q->where("C.owner={$owner}");

            if( $id>0 )
                $q->where("C.id={$id}");

            if( $secret===true )
            {
                $q->field( "secret" );
            }

            if( $q->select() )
            {
                $list = array();
                while($rec = $q->object())
                {
                    $list[] = $rec;
                }

                return $list;
            }

            return array();
        }

    }

?>
