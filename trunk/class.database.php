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
			if( !$r && $errmsg )
                throw new Exception($errmsg .": ". mysql_error(), $errcode);
		}

		public function &execute( $str, $errmsg, $errcode=7 )
		{
			if( !($r = mysql_query($str)) )
                throw new Exception($errmsg .": ". mysql_error(), $errcode);
			return $r;
		}
        
        public function id( $str, $errmsg=false, $errcode=7 )
        {
            $r = mysql_query($str);
            if( !$r && $errmsg )
                throw new Exception($errmsg .": ". mysql_error(), $errcode);
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
    
?>