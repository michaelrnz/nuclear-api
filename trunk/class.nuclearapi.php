<?php
    /*
        nuclear.framework
        altman,ryan,2008

        NuclearAPI (LocalAPI)
        ==========================================
            allows calling on unamed framework,
            bypasses the API class instance
            and parsing
    */

    class NuclearAPI implements iSingleton
    {
        protected static $_instance;
        public static $_gv = "APICALL";
        
        /* iSingleton interfacing */
        
        public static function getInstance()
        {
            if( is_null( self::$_instance ) )
                self::$_instance = new NuclearAPI();

            return self::$_instance;
        }
        
        public static function setInstance( &$object )
        {
            if( is_a( $object, "NuclearAPI" ) )
                self::$_instance = $object;
            
            return self::$_instance;
        }
        
        
        /* anonymous caller  // api method generation */
        
        //
        // We call the api method file dynamically
        //
        function __call( $name, $args )
        {
            if( preg_match('/^(get|post|put|delete)(\w+)$/i', $name, $method_match ) )
            {
                $rest               = strtolower($method_match[1]);
                $method         = $method_match[2];
                $method[0]  = strtolower($method[0]);
                
                return self::execute( $rest, $method, $args[0], $args[1] );
            }
            else
            {
                return null;
            }
        }


        //
        // sync call to global field
        // required for API
        //
        private static function globalize( &$call )
        {
            $GLOBALS[ self::$_gv ] = $call;
        }

        //
        // call the api, via include
        // TODO handle for dynamic output types
        //
        private static function &execute( $rest, $method, &$call, $output="json" )
        {
            // name the src
            $src = "api." . $rest . "." . strtolower($method) . ".php";

            //
            // globalize the call
            self::globalize( $call );

            //
            // try include
            $apiclass = (@include $src);

            if( class_exists( $apiclass, false ) )
            {
                try
                {
                    $o = new $apiclass( microtime(true), $output, false );
                    
                    //
                    // get object of instance
                    //
                    return $o->response;
                }
                catch( Exception $e )
                {
                    $o = new Object();
                    $o->status      = "error";
                    $o->valid       = 0;
                    $o->message = $e->getMessage();

                    return $o;
                }
            }
            else
            {
                // should hopefully not occur
                throw new Exception("Call to unknown API method: " . $rest . "." . $method);
            }
        }


        //
        // get/set global field index 
        // index of call
        //
        public static function setGlobal($s)
        {
            // valid strings only
            if( strlen($s)>1 )
                self::$_gv = $s;
        }

        public static function getGlobal($s)
        {
            return self::$_gv;
        }

    }

?>
