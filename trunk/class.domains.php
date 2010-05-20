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

    class Domain extends Object
    {
        public $id;
        public $name;
        protected $webfinger;
        protected $portal;

        function __get( $f )
        {
            if( isType( 'webfinger|portal', $k )  && !is_null( $this->$k ) )
            {
                return "http://" . $this->$k;
            }

            return null;
        }

        function __set( $k, $v )
        {
            if( isType( 'webfinger|portal', $k ) )
            {
                $this->$k   = str_replace('http://', '', $v);
            }
        }

        public function load()
        {
            $name   = safe_slash( $this->name );
            $data   = Database::getInstance()->single(
                        "select * from ". nu_db() . "nu_domain ".
                        "where name='{$name}' limit 1;",
                        "Error loading Domain" );

            if( $data )
                $this->merge( $data );

            return $this;
        }

        public function save()
        {
            $fields     = array();
            $values     = array();
            $dups       = array();

            if( is_null($this->name) )
                return $this;

            $fields[]   = 'name';
            $values[]   = "'". safe_slash( $this->name ) ."'";

            if( !is_null($this->id) )
            {
                $fields[]   = 'id';
                $values[]   = $this->id;
            }

            if( !is_null($this->webfinger) )
            {
                $fields[]   = 'webfinger';
                $values[]   = "'". safe_slash( $this->webfinger ) ."'";
                $dups[]     = "webfinger=values(webfinger)";
            }

            if( !is_null($this->portal) )
            {
                $fields[]   = 'portal';
                $values[]   = "'". safe_slash( $this->portal ) ."'";
                $dups[]     = "portal=values(portal)";
            }

            $sql =  "insert into ". nu_db() ."nu_domain ".
                    "(". implode(', ', $fields) . ") ".
                    "values (". implode(', ', $values) .") ";

            if( count($dups)>0 )
            {
                $sql .=  "on duplicate key update ". implode(', ', $dups);
            }

            Database::getInstance()->execute( $sql, "Error saving Domain" );

            return $this;
        }
    }

    class Domains implements iSingleton
    {
        private static $_instance;
        private $db;
        private $io;
        private $xrd;
        private $domain;

        function __construct()
        {
            $this->db   = Database::getInstance();
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

        public function host_meta( $domain=null )
        {
            if( is_null($domain) )
            {
                if( is_null($this->domain) )
                {
                    return false;
                }
                else
                {
                    $domain = $this->domain->name;
                }
            }

            $resource   = "http://" . $this->clean($domain) . "/.well-known/host-meta";
            $xrd_data   = $this->io->uri( $resource );

            if( strlen($xrd_data)>0 )
            {
                require_once('class.domdocumentexceptor.php');

                $this->xrd = new DOMDocumentExceptor('1.0', 'utf-8');
                $this->xrd->loadXML( $xrd_data );

                return $this->xrd;
            }

            return false;
        }

        public function template( $rel )
        {
            if( $this->xrd )
            {
                $links = $this->xrd->getElementsByTagName('Link');
                foreach( $links as $link )
                {
                    if( $link->getAttribute('rel') == $rel )
                    {
                        return $link->getAttribute('template');
                    }
                }
            }

            return false;
        }

        public function load( $domain, $create=false )
        {
            $domain_obj = new Domain( array('name'=> $this->clean($domain) ) );
            $domain_obj->load();

            if( !$domain_obj->id && $create )
                $domain_obj->save();

            $this->domain = $domain_obj;
            return $domain_obj;
        }
    }


?>