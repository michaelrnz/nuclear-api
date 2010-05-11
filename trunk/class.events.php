<?php

    /*
        nuclear.framework
        altman,ryan,2008

        Nuclear Event
        ================================
            adds functionality to classes
            similar to that of javascript
            events or WP hooks.

    */

    require_once('interface.nuclear.php');

    class Aspect
    {
        private $handlers;

        function __construct()
        {
            $this->handlers = array();
        }

        function subscribe( $callback )
        {
            array_push( $this->handlers, $callback );
        }

        function unsubscribe( $callback )
        {
            if( ($index = $this->hasSubscriber($callback))!==false )
            {
                $this->handlers = array_splice( $this->handlers, $index, 1 );
            }
        }

        function isSubscribed( $callback )
        {
            return array_search( $this->handlers, $callback );
        }

        function emit( &$data=null, &$source=null )
        {
            if( count($this->handlers)>0 )
            {
                foreach( $this->handlers as $H )
                {
                    if( $source )
                    {
                        call_user_func_array( $H, array($data, $source) );
                    }
                    else
                    {
                      call_user_func( $H, $data );
                    }
                }
            }
        }

        function &filter( &$data=null, &$source=null )
        {
            if( count($this->handlers)>0 )
            {
                foreach( $this->handlers as $H )
                {
                    if( $source )
                    {
                        $data = call_user_func_array( $H, array($data, $source) );
                    }
                    else
                    {
                        // pass by reference for 5.2.x and lower
                        if( !is_object($o) && version_compare( PHP_VERSION, '5.3.0', '<' ) )
                            $data = call_user_func( $H, array($data) );
                        else
                            $data = call_user_func( $H, $data );
                    }
                }
            }
            return $data;
        }
    }

    class Events implements iSingleton
    {
        private static $_instance;
        private $aspects;

        function __construct()
        {
            $this->aspects = array();
        }

        public static function getInstance()
        {
            if( is_null(self::$_instance) )
                self::$_instance = new Events();
            return self::$_instance;
        }

        public function attach( $aspect, $callback )
        {
            if( !$aspect || !$callback ) return;

            $aspect = strtolower($aspect);

            if( !array_key_exists( $aspect, $this->aspects ) )
                $this->aspects[ $aspect ] = new Aspect();

            $this->handlers[ $aspect ]->subscribe( $callback );

            return $this;
        }

        public function detach( $aspect, $callback )
        {
            if( !$aspect || !$callback ) return;

            $aspect = strtolower($aspect);

            if( !array_key_exists( $aspect, $this->aspects ) )
                return $this;

            $this->handlers[ $aspect ]->unsubscribe( $callback );

            return $this;
        }

        public function isAttached( $aspect, $callback )
        {
            if( !$aspect || !$callback ) return;

            $aspect = strtolower($aspect);

            if( array_key_exists( $aspect, $this->aspects ) )
                return $this->aspects[$aspect]->isSubscribed( $callback ) > 0;

            return false;
        }

        public function emit( $aspect, &$data=null, &$source=null )
        {
            $aspect = strtolower($aspect);

            if( array_key_exists( $aspect, $this->handlers ) )
            {
                $this->aspects[ $aspect ]->emit( $data, $source );
            }
        }

        public function &filter( $aspect, &$data=null, &$source=null )
        {
            $aspect = strtolower( $aspect );

            if( array_key_exists( $aspect, $this->aspects ) )
            {
                return $this->aspects[ $aspect ]->filter( $data, $source );
            }

            return $data;
        }
    }

?>
