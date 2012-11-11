<?php
	/*
		nuclear.framework
		altman,ryan,2008

		Service abstract
		========================================
			generalized start mechanism for
			service on nuclear
	*/

	require('class.object.php');

	abstract class Service extends ObjectContainer
	{
function __construct() {
			//
			// wrap the initialization for exceptions
			try
			{
				//
				// basic application starts
				$this->loadBase();
				$this->loadGlobals();
				$this->loadHandlers();
				$this->startDatabase();
				$this->startSession();

				//
				// specific loading occurs afterwards
				//
			}
			catch( Exception $e )
			{
				$this->serviceException( $e );
			}
		}

		//
		// service exception
		//
		private function serviceException( $e )
		{
			die( "Service exception: " . $e->getMessage() );
		}

		//
		// map globals application-wide
		//
		private function loadGlobals()
		{
			require( "application.globals.php" );
		}

		//
		// basic Nuclear file includes
		//
		protected function loadBase()
		{
			require("var.global.php");
			includer(
                            array(
                                "class.mysqlconnection.php",
                                "wrap.mysql.php",
                                "lib.nuevent.php",
                                "lib.entity.php",
                                "class.nupreference.php"
                            )
                        );
		}

		//
		// external Handlers includes
		//
		private function loadHandlers()
		{
			if( isset( $GLOBALS['HANDLERS'] ) && is_dir( $GLOBALS['HANDLERS'] ) )
			{
				$handler_dir = $GLOBALS['HANDLERS'];
				$handlers    = scandir( $handler_dir );

				if( count($handlers)>1 )
				{
				  // start buffering for handler.log
				  ob_start();

				  // load handlers
				  for($a=2; $a<count($handlers); $a++)
				  {
					if( substr( $handlers[$a], -4 ) == '.php' )
						@include( "{$handler_dir}/{$handlers[$a]}" );
				  }

				  // log handler loads
				  @file_put_contents( $GLOBALS['CACHE'] . '/handlers.loaded.log', ob_get_contents() );

				  // flush buffer
				  ob_end_clean();
				}
			}
		}

		//
		// include mysql connection
		//
		protected function startDatabase()
		{
			@include("application.database.php");
		}

		//
		// include the session managing class
		//
		protected function startSession()
		{
			require( "class.sessions.php" );

			if( isset($_SESSION) )
			{
			  NuEvent::raise('nu_session_started');
			}
		}

	}

?>
