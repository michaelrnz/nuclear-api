<?php
    
    /*
        Entities - Nuclear
        2010 Winter
        altman,ryan
    */

    require_once('interface.nuclear.php');


    abstract class Entity
    {
        protected $type;
        protected $id;
        protected $name;
        protected $domain;

        function __construct( $type, $id, $name, $domain=null )
        {
            $this->type     = $type;
            $this->id       = $id;
            $this->name     = $name;
            $this->domain   = is_null($domain) ? get_global('DOMAIN') : $domain;
        }

        function __get( $f )
        {
            switch( $f )
            {
                case 'type':    return $this->type;
                case 'id':      return $this->id;
                case 'name':    return $this->name;
                case 'domain':  return $this->domain;
            }

            return null;
        }

        public function tag($section=false)
        {
            $sec    = $section ? $seciton . ":" : "";
            return "tag:{$this->domain},". date("Y") .":{$sec}{$this->type}:{$this->id}";
        }
    }



    abstract class UserObject extends Entity
    {
        function __construct( $id, $name, $domain=null )
        {
            parent::__construct( 'user', $id, $name, $domain );
        }
    }


    
    //
    // Local user is a singleton
    //
    class LocalUser extends UserObject implements iSingleton
    {
        private static $_instance;
        protected $email;

        function __construct( $id, $name, $email )
        {
            parent::__construct( $id, $name, get_global('DOMAIN') );

            $this->email = $email;

            if( is_null(self::$_instance) )
                self::$_instance = $this;
        }

        function __get( $f )
        {
            if( $f == 'email' ) return $this->email;

            return parent::__get( $f );
        }

        public static function getInstance()
        {
            return self::$_instance;
        }

        public static function setInstance( &$object )
        {
            if( is_a( $object, "LocalUser" ) )
                self::$_instance = $object;

            return self::$_instance;
        }
    }



    //
    // Authorized user is a singleton
    //
    class AuthorizedUser extends UserObject implements iSingleton
    {
        private static $_instance;
        protected $_properties;
        protected $auth_type;
        protected $auth_data;

        function __construct( $id, $name, $domain )
        {
            parent::__construct( $id, $name, $domain );

            if( is_null(self::$_instance) )
                self::$_instance = $this;
        }

        function __get( $f )
        {
            $v  = parent::__get( $f );

            if( is_null( $v ) && is_array( $this->auth_data ) && isset( $this->auth_data[$f] ) )
                return $this->auth_data[$f];

            return $v;
        }

        public static function getInstance()
        {
            return self::$_instance;
        }

        public static function setInstance( &$object )
        {
            if( is_a( $object, "AuthorizedUser" ) )
                self::$_instance = $object;

            return self::$_instance;
        }

        public function setAuthorization( $type, $data )
        {
            $this->auth_type    = $type;
            $this->auth_data    = $data;
        }

        public function isLocal()
        {
            return isType( 'nuclear|cookie|basic', $this->auth_type );
        }
    }


?>
