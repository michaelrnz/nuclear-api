<?php
	/*
		nuclear.framework
		altman.ryan,2008

		TCP Daemon
		===================================
			Generalized daemon for
			handling socket connections
			contains event
	*/
	
	require_once('class.eventdriver.php');

	class TCPDaemon extends EventDriver
	{
		private $max_clients;
		private $port;
		private $client;
		private $socket;
		private $read;
		private $mode;

		function __construct( $p, $mc=10, $address='0.0.0.0' )
		{
			
			//
			// start events
			parent::__construct();

			$this->port 	   = $p;
			$this->max_clients = $mc;
			$this->address	   = $address;

			//
			// init clients
			$this->client = array();

			//
			// init socket
			$this->socket = stream_socket_server("tcp://$address:$p", $errno, $errstr);
		}

		//
		// fill the read array with resources 
		//
		private function bufferRead()
		{
			//
			// first
			$this->read[0] = $this->socket;

			//
			// loop fill clients on null
			for( $i = 0; $i < $this->max_clients; $i++ )
			{
				if( $this->client[$i]['sock'] != null )
					$this->read[$i+1] = $this->client[$i]['sock'];
			}
		}

		private function acceptClient()
		{
			//
			// loop through clients
			for( $i = 0; $i < $this->max_clients; $i ++ )
			{
				//
				// check for available
				if( $this->client[$i]['sock'] == null )
				{
					//
					// accept connection resource
					$this->client[$i]['sock'] = stream_socket_accept( $this->socket, -1 );

					//
					// handle accept
					$o = new Object();
					$o->client = $i;
					$o->resource = $this->client[$i]['sock'];

					// fire event
					$this->fire('ClientAccept', $o);

					return;
				}
			}

			//
			// did not find available client space
			echo "too many clients\n";
		}

		private function readClient()
		{
			//
			// loop through clients
			for( $i = 0; $i < $this->max_clients; $i++ )
			{
				//
				// check if client resource is in read
				if( in_array($this->client[$i]['sock'] , $this->read) )
				{
					//
					// handle client connection
					$o = new Object();
					$o->client = $i;
					$o->resource = $this->client[$i]['sock'];
					$o->doUnset = true;

					// fire event
					$this->fire('ClientConnect', $o );

					if( $o->doUnset || !isset($o->doUnset) )
					{
						unset( $this->client[$i] );
					}
				}
				else if( is_resource($this->client[$i]['sock']) )
				{
					//
					// close otherwise
					fclose( $this->client[$i]['sock'] );
					unset( $this->client[$i] );
				}
			}
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
					//
					// buffer read space
					$this->bufferRead();

					//
					// listen for ready stream
					$ready = stream_select( $this->read, $write=null, $except=null, $tv_sec = 0, $tv_usec = 10000 );

					//
					// check for socket in read
					if( in_array($this->socket, $this->read) )
					{
						//
						// check if ready change was not on client
						$this->acceptClient();

						if( --$ready <= 0 )
						continue;
					}

					//
					// proceed to read client if available
					$this->readClient();

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
