<?php


    /*
        nuclear.framework
        altman,ryan,2010

        Domains
        ================================
            Library for domain management,
            including endpoints and disc.

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

        public function mkdir( $dir, $mode=0775 )
        {
            if( is_dir( $dir ) ) return;
            mkdir( $dir, $mode, true );
        }

        public function uri( $uri, $limit=false )
        {
            if( !$urlsrc = @fopen( $uri , 'r' ) ){ return false; }

            if( is_numeric($limit) ) return fread( $urlsrc, $limit );

            $rf = "";
            do
            {
                $b = fread( $urlsrc, 1024 );
                $rf .= $b;
            } while ( strlen($b)>0 );

            return $rf;
        }
    }

?>