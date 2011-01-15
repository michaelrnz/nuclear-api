<?php
	/*
		nuclear.framework
		altman.ryan,2008 (2010)

		Dispatch Stream based on TCP Daemon
		===================================
			Generalized daemon for dispatching
            data to stream listeners.
	*/

    class ResourceWrapper
    {
        public $resource;
        public $id;
        public $accessed;

        function __construct( &$res )
        {
            $this->resource = $res;
            $this->id       = uniqid();
            $this->accessed = time();
        }

        function read( $len=1024 )
        {
            return fread( $this->resource, $len );
        }

        function write( $data )
        {
            if( is_null($this->resource) )
                return null;

            if( !(@fwrite( $this->resource, $data . "\n" )) )
            {
                $this->resource = null;
                return -1;
            }

            $this->accessed = time();
            return 1;
        }

        function update( $ts, $diff=30 )
        {
            if( ($ts - $this->accessed) >= $diff )
                return $this->write("");

            return 1;
        }
    }

	class DispatchStream
	{
		private $max_clients;
		private $port;
		private $socket;
		private $read;
		private $mode;

		private $clients;
        private $streams;

		function __construct( $p, $mc=10, $address='0.0.0.0' )
		{
			$this->port 	    = $p;
			$this->max_clients  = $mc;
			$this->address	    = $address;

			// init clients
			$this->clients      = array();

			// init socket
			$this->socket       = stream_socket_server("tcp://$address:$p", $errno, $errstr);

            // setup streams
            $this->streams      = array($this->socket);
		}

		private function acceptClient()
		{
            $new_client = stream_socket_accept( $this->socket, -1 );

            //
            // check upper limit
            if( count($this->clients)<$this->max_clients )
            {
                $client = new ResourceWrapper( $new_client );
                array_push( $this->clients, $client );

                printf("Client %s connected... %d online\n", $client->id, count($this->clients));

				// fire event
                Events::getInstance()->emit('daemon_accept', $client);
            }
            else
            {
                echo "Client overflow\n";
                fclose($new_client);
            }
		}

        private function acceptDispatch()
        {
            Events::getInstance()->emit('daemon_dispatch');
        }

		//
		// listen routine, loops for incoming connections
		//
		private function listen()
		{
			//
			// allow loop
			set_time_limit (0);

			if (!$this->socket)
			{
				throw new Exception( "Daemon exception: $errstr ($errno)\n" );
			}
			else
			{
				//
				// loop while mode is 1
				while( $this->mode == 1 )
				{

                    $this->streams = array($this->socket);

					//
					// listen for ready stream
					$ready = stream_select( $this->streams, $write=null, $except=null, $tv_sec = 0, $tv_usec = 10000 );

					//
					// check for socket in read
					if( in_array($this->socket, $this->streams) )
					{
                        //
						// check if ready change was not on client
						$this->acceptClient();
					}

					//
					// proceed to read client if available
                    if( count($this->clients)>0 )
                    {
                        $this->acceptDispatch();
                    }

                    $ts = time();
                    if( $ts % 5 )
                    {
                        foreach( $this->clients as $key=>$c )
                        {
                            if( !$c->update($ts) )
                            {
                                printf("Client %s disconnected...", $c->id);
                                unset( $this->clients[$key] );
                                printf(" %d online\n", count($this->clients));
                            }
                        }
                    }

					//
					// catch suspend, wait for resume or stop
					while( $this->mode == 0 )
					{
						usleep(10);
					}
				}

				//
				// close socket
				if( $this->mode == -1 )
				{
					fclose($this->socket);
				}
			}
		}

		/*
		 PUBLIC CONTROL FUNCTIONS
		 usually called from within handlers
		*/

		//
		//
		public function start()
		{
			$this->mode = 1;
			$this->listen();
		}

		//
		//
		public function stop()
		{
			// catches on listen's while
			$this->mode = -1;
		}

		//
		//
		public function suspend()
		{
			// catches on listen's while
			if( $this->mode == 1 )
			{
				$this->mode = 0;
			}
		}

		//
		//
		public function resume()
		{
			if( $this->mode == 0 )
			{
				$this->mode = 1;
			}
		}
	}

?>
