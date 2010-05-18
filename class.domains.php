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
    require_once('class.io.php');

    class Domains implements iSingleton
    {
        private static $_instance;
        private $db;
        private $io;

        function __construct()
        {
            //$this->db   = Database::getInstance();
            $this->io   = IO::getInstance();
        }

        public static function getInstance()
        {
            if( is_null(self::$_instance) )
                self::$_instance = new Domains();
            return self::$_instance;
        }

        public function clean( $domain )
        {
            $domain = str_replace('http://', '', $domain);

            if( $i = strrpos($domain, '/') )
                $domain = substr($domain, 0, $i);

            return trim(str_replace("'","", $domain),"/ \r\n");
        }

        public function host_meta( $domain )
        {
            $resource   = "http://" . $this->clean($domain) . "/.well-known/host-meta";
            $xrd_data   = $this->io->uri( $resource );

            if( strlen($xrd_data)>0 )
            {
                require_once('class.domdocumentexceptor.php');

                $doc = new DOMDocumentExceptor('1.0', 'utf-8');
                $doc->loadXML( $xrd_data );
                return $doc;
            }

            return false;
        }

    }


?>