<?php
/*
 * Application
 *
 * Copyright 2011 Ryan <altaokami@gmail.com>
 * Nuclear Framework
 * Revised 2011
 *
 * ==========================================
 * Template-pattern mechanism for web-app
 */

abstract class Application extends Service {

	/**
	 * @var instance
	 * @var root
	 */
	protected static $instance;
	protected $root;


	/**
	 * Constructor
	 * 
	 * @return void
	 */
	function __construct ($autoPost=true, $autodisplay=false ) {

		// load service
		parent::__construct();

		// wrap the initialization for exceptions
		try
		{
			// basic application starts
			$this->initURIDriver();
			$this->initIdentification();
			$this->loadRedirectLayer();

			// do post handling?
			if( $autoPost )
			{
				$this->doPostLayer();
			}

			// do display handling?
			if( $autoDisplay )
			{
				$this->doDisplayLayer();
			}
		}
		catch( Exception $e )
		{
			$this->applicationException( $e );
		}
	}
	

	/**
	 * Generalized wrapper for application exception
	 * @return void
	 */
	protected function applicationException ($e) {
		die('Application exception: ' . $e->getMessage());
	}
	

	/**
	 * [depreciated]
	 * 
	 * @return void
	 *
	protected function loadBase()
	{
		parent::loadBase();
		includer(
			array(
				"class.localapi.php",
				"class.nuclearapi.php"
			)
		);
	}
	*/


	/**
	 * Application-specific
	 * @return void
	 */
	protected function initURIDriver(){}


	/**
	 * Initialize identification layer
	 * @return void
	 */
	protected function initIdentification(){}


	/**
	 * Load redirect layer
	 * TODO: refactor the need for including
	 * @return void
	 */
	protected function loadRedirectLayer s() {
		@include( "application.redirects.php" );
	}


	/**
	 * Application-specific
	 * @return void
	 */
	public function doPostLayer(){}
	

	/**
	 * Application-specific
	 * @return void
	 */
	public function doDisplayLayer(){}

}
