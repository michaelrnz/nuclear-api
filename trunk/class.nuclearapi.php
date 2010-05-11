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
            $src_1  = "api.{$rest}.". strtolower($method) .".php";
            $src_2  = "api.{$rest}.{$output}.". strtolower($method) .".php";

            //
            // globalize the call
            self::globalize( $call );

            //
            // try include
            $api_class  = (@include $src_2);
            
            if( !$api_class )
            {
                $api_class  = (@include $src_1);
                $dynamic    = false;
            }
            else
            {
                $dynamic    = true;
            }
            
            if( $api_class && strlen($api_class)>1 )
            {
                    if( class_exists( $api_class, false ) )
                    {
                        try
                        {
                            if( $dynamic )
                                $co = new $api_class( microtime(true), false );
                            else
                                $co = new $api_class( microtime(true), $output, false );
                            
                            return $co->response;
                        }
                        catch( Exception $e )
                        {
                            $o  = new JSON();
                            $o->status      = "error";
                            $o->message = $e->getMessage();
                            return $o;
                        }
                    }
                    else
                    {
                        $o  = new JSON();
                        $o->status      = "error";
                        $o->message = "Operation is not defined: {$method}";
                    }
            }
            else
            {
                $o = new JSON();
                $o->status      = "error";
                $o->message = "Operation does not exist: {$method}";
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