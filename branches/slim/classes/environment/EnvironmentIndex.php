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
	 * Set refresh times
	 *
	 * @param int $refresh
	 * @param int $forceRefresh
	 * @return EnvironmentIndex
	 */
	public function setRefresh ($refresh, $forceRefresh=null) {
		parent::setRefresh($refresh);
		if (!is_null($forceRefresh)) {
			$this->forceRefresh = $forceRefresh;
		}

		return $this;
	}


	/**
	 * register
	 * getIndex and register with spl autoloading
	 * 
	 * @return EnvironmentIndex
	 */
	public function register () {

		if (empty($this->registered)) {

			$this->registered = true;
			$this->index($this->refresh);

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
	public function forceIndex () {
		$this->index = null;
		$this->index(0);
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
	public function resolve ($structure) {

		while (true) {

			$filename = $this->search($structure .'.php');

			if (strlen($filename)) {
				include_once($filename);

				if( class_exists($structure) || interface_exists($structure) )
					return;
			}
			
			if (($this->accessTime - $this->buildTime) > $this->forceRefresh) {
				$this->forceIndex();
				continue;
			}

			break;
		}

		throw new Exception("EnvironmentIndex: unabled to load structure ({$structure})");
	}

}

