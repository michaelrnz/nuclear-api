<?php

    /*
        nuclear
        altman,ryan,2010

        Database Singleton
        =====================================
            basic query methods, allows
            exception throwing
    */

    require_once( 'interface.nuclear.php' );

    class Database implements iSingleton
    {
        private static $_instance;
        private $connection;

        public static function getInstance()
        {
            if( is_null( self::$_instance ) )
                self::$_instance = new Database();

            return self::$_instance;
        }

        public function addConnection( $c )
        {
            $this->connection = $c;
        }

        //
        // Query Methods
        //

        public function void( $str, $errmsg=false, $errcode=7 )
        {
            $r = mysql_query($str);
            if( !$r && $errmsg ) {
		$msg = $errmsg .": ". mysql_error().", $errcode";
		file_put_contents('/tmp/mysql_exceptions.log', date('Y-m-d H:i:s').",{$msg}\n", FILE_APPEND);
                throw new Exception($msg);
	    }
        }

        public function &execute( $str, $errmsg=false, $errcode=7 )
        {
            if( !($r = mysql_query($str)) ) {
		$msg = $errmsg .": ". mysql_error().", $errcode";
		file_put_contents('/tmp/mysql_exceptions.log', date('Y-m-d H:i:s').",{$msg}\n", FILE_APPEND);
                throw new Exception($msg);
	    }
            return $r;
        }

        public function id( $str, $errmsg=false, $errcode=7 )
        {
            $r = mysql_query($str);
            if( !$r && $errmsg ) {
		$msg = $errmsg .": ". mysql_error().", $errcode";
		file_put_contents('/tmp/mysql_exceptions.log', date('Y-m-d H:i:s').",{$msg}\n", FILE_APPEND);
                throw new Exception($msg);
  	   }
            return mysql_insert_id();
        }

        public function affected( $str, $errmsg=false )
        {
            $this->void( $str, $errmsg );
            return mysql_affected_rows();
        }

        public function single( $str, $errmsg )
        {
            $r = $this->execute( $str, $errmsg );
            if( $r )
                return mysql_fetch_array( $r );
            return null;
        }
    }

