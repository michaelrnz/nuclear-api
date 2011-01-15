<?php
	/*
		nuclear.framework
		altman,ryan,2008

		Feed abstract
		========================================
		  generalized start mechanism for
		  syndication
	*/

	require('abstract.service.php');

	abstract class Feed extends Service
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
				// xml layer
				$this->doXmlLayer();

			}
			catch( Exception $e )
			{
				$this->feedException( $e );
			}
		}

		//
		// generalized global application
		// exception handling
		//
		protected function feedException( $e ){ die('Feed exception: ' . $e->getMessage()); }

		//
		// basic Nuclear file includes
		//
		protected function loadBase()
		{
			includer(
				array(
					"class.mysqlconnection.php",
					"wrap.mysql.php",
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
			include( "feed.redirects.php" );
		}

		/*
		 Public abstract methods in which to call externally from the class
		*/

		//
		// override application-specific
		//
		public function doXmlLayer(){}

	}

?>
