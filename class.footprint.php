<?php

    /*
        nuclear
        altman,ryan,2010

        Footprint Singleton
		=====================================
			footprinting is a generic solution
            for timestamped event checking
    */

    require_once( 'interface.nuclear.php' );

    class Footprint implements iSingleton
    {
        private static $_instance;
        private $table;
        private $db;

        function __construct()
        {
            $this->table = "nu_footprint";
            $this->db = Database::getInstance();
        }

        public static function getInstance()
        {
            if( is_null( self::$_instance ) )
                self::$_instance = new Footprint();

            return self::$_instance;
        }


        //
        // Create a footprint, default expiration of 1 minute
        //

        public function create( $key_data, $lifetime=60 )
        {
            if( is_string($key_data) )
            {
                $signature = hash("sha1", strtolower( $key_data ));
            }
            else
            {
                $signature = hash("sha1", strtolower( serialize($key_data) ));
            }

            $time   = time();

            $sql    = "insert into {$this->table} (signature, updated, expires) ".
                      "values (UNHEX('{$signature}'), {$time}, {$time}+{$lifetime}) ".
                      "on duplicate key update ".
                        "updated=IF(expires<=values(updated), values(updated), updated), ".
                        "expires=IF(expires<=values(updated), values(expires), expires);";

            return $this->db->affected( $sql ) > 0 ? true : false;
        }


        //
        // Garbage collection on the footprint table
        //

        public function gc()
        {
            $time   = time();
            $hold   = 1000;
            $sql    = "delete from {$this->table} ".
                      "where expires<={$time} limit {$hold};";

            $this->db->void( $sql );
        }
    }

?>
