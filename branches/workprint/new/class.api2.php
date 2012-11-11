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

	class API2 extends Service
	{
		private $map;
		private $method;
		private $op;
		private $format;
		private $output;

		protected $resource;
		protected $call;

		//
		// readonly GET or POST with no modify
		//
		protected $readOnly;

		function __construct( $fieldmap = false, $die=true, $output='json' )
		{

			parent::__construct();

			// field map
			$this->map = is_array($fieldmap) ? $fieldmap : array("op"=>"op","format"=>"format");
			$this->output = $output;

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
				// process call
				$this->process();

			}
			catch( Exception $e )
			{
			  self::invalidate($e->getMessage(), $e->getCode(), $die);
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
			  $this->output = $this->resource['output'];
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
			//
			// parse 
			$this->parse();

			//
			// include
			self::includer( strtolower($this->opFile()) );
		}


		//
		// rest method
		private function restMethod()
		{
		//$this->readOnly = true; // for stalling backups
			// POST over GET
			switch( true )
			{
				// Create, Update, Delete
				case count($_POST)>0:
					$r = 4;
					$this->resource = &$_POST;
					break;

				// Create, Update
				case count($_PUT)>0:
					$r = 3;
					$this->resource = &$_PUT;
					break;

				// Delete
				case count($_DELETE)>0:
					$r = 2;
					$this->resource = &$_DELETE;
					break;

				// Read
				case count($_GET)>0:
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
				throw new Exception( $emsg );
			}
		}

		//
		// operation
		private function operation()
		{
			return $this->mapField('op', "Missing API operation");
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
			if( $api_key = $this->resource['key'] )
			{
				// obtain CONTROL via ID
				$user_c = ID::userByAPI( $api_key );
			}
			else if( $_SESSION['logged'] == 1 )
			{
				// session holds USER_CONTROL
				$user_c = $_SESSION['USER_CONTROL'];
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
				if( strpos("-|". $this->overridePostAuthentication() . "|", "|". strtolower($this->op) . "|")>0 )
				{
					// override the post, stuff like registration|login|verification
					return true;
				}

				throw new Exception("Unauthorized access for post.{$this->op}");
			}

			//
			// BLOCK GETS BY AUTHORITY
			$getBlocks = $this->requireAuthentication();

			if( strpos("-|". $getBlocks . "|", "|". strtolower($this->op) . "|")>0 )
			{
				throw new Exception("Unauthorized access for get.{$this->op}");
			}

			return true;
		}

		//
		// post overrides
		// default no override
		protected function overridePostAuthentication()
		{
			return false;
		}

		//
		// access authentication
		// default no override
		protected function requireAuthentication()
		{
			return false;
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
				throw new Exception( $exc );

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

			throw new Exception( "Check JSON format" );
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
			$methop = $this->getMethod() .".". preg_replace('/[^\w]/','',$this->op) .".". $this->output;
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
			     '<response status="execption" code="'. $code .'" ms="'. $ms . '"'. 
			     ($msg ? "><message>{$msg}</message></response>" : ' />');

		      header('Content-type: text/xml');

		      if( $die )
			die( $xml );
		      else
			echo $xml;

		      break;

		    default:
			$json = '{"status":"error", "code":'. $code . ($msg? ', "message": "'. $msg .'"':'') . ',"ms":'. $ms .'}';
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
				throw new Exception("Invalid operation: {$methop}");
			}
		}
	}
?>
