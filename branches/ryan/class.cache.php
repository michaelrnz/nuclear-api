<?php

    /*
        nuclear.framework
        altman,ryan,2008 (recod 2010)

        Cache
        ===================================================
            library for handling application-wide cache
    */

    require_once('interface.nuclear.php');
    require_once('class.io.php');

    class Cache implements iSingleton
    {
        private static $_instance;
        private $time;
        private $on;
        private $cache_dir;
        private $events;
        private $io;

        function __construct()
        {
            $this->events       = Events::getInstance();
            $this->io           = IO::getInstance();
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

        private function key( $resource )
        {
            $k0 = hash( "md5", $resource );
            $k1 = substr( $k0, 0, 2 );
            $k2 = substr( $k0, 2, 2 );
            return (object) array("path"=>"{$k1}/{$k2}/", "hash"=>"{$k0}");
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

            if( $this->on )
            {
                $expires    = $this->time - ($lifetime === false ? -100000 : $lifetime);
                $key_path   = $this->key( $resource );
                $key_file   = ( $prefix_dir ? $prefix_dir : $this->cache_dir ) . $key_path->path . $key_path->hash;

                if( file_exists( $key_file ) && filemtime( $key_file ) >= $expires )
                {
                    return true;
                }
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

            if( $this->on )
            {
                $key_path   = $this->key( $resource );
                $dir        = ($prefix_dir ? $prefix_dir : $this->cache_dir) . $key_path->path;
                $t          = "{$dir}{$key_path->hash}.". microtime(true);

                $this->io->mkdir( "{$dir}" );
                file_put_contents( $t, $data );
                rename( $t, "{$dir}{$key_path->hash}" );
            }
        }

        private static function _get( $resource, $lifetime=false, $prefix_dir=false )
        {

            // allow custom management of caching
            if( $this->events->isObserved( 'nu_cache_get' ) )
            {
                $source = (object) array("resource"=>$resource, "lifetime"=>$lifetime);
                $data = null;
                $data = $this->events->filter( 'nu_cache_get', $data, $source );
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

        public function getText( $resource, $lifetime=false, $prefix_dir=false )
        {
            return $this->_uncache( $resource, $lifetime, $prefix_dir );
        }

        public function setText( $resource, $text, $prefix_dir=false )
        {
            $this->_cache( $resource, $text, $prefix_dir );

            return $this;
        }

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