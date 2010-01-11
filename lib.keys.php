<?php

    /*
        nuclear.framework
        altman,ryan,2008

        Key Generation, API
        ====================================
    */


    //
    // generic key object
    //
    class KeyObject
    {
        protected $token;

        function __construct($value, $hash="sha1")
        {
            $this->token = hash( $hash, $value );
        }

        function __get($a)
        {
            switch( $a )
            {
                case 'user_token':
                    return str_replace( '/','_', base64_encode( pack("H*", $this->token) ) );

                case 'token':
                    return $this->token;
            }
        }

        function __toString()
        {
            if( is_null($this->token) ) return "";
            return $this->token;
        }

        public static function unpack( $token )
        {
            $data =  unpack("H*", base64_decode( str_replace( '_', '/', $token ) ) );

            if( isset($data[1]) )
                return $data[1];
            
            return null;
        }

        public function setUserToken( $token )
        {
            $this->token = self::unpack( $token );
            return $this;
        }
    }


    //
    // Nuclear's native API auth tokens
    // key format: hash-expires
    // stored internally as BINARY,
    // externally as base64 of binary
    //
    class NuclearAuthToken extends KeyObject
    {
        public static function secret( $identification, $expires_ts=false, $app_secret=false )
        {
            if( $app_secret )
            {
                $secret = $app_secret;
            }
            else if( isset($GLOBALS['APPLICATION_AUTH_SECRET']) )
            {
              $secret = $GLOBALS['APPLICATION_AUTH_SECRET'];
            }
            else
            {
              throw new Exception("Unable to securely generate AUTH; missing application secret",4);
            }

            return $user_id . '/' . $secret . '/' . ($expires_ts ? $expires_ts : (time() + 315360000));
        }

        public static function verify( $token, $identification, $app_secret=false )
        {
            // get expires
            $exp = intval( substr( $token, strrpos($token, '-')+1 ) );

            // check expiration
            if( $exp < time() )
              return false;

            // check for valid auth
            $auth = new NuclearAuthToken( $identification, $exp, $app_secret );
            if( $token === $auth->user_token )
              return true;

            return false;
        }

        function __construct( $user, $expiration, $secret=false )
        {
            parent::__construct( self::secret( $user, $expiration, $secret ) );
        }
    }


    //
    // password storage
    //
    class NuclearPassword extends KeyObject
    {
        function __construct( $user, $password )
        {
              parent::__construct( strtolower($user).$password );
        }
    }


    //
    // verification hashes for emailing
    //
    class NuclearVerification extends KeyObject
    {
        function __construct( $userpass )
        {
              // userpass + salt
              $values = array( $up, rand(), microtime(true) );

              // shuffle values, 9 variations
              shuffle( $values );

              // parent
              parent::__construct( $userpass, "sha256" );
        }
    }


    //
    // old library for using above classes
    //
    class Keys
    {
        public static function auth( $user_name, $timestamp=false, $app_secret=false )
        {
            $auth = new NuclearAuthToken( $user_name, $timestamp, $app_secret );
            return $auth->user_token;
        }

        public static function generate( $up )
        {
            $auth = new NuclearVerification( $up );
            return $auth->token;
        }

        public static function password( $u, $p )
        {
          $auth = new NuclearPassword( $u, $p );
          return $auth->user_token;
        }
    }

?>
