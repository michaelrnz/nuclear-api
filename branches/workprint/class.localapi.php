<?php
	/*
		nuclear.framework
		altman,ryan,2008

		LocalAPI
		==========================================
			allows calling on unamed framework,
			bypasses the API class instance
			and parsing
	*/

	require_once('class.eventlibrary.php');

	class LocalAPI extends EventLibrary
	{
		public static $_gv = "APICALL";

		function __call( $m, $args )
		{
			if( preg_match('/^(get|post|put|delete)(\w+)$/i', $m, $method ) )
			{
				$rest = strtolower($method[1]);
				$meth = $method[2];
				$meth[0] = strtolower($meth[0]);
				return self::execute( $rest, $meth, $args[0], $args[1] );
			}
			else
			{
				return null;
			}
		}

		//
		// sync call to global field
		// required for API
		//
		private static function globalize( &$call )
		{
			$GLOBALS[ self::$_gv ] = $call;
		}

		//
		// call the api, via include
		//
		private static function &execute( $rest, $method, &$call, $output="json" )
		{
			// name the src
			$src = "api." . $rest . "." . strtolower($method) . ".php";

			//
			// globalize the call
			self::globalize( $call );

			//
			// try include
			$apiclass = (include $src);

			if( class_exists( $apiclass, false ) )
			{
			  try
			  {
			    $o = new $apiclass( microtime(true), $output, false );

			    //
			    // get object of instance
			    //
			    return $o->response;
			  }
			  catch( Exception $e )
			  {
			    $o = new Object();
			    $o->valid = 0;
			    $o->message = $e->getMessage();

			    return $o;
			  }
			}
			else
			{
			  // should hopefully not occur
			  throw new Exception("Call to unknown API method: " . $rest . "." . $method);
			}
		}


		//
		// get/set global field index 
		// index of call
		//
		public static function setGlobal($s)
		{
			// valid strings only
			if( strlen($s)>1 )
				self::$_gv = $s;
		}

		public static function getGlobal($s)
		{
			return self::$_gv;
		}

	}

?>
