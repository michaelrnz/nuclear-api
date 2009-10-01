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
			  $GLOBALS['API_FORMAT'] = $this->resource['output'];

			else if( isset($_GET['output']) )
			  $GLOBALS['API_FORMAT'] = $_GET['output'];

			else
			  $GLOBALS['API_FORMAT'] = 'json';

			//
			// assign op
			$this->op = $this->operation();
			$this->format = $this->mapField('format');
		}


		//
		// process the call
		private function process()
		{
			self::includer( strtolower($this->opFile()) );
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
					$this->resource = &$_POST;
					break;

				// Create, Update
				case 'PUT':
					$r = 3;
					$this->resource = &$_PUT;
					break;

				// Delete
				case 'DELETE':
					$r = 2;
					$this->resource = &$_DELETE;
					break;

				// Read
				case 'GET':
					$r = 1;
					$this->resource = &$_GET;
					$this->readOnly = true;
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
		// text-only operation
		private function opText()
		{
			return strtolower(preg_replace('/[^\w]/', "", $this->op));
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

			//
			// obtain USER_CONTROL when possible
			if( $auth_key = $this->resource['auth_token'] )
			{
				$user_c = ID::userByAuthKey( strtolower($this->resource['auth_user']), $auth_key );
			}
			else if( isset($this->resource['oauth_version']) )
			{
				include('lib.nuoauthorize.php');

				// Attempt to validate oauth?
				//
				$op_auth = $this->operationType( $this->opText() );

				switch( $op_auth )
				{
				  case 'access_auth':
				    // ACCESS OAUTH

				    break;

				  case 'federation_auth':
				    // PUBLISHER OAUTH
				    $auth_resp = NuOAuthorize::federation( 
						   "http://{$GLOBALS['DOMAIN']}/api/fps/access_token.{$GLOBALS['API_FORMAT']}", 
						   $this->getMethod(), 
						   $this->resource,
						   "format|op|output" );

				    // CHECK VALID
				    if( !$auth_resp[0] )
				      throw new Exception("Unauthorized fps request", 2);
				    
				    $GLOBALS['FPS_REQUEST_AUTH'] = $auth_resp;
				    return true;

				    break;

				  case 'publisher_auth':
				    // PUBLISHER OAUTH
				    $auth_resp = NuOAuthorize::publisher( 
						   "http://{$GLOBALS['DOMAIN']}/api/fps/publish.{$GLOBALS['API_FORMAT']}", 
						   $this->getMethod(), 
						   $this->resource,
						   "format|op|output" );

				    // CHECK VALID
				    if( !$auth_resp[0] )
				      throw new Exception("Unauthorized fps request", 2);
				    
				    $GLOBALS['FPS_AUTHORIZED'] = array_splice( $auth_resp, 0, 10 );
				    return true;

				    break;

				  default:
				    // USER OAUTH

				    break;
				}
			}
			else if( $_SESSION['logged'] == 1 )
			{
				// session holds USER_CONTROL
				$user_c = $_SESSION['USER_CONTROL'];
			}
			else if( isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']) )
			{
			  // try Basic
			  require_once('lib.keys.php');
			  $user_c = ID::userLoginByPassword( $_SERVER['PHP_AUTH_USER'], Keys::password($_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW']) );
			}

			if( $user_c )
			{
				$GLOBALS['USER_CONTROL'] = $user_c;
				
				// do we return always true for authorized users?
				// user-level checking can be left to Call
				//
				return true;
			}

			//
			// on no access by auth
			//

			//
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

			//
			// BLOCK GETS BY AUTHORITY
			$getBlocks = $this->requireAuthentication();

			if( isType( $getBlocks, $this->opText() ) )
			{
				$this->basicAuthentication();
				//throw new Exception("Unauthorized access for get.{$this->op}", 2);
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
		  if( $op == "fpsaccess_token" )
		    return "federation_auth";

		  if( strpos($op, "fpspublish")===0 )
		    return 'publisher_auth';

		  if( $op == "oauthaccess_token" )
		    return 'access_auth';

		  return 'user_auth';
		}


		//
		// post overrides
		// default no override
		protected function overridePostAuthentication($op="")
		{
		  if( isType("register|verify|login|resetpassword|verifyresetpassword|nuclearaccountsdestroyverification|fpsshare_token|fpspublisher_token", $op) ) return true;
		}


		//
		// access authentication
		// default no override
		protected function requireAuthentication()
		{
			return "|authtokens|tokens";
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
		protected function parse() { }


		//
		// public to invalidate
		public static function invalidate($msg=false,$code=-1,$die=true)
		{
		  $ms = number_format( (microtime(true) - $GLOBALS['ATIME']) * 1000, 3);

		  switch( strtolower($GLOBALS['API_FORMAT']) )
		  {
		    case 'xml':
		      $xml = '<?xml version="1.0"?>'. "\n" .
			     '<response status="error" code="'. $code .'" ms="'. $ms . '"'. 
			     ($msg ? "><message>{$msg}</message></response>" : ' />');

		      header('Content-type: text/xml');

		      if( $die )
			die( $xml );
		      else
			echo $xml;

		      break;

		    default:
			$json = '{"valid":0, "msg": "N", "status":"error", "code":'. $code . ($msg? ', "message": "'. $msg .'"':'') . ',"ms":'. $ms .'}';
			if($die)
			{
			  die( $json );
			}
			else
			{
			  echo $json;
			}
		      break;
		  }
		}

		//
		// include file 
		public static function includer( $methop )
		{
			//
			// make name
			$src = 'api.'. $methop .'.php';

			//
			// passes control to meth.op file
			$apiclass = (include $src);

			//
			// check if returned class exists
			if( class_exists($apiclass,false) )
			{
				$co = new $apiclass($GLOBALS['ATIME']);
			}
			else
			{
				throw new Exception("Invalid operation: {$methop}", 1);
			}
		}
	}
?>
