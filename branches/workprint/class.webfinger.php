<?php


    /*
        nuclear.framework
        altman,ryan,2010

        Webfinger
        ================================
            Library for webfinger

    */

    require_once('interface.nuclear.php');
    require_once('class.domains.php');

    class Webfinger implements iSingleton
    {
        private static $_instance;
        private $domains;
        private $io;
        private $xrd;

        function __construct()
        {
            $this->domains  = Domains::getInstance();
            $this->io       = IO::getInstance();
        }

        public static function getInstance()
        {
            if( is_null(self::$_instance) )
                self::$_instance = new Webfinger();
            return self::$_instance;
        }

        public function parse( $uri )
        {
            $uri = str_replace('acct:', '', $uri);

            if( strpos($uri, '@')>0 )
            {
                $acct = explode('@', $uri);
                return array("user"=>$acct[0], "domain"=>$acct[1]);
            }
            else if( strpos($uri, '/')>0 )
            {
                $acct = explode('/', str_replace('http://', '', $uri));
                return array("user"=>$acct[1], "domain"=>$acct[0]);
            }

            return false;
        }

        public function link( $rel, $attribute='template' )
        {
            if( $this->xrd )
            {
                $links = $this->xrd->getElementsByTagName('Link');
                foreach( $links as $link )
                {
                    if( $link->getAttribute('rel') == $rel )
                    {
                        return $link->getAttribute($attribute);
                    }
                }
            }

            return false;
        }

        public function acct( $domain, $acct, $template=false )
        {
            if( !$template )
            {
                $acct       = urlencode( 'acct:' . str_replace('acct:', '', $acct) );
                $this->domains->host_meta( $domain );
                $template   = $this->domains->template( 'lrdd' );
            }

            if( strlen($template)>0 )
            {
                $resource   = str_replace( '{uri}', $acct, $template );
                $xrd_data   = $this->io->uri( $resource );

                if( strlen($xrd_data)>0 )
                {
                    require_once('class.domdocumentexceptor.php');

                    $this->xrd = new DOMDocumentExceptor('1.0', 'utf-8');
                    $this->xrd->loadXML( $xrd_data );
                    return $this->xrd;
                }
            }

            return false;
        }
    }

    class WebfingerException extends Exception
    {
        function __construct( $msg="Unspecified" )
        {
            parent::__construct( "Webfinger Exception: {$msg}" );
        }
    }

?>