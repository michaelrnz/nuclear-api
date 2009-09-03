<?php

	/*
		nuclear.framework
		altman,ryan,2008

		Cache
		===================================================
			library for handling application-wide cache
	*/

	require_once('lib.files.php');
	require_once('class.eventlibrary.php');

	class Cache extends EventLibrary
	{

		//
		// control object
		private static $control=null;
		private static $on=true;

		//
		// getter/setter control
		public static function setControl(&$c)
		{
			self::$control = $c;
		}
		public static function getControl(&$c)
		{
			return self::$control;
		}

		//
		// switch cache on/off
		public static function on()
		{
			self::$on = true;
		}
		public static function off()
		{
			self::$on = false;
		}

		/*

		CACHE GENERAL METHODS

		*/

		//
		// check if cached with valid time 
		private static function isCached( $f, $e )
		{
			// caclulate expire
			$exp = $e===false? $e : time()-$e;

			//
			// fire check cache
			$o = new Object();
			$o->file = $f;
			$o->expires = $exp;

			// call the method
			self::fire('CheckCache', $o);

			// false expire means no expire
			if( file_exists( $f ) && ($exp==false || filemtime( $f )>$exp) )
			{
				return true;
			}

			//
			// fire on failed cached

			// call the method
			self::fire('CheckCacheFail', $o);

			return false;
		}

		//
		// cache/uncache private
		// TODO: and exceptions
		//
		private static function _cache( &$d, $dir, $f )
		{
			//
			// temporary t
			$t = self::$control->tmp . $f . microtime(true);

			//
			// empty into t
			file_put_contents( $t, $d );

			//
			// rename to cache
			rename( $t, $dir . $f );

			return true;
		}

		//
		// private uncache, returns string data
		// not as useful as more specific uncache
		//
		private static function &_uncache( $f, $ex=false )
		{
			if( self::isCached( $f, $ex ) )
			{
				if( ($fd = Files::read( $f )) )
				{
					return $fd;
				}
			}
			return false;
		}

		
		/*
		
		PUBLIC GENERAL CACHING METHODS

		*/

		public static function in( &$o, $n )
		{
			$eo = new Object();
			$eo->file = $n;

			if( is_object( $o ) )
			{
				if( is_a($o,'DOMDocument') )
				{
					self::iDOMDocument( $o, $n );
				}
				else
				{
					self::iObject( $o, $n );
				}
			}
			else if( !is_null($o) && $o!==false )
			{
				self::iString( $o, $n );
			}
			else
			{
				return false;
			}

			//
			// fire oncached
			self::fire('Cache', $o);
		}

		public static function &out( $n, $t=false, $ex=false )
		{
			switch( $t )
			{
				case 'json':
				case 'object':
					return self::oObject( $n, $ex );

				case 'dom':
				case 'xml': 
					return self::oDOMDocument($n, $ex);

				case 'xhtml':  
					return self::oString( $n, $ex );

				case 'string':
					return self::oString( $n, $ex );

				default: break;
			}

			return null;
		}


		/*

		SPECIFIC TYPE CACHING/UNCACHING

		*/

		private static function iObject( &$o, $n )
		{
			if( is_object( $o ) && strlen( $n )>0 )
			{
				// make tmp and cache names
				$tf = self::$control->tmp . $n . time();
				$cf = self::$control->objects . $n;

				// output to tmp, rename to cache
				file_put_contents( $tf, serialize($o) );
				rename( $tf, $cf );

				// valid cache
				return true;
			}

			// didn't cache
			return false;
		}

		private static function &oObject( $n, $exp )
		{
			if( $od = self::_uncache( self::$control->objects . $n, $exp ) )
			{
				$r = unserialize( $od );
				return $r;
			}
			return false;
		}

		private static function iString( &$str, $n )
		{
			return self::_cache( $str, self::$control->xhtml, $n );
		}

		private static function &oString( $n, $ex, $passthru=false, $content_type=false )
		{
			$f = self::$control->xhtml . $n;

			if( self::isCached( $f, $ex ) )
			{
				if( $passthru )
				{
					Files::passthrough( $f, $content_type );
				}
				else if( ($fd = Files::read( $f )) )
				{
					return $fd;
				}
			}

			return false;
		}

		private static function iDOMDocument( &$doc, $n )
		{
			if( is_object($doc) && is_a( $doc, 'DOMDocument' ) )
			{
				$tf = self::$control->tmp . $n . time();
				$cf = self::$control->xml . $n;

				//
				// attempt saving doc
				if( $doc->save( $tf ) )
				{
					// rename from tmp
					rename( $tf, $cf );

					return true;
				}
			}

			return false;
		}

		private static function &oDOMDocument( $n, $ex )
		{
			$f = self::$control->xml . $n;

			//
			// check cache and init document
			if( self::isCached( $f, $ex ) )
			{
				$doc = new DOMDocument("1.0","utf-8");
				$doc->load( $f );
				return $doc;
			}

			return false;
		}


		/*
		
		CACHE EXPIRATION METHODS

		*/

		public static function expire($fn)
		{
			if( file_exists( $fn ) )
			{
				rename( $fn, $fn .'.expired.'.time() );
			}
		}

		public static function massExpire( $exps )
		{
			if( is_array( $exps ) )
			{
				$nexps = preg_replace( '/(.+)/', "-regex \"$1\"", $exps );
				$reg = implode( ' -o ', $nexps );
			}
			else
			{
				$reg = '-regex "' . $exps . '"';
			}
			$path = $GLOBALS['CACHE'] . 'xml-cache/';
			$exp = time();
			$find = "find $path -type f \( $reg \) -exec mv ". '{} {}.expired.' ."$exp \;";
			exec( $find );
		}

	}

?>
