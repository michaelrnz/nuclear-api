<?php
    /*
        nuclear.framework
        altman,ryan,2010

        Scheduler
        ==========================================
            module for inserting data to queue table
            to be used via an async method
            (via NuFiles::ping, async socket GET)
            
    */

    class Scheduler implements iSingleton
    {
        protected static $_instance;
        protected static $_table = 'nu_queue';
        
        public static function getInstance()
        {
            if( is_null( self::$_instance ) )
                self::$_instance = new Scheduler();
            
            return self::$_instance;
        }
        
        public static function setInstance( &$object )
        {
            if( is_a( $object, "Scheduler" ) )
                self::$_instance = $object;
            
            return self::$_instance;
        }
        
        
        /* Queuing */
        
        //
        // Save by id+label, serialize object, return id
        //
        public function queue( $label, $object )
        {
            $label      = safe_slash( $label );
            $data       = safe_slash( serialize( $object ) );
            
            $id = WrapMySQL::id(
                "insert into ". self::$_table ." (label, data) ".
                "values ('{$label}', '{$data}');",
                "Unable to queue object"
            );
            
            return $id;
        }
        
        //
        // Lookup by id+label, remove, return unserialized
        //
        public function unqueue( $id, $label )
        {
            require_once('class.nuselect.php');
    
            $q      = new NuSelect(self::$_table . " Q");
            $q->where("id={$id}");
            $q->where("label='{$label}'");
            
            $data = $q->single();

            if( $data )
                WrapMySQL::void("delete from ". self::$_table ." where id={$id} limit 1;");
            
            return unserialize( $data['data'] );
        }

        //
        // Dispatching
        //
        public function dispatch( $id, $uri )
        {
            require_once('lib.nufiles.php');
            NuFiles::ping( "{$uri}?schedule_id={$id}" );
        }
        
    }
    
?>
