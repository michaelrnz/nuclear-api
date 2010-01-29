<?php
    
    /*
        nuclear.framework
        altman,ryan,2008

        API Service class
        =====================================
            Head object for external interfacing
            extend for customization

    */

    require('abstract.service.php');

    class API extends Service
    {
        private $map;
        private $method;
        private $op;
        private $format;
        private $output_extension;
        private $_optext;

        protected $resource;
        protected $call;

        //
        // readonly GET or POST with no modify
        //
        protected $readOnly;

        function __construct( $fieldmap = false, $die=true )
        {

            parent::__construct();

            // field map
            $this->map = is_array($fieldmap) ? $fieldmap : array("op"=>"op","format"=>"format");

            //
            // build and process
            try
            {
                //
                // build
                $this->build();

                //
                // validate for access
                $this->validateAccess();

                //
                // validate for api call
                $this->validateCall();

                //
                // parse 
                $this->parse();

                //
                // process call
                $this->process();

            }
            catch( Exception $e )
            {
              $code = $e->getCode();
              self::invalidate($e->getMessage(), $code, $die);
            }

        }

        private function build()
        {
            //
            // judge method
            $this->method = $this->restMethod();

            // output
            if( isset($this->resource['output']) )
            {
              $GLOBALS['API_FORMAT'] = $this->resource['output'];
              $this->output_extension = $this->resource['output'];
            }
            else if( isset($_GET['output']) )
            {
              $GLOBALS['API_FORMAT'] = $_GET['output'];
              $this->output_extension = $this->resource['output'];
            }
            else
            {
              $GLOBALS['API_FORMAT'] = 'json';
              $this->output_extension = 'json';
            }

            //
            // assign op
            $this->op = $this->operation();
            $this->format = $this->mapField('format');
        }


        //
        // process the call
        private function process()
        {
            $this->includer( strtolower($this->opFile()) );
        }


        //
        // rest method
        private function restMethod()
        {
            // POST over GET
            switch( $_SERVER['REQUEST_METHOD'] )
            {
                // Create, Update, Delete
                case 'POST':
                    $r = 4;
                    break;

                // Create, Update
                case 'PUT':
                    $r = 3;
                    break;

                // Delete
                case 'DELETE':
                    $r = 2;
                    break;

                // Read
                case 'GET':
                    $r = 1;
                    $this->resource = &$_GET;
                    break;

                default:
                    throw new Exception("No REST method");
            }
            
            $this->resource = $_REQUEST;
            return $r;
        }

        //
        // field assign
        private function mapField($f, $emsg=false )
        {
            $map = $this->map[$f];
            $v = isset($this->resource[$map]) ? $this->resource[$map] : $_GET[$map];
            
            if( $v )
            {
                return $v;
            }

            if( $emsg )
            {
                throw new Exception( $emsg, 1 );
            }
        }


        //
        // operation
        private function operation()
        {
            return $this->mapField('op', "Missing API operation");
        }


        //
        // textual operation (only \w chars)
        protected function opText()
        {
            if( is_null($this->_optext) )
              $this->_optext = strtolower(preg_replace('/[^\w]/', "", $this->op));

            return $this->_optext;
        }


        //
        // get method name
        private function getMethod()
        {
            //$overrides = $this->postOverrides();

            //
            // test for get overrides
            //if( $this->readOnly && (!$overrides || preg_match("/^{$overrides}$/", $this->op)==0 ) )
            if( $this->readOnly )
                return 'get';

            //
            // return to method
            switch( $this->method )
            {
                case 4: return 'post';
                case 3: return 'put';
                case 2: return 'delete';
                case 1:
                default: return 'get';
            }
        }

        //
        // test for access
        private function validateAccess()
        {
            require_once('lib.id.php');

            $auth_data  = null;

            //
            // Check for Nuclear's native auth_token
            //
            if( $auth_key = $this->resource['auth_token'] )
            {
                $auth_type  = 'nuclear';
                $auth_data  = ID::userByAuthKey( strtolower($this->resource['auth_user']), $auth_key );
            }
            //
            // Check for OAuth
            //
            else if( isset($this->resource['oauth_version']) )
            {
                include('lib.nuoauthorize.php');

                // check the auth_type
                $oauth_type = $this->operationType( $this->opText() );
                $auth_type  = $oauth_type;

                switch( $oauth_type )
                {

                  // OAuth for requesting access token 
                  case 'oauth_access':
                    // not-implmeneted here
                    break;

                  //
                  // OAuth for fmp/access_token
                  //
                  case 'oauth_fmp':
                    $auth_resp = NuOAuthorize::federation( 
                           "http://{$GLOBALS['DOMAIN']}". real_request_uri(),
                           $this->getMethod(), 
                           $this->resource,
                           "format|op|output" );

                    // CHECK VALID
                    if( !$auth_resp[0] )
                      throw new Exception("Unauthorized oauth_fmp request", 2);
                    
                    $auth_data  = $auth_resp;
                    break;

                  //
                  // Publisher OAuth (fmp) 
                  //
                  case 'oauth_publisher':

                    $auth_resp = NuOAuthorize::publisher( 
                           "http://{$GLOBALS['DOMAIN']}". real_request_uri(),
                           $this->getMethod(), 
                           $this->resource,
                           "format|op|output" );

                    // CHECK VALID
                    if( !$auth_resp[0] )
                      throw new Exception("Unauthorized oauth_publisher request", 2);
                    
                    $auth_data  = array_splice( $auth_resp, 0, 10 );
                    $auth_data['id'] = $auth_data['publisher'];
                    break;

                  //
                  // Subscriber OAuth (fmp, uses publisher's keys) 
                  //
                  case 'oauth_subscriber':

                    $auth_resp = NuOAuthorize::subscriber( 
                           "http://{$GLOBALS['DOMAIN']}". real_request_uri(),
                           $this->getMethod(), 
                           $this->resource,
                           "format|op|output" );

                    // CHECK VALID
                    if( !$auth_resp[0] )
                      throw new Exception("Unauthorized oauth_subscriber request", 2);
                    
                    $auth_data  = $auth_resp;
                    $auth_data['id'] = $auth_data['subscriber'];
                    break;


                  default:
                    // USER OAUTH

                    break;
                }
            }
            //
            // SESSION-based AUTH
            //
            else if( $_SESSION['logged'] == 1 )
            {
                $auth_data  = $_SESSION['USER_CONTROL'];
                $auth_type  = 'cookie';
            }
            //
            // BASIC AUTH
            //
            else if( isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']) )
            {
              require_once('lib.keys.php');
              $auth_u       = $_SERVER['PHP_AUTH_USER'];
              $auth_p       = $_SERVER['PHP_AUTH_PW'];
              $password     = new NuclearPassword( $auth_u, $auth_p );
              $auth_data    = ID::userLoginByPassword( $auth_u, $password->token );
              $auth_type    = 'basic';
            }

            //
            // Set user control for local user
            //
            if( $auth_data )
            {
                $GLOBALS['USER_CONTROL'] = $auth_data;

                $auth_user  = new AuthorizedUser( $auth_data['id'], $auth_data['name'], get_global('DOMAIN') );
                $auth_user->setAuthorization( $auth_type, $auth_data );
                
                // do we return always true for authorized users?
                // user-level checking can be left to Call
                //
                return true;
            }


            // OVERRIDE POSTS BY AUTHORITY
            if( $this->method == 4 )
            {
                if( $this->overridePostAuthentication($this->opText()) )
                {
                    // override the post, stuff like registration|login|verification
                    return true;
                }

                throw new Exception("Unauthorized access for post.{$this->op}", 2);
            }


            // BLOCK GETS BY AUTHORITY, fall back on Basic
            if( isType( $this->requireAuthentication(), $this->opText() ) )
            {
                $this->basicAuthentication();
            }

            return true;
        }


        //
        // basic authentication
        // default realm to APPLICATION_NAME
        protected function basicAuthentication()
        {
            $app_name = $GLOBALS['APPLICATION_NAME'];
            header('WWW-Authenticate: Basic realm="'. ($app_name ? $app_name : 'Nuclear') .'"');
            header('HTTP/1.0 401 Unauthorized');
            echo "You must enter a valid login ID and password to access this resource\n";
            exit;
        }


        //
        // operation-auth-type
        // useful for Federated OAuth
        protected function operationType( $op="" )
        {
          if( isType("fmpaccess_token", $op) )
            return "oauth_fmp";

          if( isType("fmppublish|fmprepublish|fmpunpublish", $op) )
            return 'oauth_publisher';

          if( isType("fmpunsubscribe", $op) )
            return 'oauth_subscriber';

          if( $op == "oauthaccess_token" )
            return 'oauth_access';

          return 'oauth_user';
        }


        //
        // post overrides
        // default no override
        protected function overridePostAuthentication($op="")
        {
          if( isType("accountregister|accountverify_registration|sessioncreate|accountreset_password|accountverify_password|accountverify_destroy|fmpshare_token|fmppublisher_token", $op) ) return true;
        }


        //
        // access authentication
        // default no override
        protected function requireAuthentication()
        {
            return "|authtokens|tokens|fmppacketinbox|fmpaccess_token";
        }


        //
        // override for custom access
        protected function access()
        {
            return false;
        }


        //
        // test for call
        protected function validateCall()
        {
            $format = false;

            // get c call
            if( isset($this->resource['call']) )
            {
                $c = $this->resource['call'];
                $format = $this->format ? $this->format : 'json';
            }
            else
            {
                // test format
                $format = $this->format ? $this->format : 'rest';
            }
            

            // test for c or meth
            if( $c || $format=='rest' )
            {
                switch( $format )
                {
                    case 'xml':
                        $exc = "Invalid call format; xml restricted at this time, try json";
                        break;

                    case 'rest':
                        $this->call = self::getREST( $this->resource );
                        $this->call->ATIME = $GLOBALS['ATIME'];
                        break;

                    case 'json':
                        $this->call = self::getJSON( $c );
                        $this->call->ATIME = $GLOBALS['ATIME'];
                        break;

                    default:
                        $exc = "Invalid call format; json, xml, method only";
                        break;
                }
            }
            else
            {
                $exc = "Missing Call";
            }

            if( $exc )
                throw new Exception( $exc, 4 );

            // assign global to api
            $GLOBALS['APICALL'] = &$this->call;

        }

        /*
            format parsing methods 
        */

        //
        // JSON
        //
        protected static function &getJSON( $c )
        {
            $call = json_decode( (GET('base64') ? base64_decode($c) : stripslashes($c)) );

            if( $call )
            {
                return $call;
            }

            throw new Exception( "Check JSON format", 6 );
        }

        protected static function &getREST( $c )
        {
            $o = new Object();
            foreach( $c as $f=>$k )
            {
                // handle magic slash
                $o->$f = stripslashes( trim($k) );
            }
            return $o;
        }

        protected static function &getXML( $c )
        {
            return false;
        }

        /*
            end parsing methods
        */


        //
        // includeName
        protected function opFile()
        {
            $methop = $this->getMethod() .".". preg_replace('/[^\w]/','',$this->op);
            return $methop;
        }


        //
        // leaving to subclass
        // not necessary for action
        protected function parse()
        {
          if( isset($this->resource['user']) )
          {
            // globalize LocalUser
            ID::loadUserByName( str_replace("'", "", $this->resource['user']) );
          }
        }


        //
        // public to invalidate
        public static function invalidate($message=false, $code=-1, $die=false)
        {
            // basic error logging
            file_put_contents($GLOBALS['CACHE'] . "/api.log", time() . ":{$code}:{$message}\n", FILE_APPEND);
            
          $ms = number_format( (microtime(true) - $GLOBALS['ATIME']) * 1000, 3);

          if( $code==2 )
            header("HTTP/1.0 401 Unauthorized");

          switch( strtolower($GLOBALS['API_FORMAT']) )
          {
            case "xml":
              $xml = '<?xml version="1.0"?>' . "\n" . //<?
                     '<response status="error" '.
                     'code="'. $code .'" ms="'. $ms . '"' . 
                     ($message ? "><message>{$message}</message></response>" : " />");
                     
              header('Content-type: text/xml');

              if( $die )
                die( $xml );
              else
                echo $xml;

            break;

            default:

                $json = '{"status":"error", "code":'. $code . ($message ? ', "message": "'. $message.'"':'') . ',"ms":'. $ms .'}';
                if($die)
                    die( $json );
                else
                    echo $json;
                break;
          }
        }


        //
        // include API operation
        //
        public function includer( $methop )
        {
            $src_1 = "api.{$this->getMethod()}.{$this->opText()}.php";
            $src_2 = "api.{$this->getMethod()}.{$this->output_extension}.{$this->opText()}.php";

            switch( $this->output_extension )
            {
                case 'json': header("Content-type: text/javascript"); break;
                case 'xml': header("Content-type: application/xml"); break;
            }

            $api_class = (@include_once $src_2);

            if( !$api_class )
                $api_class = (@include_once $src_1);

            if( $api_class && strlen($api_class)>1 )
            {
                if( class_exists($api_class,false) )
                {
                    $co = new $api_class($GLOBALS['ATIME']);
                }
                else
                {
                    throw new Exception("Operation is not defined: {$methop}", 1);
                }
            }
            else
            {
                throw new Exception("Operation does not exist: {$methop}", 1);
            }
        }
    }
?>
