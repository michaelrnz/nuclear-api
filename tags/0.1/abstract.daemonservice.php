<?php
	/*
		nuclear.framework
		altman,ryan,2008

		Daemon Service
		========================================
			generalized start mechanism for
			service daemon
	*/

	require_once('class.tcpdaemon.php');

	abstract class DaemonService
	{
		private static $instance = null;

		protected $name;
		protected $port;
		protected $max_clients;

		protected $daemon;

		//
		// constructor
		// root directory of nuclear, port to listen, max clients, handler class
		//
		function __construct( $path, $port, $max_clients, $name='daemon' )
		{
			//
			// set paths for application
			set_include_path( get_include_path() . PATH_SEPARATOR . implode( PATH_SEPARATOR, $path ) );

			//
			// assign parameters
			$this->port = $port;
			$this->max_clients = $max_clients;

			//
			// singlton instance
			self::$instance = $this;

			//
			// wrap the initialization for exceptions
			try
			{
				//
				// allow forever run
				set_time_limit(0);

				//
				// basic application starts
				$this->startGlobals();
				$this->includeBase();
				$this->startDatabase();
				$this->createDaemon();
				$this->addDaemonHandlers();

			}
			catch( Exception $e )
			{
				$this->handleException( $e );
			}

		}

		//
		// magic get
		function __get( $f )
		{
			switch( $f )
			{
				case 'daemon':
					return $this->daemon;
				default:
					return null;
			}
		}

		//
		// magic call
		function __call( $m, $args )
		{
			switch( $m )
			{
				case 'start':
				case 'stop':
				case 'suspend':
				case 'resume':
					$this->daemon->$m();
					break;
			}
		}

		//
		// generalized global application
		// exception handling
		//
		protected function handleException( $e ){ die('Daemon exception: ' . $e->getMessage()); }

		//
		// map globals application-wide
		//
		protected function startGlobals()
		{
			require( "daemon.globals.php" );
			require( "var.global.php" );
		}

		//
		// include mysql connection
		//
		protected function startDatabase()
		{
			include( "{$this->name}.database.php" );
		}

		//
		// basic Nuclear file includes
		//
		protected function includeBase()
		{
			includer(
				array(
					"class.mysqlconnection.php",
					"wrap.mysql.php",
					"class.object.php",
				)
			);
		}

		//
		// create daemon
		//
		protected function createDaemon()
		{
			//
			// create daemon
			$this->daemon = new TCPDaemon( $this->port, $this->max_clients );
		}

		//
		// add event hanlders to daemon in order to process 
		// client requests, implement in extend
		//
		protected function addDaemonHandlers()
		{
		}

	}

?>
