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

		function __construct()
		{
			//
			// wrap the initialization for exceptions
			try
			{
				//
				// basic application starts
				$this->loadGlobals();
				$this->loadBase();
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
			require( "var.global.php" );
		}

		//
		// basic Nuclear file includes
		//
		protected function loadBase()
		{
			includer( array("class.mysqlconnection.php","wrap.mysql.php") );
		}

		//
		// include mysql connection
		//
		protected function startDatabase()
		{
			@include("application.database.php");
			//@include("startdb.php");
		}

		//
		// include the session managing class
		//
		protected function startSession()
		{
			require( "class.sessions.php" );
		}

	}

?>
