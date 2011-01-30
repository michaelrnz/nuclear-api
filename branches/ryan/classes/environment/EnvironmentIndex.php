<?php
/*
 * EnvironmentIndex
 *
 * Copyright 2011 Ryan <altaokami@gmail.com>
 * Nuclear Framework
 *
 * ==========================================
 * Environment file index.
 * 
 */
require_once('DirectoryIndex.php');

class EnvironmentIndex extends DirectoryIndex {
	
	/**
	 * @var instance (singleton)
	 * @var forceRefresh
	 * @var registered
	 */
	protected static $instance;
	protected $forceRefresh;
	protected $registered;


	/**
	 * private constructor for singleton
	 * sets up the refresh properties
	 *
	 * @return void
	 */
	private function __construct () {

		parent::__construct(ENV_REFRESH);
		$this->forceRefresh = ENV_FORCE_REFRESH;
	}


	/**
	 * Singleton instance retrieval
	 * 
	 * @return EnvironmentIndex
	 */
	public static function getInstance () {

		if( !(self::$instance instanceof self) )
			self::$instance = new self();

		return self::$instance;
	}


	/**
	 * register
	 * getIndex and register with spl autoloading
	 * 
	 * @return EnvironmentIndex
	 */
	function register () {

		if( empty($this->registered) )
		{
			$this->registered = true;
			$this->getIndex();

			// register the loader
			spl_autoload_register(array($this,"resolve"));
		}

		return $this;
	}


	/**
	 * Force refresh of the index
	 * regardless of DirectoryIndex's refresh
	 *
	 * @return void
	 */
	function forceIndex () {

		$this->index	= null;
		$refresh		= $this->refresh;
		$this->refresh	= 0;
		$this->getIndex();
		$this->refresh	= $refresh;
	}


	/**
	 * Resolve a structure by classname using
	 * DirectoryIndex's search method.
	 *
	 * Called for spl autoloading
	 *
	 * @param structure - string (class or instance)
	 * @return void
	 */
	function resolve ($structure) {

		while(true) {

			$filename = $this->search($structure .'.php');

			if( strlen($filename) )
			{
				@include_once($filename);

				if( class_exists($structure) || interface_exists($structure) )
					return;
			}
			
			if( ($this->accessTime - $this->buildTime) > $this->forceRefresh )
			{
				$this->forceIndex();
				continue;
			}

			break;
		}

		throw new Exception("EnvironmentIndex: unabled to load structure ({$structure})");
	}

}

		