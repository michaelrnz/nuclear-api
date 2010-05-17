<?php

	/*
		nuclear.framework
		altman,ryan,2008 (recod 2010)

		Cache
		===================================================
			library for handling application-wide cache
	*/

    require_once('interface.nuclear.php');

    class IO implements iSingleton
    {
        private static $_instance;

        public static function getInstance()
        {
            if( is_null(self::$_instance) )
                self::$_instance = new IO();
            return self::$_instance;
        }
    }

    class Cache implements iSingleton
    {
        private static $_instance;
        private $time;
        private $on;
        private $cache_dir;
        private $events;

        function __construct()
        {
            $this->events       = Events::getInstance();
            $this->time         = time();
            $this->on           = true;
            $this->cache_dir    = get_global( 'CACHE' );
        }

        public static function getInstance()
        {
            if( is_null(self::$_instance) )
                self::$_instance = new Cache();
            return self::$_instance;
        }

        public function on()
        {
            $this->on = true;
            return $this;
        }

        public function off()
        {
            $this->on = false;
            return $this;
        }

        //
        // isCached: privately check if resource exists or has not expired
        //
        private function isCached( $resource, $lifetime=false, $prefix_dir=false )
        {

            // allow custom management of caching
            if( $this->events->isObserved( 'nu_cache_check' ) )
            {
                $source = (object) array("resource"=>$resource, "lifetime"=>$lifetime);
                $cached = false;
                return $this->events->filter( 'nu_cache_check', $cached, $source );
            }

            $expires = $this->time - ($lifetime === false ? -100000 : $lifetime);

            $full_resource = ( $prefix_dir ? $prefix_dir : $this->cache_dir ) . $resource;

            if( file_exists( $full_resource ) && filemtime( $full_resource ) >= $expires )
            {
                return true;
            }

            return false;
        }

        //
        // privately cache
        //
        private function _set( $resource, $data, $prefix_dir=false )
        {

            // allow custom management of caching
            if( $this->events->isObserved( 'nu_cache_set' ) )
            {
                $this->events->emit( 'nu_cache_set', $data, $resource );
                return;
            }

            // output directory
            $dir    = $prefix_dir ? $prefix_dir : $this->cache_dir;

            // ensure resource
            mk_cache_dir( "{$dir}" );

            // save temporary cache
            $t  = "{$dir}". hash("md5", $resource) .".". microtime(true);
            file_put_contents( $t, $data );

            // rename to directory
            rename( $t, "{$dir}{$resource}" );
        }

		//
		// private uncache, returns string data
		//
		private static function _get( $resource, $lifetime=false, $prefix_dir=false )
		{

            // allow custom management of caching
            if( $this->events->isObserved( 'nu_cache_get' ) )
            {
                $data = null;
                $data = $events->filter( 'nu_cache_get', $data, $id );
                return $data;
            }

            $dir = $prefix_dir ? $prefix_dir : $this->cache_dir;

			if( $this->isCached( $resource, $lifetime, $prefix_dir ) )
			{
				if( ($data = file_get_contents( $resource )) )
				{
					return $data;
				}
			}
			return false;
		}


        /*

            Public caching methods (text, document, object)

        */

        public function getObject( $resource, $lifetime=false, $prefix_dir=false )
        {
            if( $data = self::_uncache( $resource, $lifetime, $prefix_dir ) )
			{
				return unserialize( $data );
			}
			return null;
        }

        public function setObject( $resource, $object, $prefix_dir=false )
        {
            if( is_object( $object ) && strlen( $resource )>0 )
			{
			    $this->_cache( $resource, serialize( $object ), $prefix_dir );
			}

			return $this;
        }

        public function getText( $resource, $lifetime=false, $prefix_dir=false )
        {
            return $this->_uncache( $resource, $lifetime, $prefix_dir );
        }

        public function setText( $resource, $text, $prefix_dir=false )
        {
            return $this->_cache( $resource, $text, $prefix_dir );
        }

        public function getDocument( $resource, $prefix_dir=false )
        {
			if( $data = $this->_uncache( $resource, $lifetime, $prefix_dir ) )
			{
				$doc = new DOMDocument("1.0","utf-8");
				$doc->loadXML( $data );
				return $doc;
			}

			return null;
        }

        public function setDocument( $resource, $doc, $prefix_dir=false )
        {
            if( is_a($doc, 'DOMDocument') && strlen( $resource )>0 )
            {
                $this->_cache( $resource, $doc->saveXML() );
            }

            return $this;
        }
	}

?>