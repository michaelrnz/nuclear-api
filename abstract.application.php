<?php
	/*
		nuclear.framework
		altman,ryan,2008

		Application abstract
		========================================
			generalized start mechanism for
			web-app
	*/

	require('abstract.service.php');

	abstract class Application extends Service
	{
		private static $instance = null;

		protected $root;

		//
		// constructor takes general object o
		function __construct( $auto_post=true, $auto_display=false )
		{
			//
			// load service
			parent::__construct();

			//
			// wrap the initialization for exceptions
			try
			{
				//
				// basic application starts
				$this->initURIDriver();
				$this->initIdentification();
				$this->loadRedirectLayer();

				//
				// do post handling?
				if( $auto_post )
				{
					$this->doPostLayer();
				}

				//
				// do display handling?
				if( $auto_display )
				{
					$this->doDisplayLayer();
				}

			}
			catch( Exception $e )
			{
				$this->applicationException( $e );
			}
		}

		//
		// generalized global application
		// exception handling
		//
		protected function applicationException( $e ){ die('Application exception: ' . $e->getMessage()); }

		//
		// basic Nuclear file includes
		//
		protected function loadBase()
		{
			parent::loadBase();
			includer(
				array(
					"class.localapi.php"
				)
			);
		}

		//
		// override application-specific
		//
		protected function initURIDriver(){}

		//
		// identification initialization
		// override application-specific
		//
		protected function initIdentification(){}

		//
		// generalized include
		// override application-specific
		//
		protected function loadRedirectLayer()
		{
			@include( "application.redirects.php" );
		}

		/*
		 Public abstract methods in which to call externally from the class
		*/

		//
		// generalized
		//
		public function doPostLayer(){}

		//
		// override application-specific
		//
		public function doDisplayLayer(){}

	}

?>
