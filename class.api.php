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
        private $format;
        private $output_extension;
        private $_optext;
        private $auth_user;

        protected $op;
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

            //
            // assign op
            $operation = $this->operation();

            //
            // check if operation is remote
            if( $operation == 'nuclear' )
            {
                $operation = $this->runScheduler();
            }

            //
            // nu_api_operation filter
            $filter_operation = NuEvent::filter('nu_api_operation', $operation);

            //
            // test for operation, fallback on default
            if( strlen($filter_operation) )
                $this->op = $filter_operation;
            else
                $this->op = $operation;

            $this->format = $this->mapField('format');

            // output
            //
            if( array_key_exists('output', $_REQUEST) && strlen($_REQUEST['output'])>0 )
            {
              $GLOBALS['API_FORMAT'] = $_REQUEST['output'];
              $this->output_extension = $_REQUEST['output'];
              unset($_REQUEST['output']);
            }
            else
            {
              $GLOBALS['API_FORMAT'] = 'json';
              $this->output_extension = 'json';
            }

            //
            // assign resource via REQUEST
            $this->resource = $_REQUEST;

            //
            // filter resource
            foreach( $_COOKIE as $f=>$v )
            {
                unset($this->resource[$f]);
            }
        }


        //
        // process the call
        private function process()
        {
            $this->includer( strtolower($this->opFile()) );
        }

        //
        // run scheduler
        private function runScheduler()
        {
            $id = $_REQUEST['schedule_id'];

            if( !is_numeric( $id ) )
                throw new Exception("Missing schedule_id for nuclear.api", 4);

            // must return operation
            require_once('class.scheduler.php');

            $data = Scheduler::getInstance()->unqueue( $id, 'nuclear_api' );

            // TODO data checking
            if( is_null($data) || is_null($data->operation) )
                throw new Exception("Scheduler does not exist", 5);

            $operation          = $data->operation;
            $this->method       = $data->method;
            $this->auth_user    = $data->auth_user;

            foreach( $data->parameters as $p=>$v )
                $_REQUEST[$p] = $v;

            return $operation;
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
                    break;

                default:
                    throw new Exception("No REST method");
            }

            return $r;
        }

        //
        // field assign
        private function mapField($f, $emsg=false )
        {
            $map = $this->map[$f];
            $v = isset($_REQUEST[$map]) ? $_REQUEST[$map] : $_GET[$map];

            if( $v )
            {
                unset($_REQUEST[$map]);
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
            // Check for Nuclear remote auth_user (scheduled)
            //
            if( $this->auth_user > 0 )
            {
                $auth_type  = 'nuclear';
                $auth_data  = ID::userById( $this->auth_user );
            }
            //
            // Check for Nuclear's native auth_token
            //
            else if( $auth_key = $this->resource['auth_token'] )
            {
                $auth_type  = 'nuclear';
                $auth_data  = ID::userByAuthKey( strtolower($this->resource['auth_user']), $auth_key );
            }
            //
            // Check for OAuth
            //
            else if( isset($this->resource['oauth_version']) )
            {
                // check the auth_type
                switch( $this->opText() )
                {
                    case 'oauthrequest_token':
                        $oauth_type = "oauth_consumer";
                        break;

                    case 'oauthaccess_token':
                        $oauth_type = "oauth_request";
                        break;

                    default:
                        $oauth_type = "oauth_access";
                        break;
                }

                $auth_type  = $oauth_type;

                //
                // oauth_consumer/request are not user-based
                // should use GET (validate in method)
                //
                if( $oauth_type == "oauth_access" )
                {
                    require_once('lib.oauth.php');
                    //$auth_data = OAuthManager::getInstance()->authorizeUser();
                    $params = array();
                    foreach( $this->resource as $f=>$v )
                    {
                        $params[$f] = $v;
                    }

                    $req = new OAuthRequest('GET', "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], $params);

                    $test_server = new OAuthServer(new NuOAuthDataStore());
                    $hmac_method = new OAuthSignatureMethod_HMAC_SHA1();
                    $plaintext_method = new OAuthSignatureMethod_PLAINTEXT();
                    $test_server->add_signature_method($hmac_method);
                    $test_server->add_signature_method($plaintext_method);

                    $data = $test_server->verify_request($req);

                    if( is_array($data) && is_object($data[1]) )
                        $auth_data = $data[1]->auth_data;
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
            $meths = "|authtokens|tokens|fmppacketinbox|fmpaccess_token";
            return NuEvent::filter('nu_api_require_auth', $meths);
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
            /*
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
                        break;

                    case 'json':
                        $this->call = self::getJSON( $c );
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
            */

            $GLOBALS['APICALL'] = (object) $this->resource;
        }


        /**
         *
         * TODO remove/reorganize this
         *
        **/
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
                $o->$f = trim($k);
            }
            return $o;
        }
        protected static function &getXML( $c )
        {
            return false;
        }


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
