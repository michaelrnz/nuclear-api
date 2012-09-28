<?php

    include "repository/class.object.php";
    include "repository/class.events.php";
    include "repository/class.dispatchstream.php";

    class DispatchWrapper
    {
        private $resources;
        private $listeners;

        function __construct()
        {
            $this->listeners = array();
        }

        function attach($id, &$resource)
        {
            if( array_key_exists( $id, $this->listeners ) )
            {
                array_push( $this->listeners[$id], $resource );
            }
            else
            {
                $this->listeners[$id] = array($resource);
            }
        }

        function detach($id, &$resource)
        {
        }

        function accept( $o )
        {
            $input = $o->read(1024);

            if( preg_match('/GET \/([0-9,]+)/', $input, $m) )
            {
                $ids = explode(',', $m[1]);
                foreach( $ids as $id )
                {
                    $this->attach( $id, $o );
                }
                unset($ids);
            }
            unset($input);

            $o->write(
                "HTTP/1.1 200 OK\r\n".
                "Connection: keep-alive\r\n".
                "Content-Type: text/plain\r\n\r"
            );
        }

        function dispatch( $o )
        {
            if( (rand() % 10000) < 5000 )
            {
                $obj_id = rand() % 1000;
                $obj    = json_decode('{"id":"'. $obj_id . '"}');

                if( array_key_exists( $obj_id, $this->listeners ) )
                {
                    foreach( $this->listeners[$obj_id] as $key=>$res )
                    {
                        if( $res != null )
                        {
                            if( !($res->write( json_encode($obj) )) )
                            {
                                unset($this->listeners[$obj_id][$key]);
                            }
                        }
                    }

                    if( count($this->listeners[$obj_id])==0 )
                        unset($this->listeners[$obj_id]);
                }
            }
        }
    }

    $Wrapper = new DispatchWrapper();

    Events::getInstance()->attach('daemon_accept', array($Wrapper, 'accept'));
    Events::getInstance()->attach('daemon_dispatch', array($Wrapper, 'dispatch'));

    //
    // Start stream with 100 max clients
    //
    $d = new DispatchStream( 9009, 100 );
    $d->start();

?>