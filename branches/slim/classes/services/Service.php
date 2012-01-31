<?php
/*
 * Service
 *
 * Copyright 2011 Ryan <altaokami@gmail.com>
 * Nuclear Framework
 * Revised 2011
 *
 * ==========================================
 * generalized start mechanism for
 * service on nuclear
 */

abstract class Service extends ObjectContainer {

	/**
	 * Constructor
	 * TODO shift the try block to another method
	 * 
	 * @return void
	 */
	function __construct () {

		// wrap the initialization for exceptions
		try
		{
			// basic application starts
			$this->loadGlobals();
			$this->loadHandlers();
			$this->startDatabase();
			$this->startSession();

		}
		catch( Exception $e )
		{
			$this->serviceException( $e );
		}
	}
	

	/**
	 * Handle service exception
	 *
	 * @param Exception
	 * @return void
	 */
	private function serviceException ($e) {
		
		die( "Service exception: " . $e->getMessage() );
	}


	/**
	 * [depreciated]
	 * map globals application-wide
	 * @return void
	 */
	private function loadGlobals () {
		require( "application.globals.php" );
	}


	/**
	 * [depreciated]
	 * Include database connection
	 * @return void
	 */
	protected function startDatabase () {
		
		@include("application.database.php");
	}

	
	/**
	 * [depreciated]
	 * previously loaded the core sources
	 * @return void
	 *
	protected function loadBase () {
		includer(
			array(
				"class.events.php",
				"class.database.php",
				"class.mysqlconnection.php",
				"wrap.mysql.php",
				"lib.nuevent.php",
				"lib.entity.php",
				"class.nupreference.php"
			)
		);
	}
	*/


	/**
	 * Load event handlers
	 * @return void
	 */
	private function loadHandlers () {
		
		if( isset( $GLOBALS['HANDLERS'] ) && is_dir( $GLOBALS['HANDLERS'] ) )
		{
			$handler_dir = $GLOBALS['HANDLERS'];
			$handlers    = glob( $handler_dir . "/*.php" );

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


	/**
	 * Load session class
	 * @return void
	 */
	protected function startSession () {

		// begin session
		Sessions::sessionLogged();
		
		if( isset($_SESSION) )
		{
		  NuEvent::raise('nu_session_started');
		}
	}

}
