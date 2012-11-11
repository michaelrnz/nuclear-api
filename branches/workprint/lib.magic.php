<?php

    /*
        nuclear.framework
        altman,ryan,2010

        Magic
        ================================
            Magic for users, keys and ssl.
            Requires
            * Database
            * SSL
            * Preference

    */

    class UserKeys
    {
        private $db;
        private $user_id;
        private $public_key;
        private $private_key;

        function __construct( $user_id )
        {
            $this->db       = Database::getInstance();
            $this->user_id  = intval($user_id);
        }

        function __get( $k )
        {
            if( $k == 'public_key' )
                return $this->public_key;

            return null;
        }

        function __set( $k, $v )
        {
            switch( $k )
            {
                case 'private_key':
                case 'public_key':
                    if( preg_match( '/^[0-9a-f]+$/i', $v ) )
                    {
                        $this->$k = $v;
                    }
                    else
                    {
                        $hexkey   = unpack("H*", base64_decode( $v ));
                        $this->$k = $hexkey[1];
                    }
                 break;
            }
        }

        public function load()
        {
            if( is_null($this->public_key) )
            {
                $q  = new NuSelect( get_global('NU_DB') . 'nuclear_userkey' );
                $q->where( "id={$this->user_id}" );
                $q->field( array(
                        'HEX(public_key) as public_key',
                        'HEX(private_key) as private_key'
                ));

                $key = $q->single();

                if( $key && $key['public_key'] )
                    $this->public_key = $key['public_key'];

                if( $key && $key['private_key'] )
                    $this->private_key = $key['private_key'];
            }
        }

        public function save()
        {
            $field  = array();
            $values = array();
            $dups   = array();

            if( !is_null($this->public_key) )
            {
                $fields[] = 'public_key';
                $values[] = "UNHEX('{$this->public_key}')";
                $dups[]   = 'public_key=values(public_key)';
            }

            if( !is_null($this->private_key) )
            {
                $fields[] = 'private_key';
                $values[] = "UNHEX('{$this->private_key}')";
                $dups[]   = 'private_key=values(private_key)';
            }

            if( count($values)>0 )
            {
                $fields[] = 'id';
                $values[] = $this->user_id;

                $sql  = "insert into ". get_global('NU_DB') ."nuclear_userkey ".
                        "(" . implode( ', ', $fields ) . ") ".
                        "values (". implode( ', ', $values ) . ") ".
                        "on duplicate key update ". implode( ', ', $dups ) . ";";

                $this->db->execute( $sql );
            }
        }
    }

    class MagicKeys implements iSingleton
    {
        private static $_instance;
        private $ssl;

        function __construct()
        {
            require_once('class.ssl.php');
            $this->ssl = SSL::getInstance();
        }

        public static function getInstance()
        {
            if( is_null(self::$_instance) )
                self::$_instance = new MagicKeys();
            return self::$_instance;
        }

        public function href( $hex_key )
        {
            $key    = pack("H*", $hex_key);
            $n      = substr( $key, 0, -5 );
            $e      = substr( $key, -3 );
            $href   = "data:application/magic-public-key,RSA." .
                      base64url_encode( $n ) ."." .
                      base64url_encode( $e );

            return $href;
        }

        public function hex( $href )
        {
            $data   = explode('.', $href);
            $e      = array_pop($data);
            $n      = array_pop($data);
            $key    = base64url_decode( $n ) . "\x02" . "\x03" . base64url_decode( $e );
            $hex    = unpack("H*", $key);

            return strtoupper($hex[1]);
        }
    }

    class NuUserMagic
    {
        private $user_id;
        private $keys;
        private $local;
        private $ssl;
        private $db;
        private $prefs;
        private $public_key;

        function __construct( $user_id, $local=true )
        {
            require_once('class.nupreference.php');
            require_once('class.ssl.php');

            $this->user_id  = $user_id;
            $this->keys     = new UserKeys( $user_id );
            $this->local    = $local;
            $this->ssl      = SSL::getInstance();
            $this->db       = Database::getInstance();
            $this->prefs    = NuPreference::getInstance();
        }

        public function loadPreference()
        {
            return $this->prefs->getBlob(
                $this->user_id, 'public_key' );
        }

        public function savePreference()
        {
            $this->prefs->setBlob(
                $this->user_id, 'public_key', $this->keys->public_key );
        }

        public function load()
        {
            $this->keys->load();

            if( $this->keys->public_key )
                return $this;

            if( $this->local )
            {
                // TODO allow global preference for keys
                $keys = $this->ssl->createKeys(
                    array('digest_alg'=>'sha256', 'private_key_bits'=>768) );

                $this->keys->public_key     = $keys['public'];
                $this->keys->private_key    = $keys['private'];
                $this->keys->save();
                $this->savePreference();

                return $this;
            }
            else
            {
                throw new WebfingerException("Cannot load public keys for remote user");
            }
        }

        public function href()
        {
            return MagicKeys::getInstance()->href( $this->keys->public_key );
        }
    }

?>