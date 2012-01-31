<?php
/*
 * DirectoryIndex
 *
 * Copyright 2011 Ryan <altaokami@gmail.com>
 * Nuclear Framework
 *
 * ==========================================
 * Directory listing/index wrapper for
 * searching in a set of paths.
 * 
 */

class DirectoryIndex {
	
	/**
	 * @var paths
	 * @var refresh
	 * @var index
	 * @var buildTime
	 */
	protected $path;
	protected $paths;
	protected $refresh;
	protected $index;
	protected $accessTime;
	protected $buildTime;
	
	
	/**
	 * 
	 * @param int $refresh
	 * @return void
	 */
	function __construct ($refresh=60) {

		$this->accessTime	= time();
		$this->paths		= array();
		$this->setRefresh($refresh);
		$this->resourcePath("/tmp");
	}
	
	
	/**
	 * setRefresh
	 * 
	 * @param int $refresh
	 * @return DirectoryIndex
	 */
	public function setRefresh ($refresh=0) {
		
		if( is_numeric($refresh) )
			$this->refresh = $refresh;
		
		return $this;
	}
	
	
	/**
	 * addPath
	 * 
	 * @param string $path
	 * @return DirectoryIndex
	 */
	public function addPath ($path) {
		
		array_push($this->paths, $path);
		return $this;
	}
	
	
	/**
	 * prependPath
	 * 
	 * @param string $path
	 * @return DirectoryIndex
	 */
	public function prependPath ($path) {
		
		$this->paths = array_merge(array($path), $this->paths);
		return $this;
	}


	/**
	 * getResourcePath
	 * @param string
	 * @return string
	 */
	public function resourcePath ($path=null) {

		if (strlen($path)) {
			$this->path = rtrim($path, '/');
			return $this;
		}
		
		return "{$this->path}/env.{$this->resourceKey()}";
	}


	/**
	 * search for a filename in the path
	 * 
	 * @param string $fileName
	 * @return mixed
	 */
	public function search ($fileName) {
		
		// trim and add forward-slash (first character delim)
		$fileName = "/" . ltrim($fileName, "/");

		// get the listing (text)
		$index = $this->index();

		// check for position of fileName in text
		if (($pos = strpos($index, $fileName . "\t"))!==false) {

			// adjust position to after the tab
			$pos = $pos + strlen($fileName . "\t");

			// get the position of the next newline
			$end = strpos($index, "\n", $pos);

			// return the path from search to newline
			return substr($index, $pos, ($end - $pos));
		}

		return false;
	}
	
	
	/**
	 * getListing - check the cachefile and create new listing or load
	 * 
	 * @return string
	 */
	protected function index () {
		
		if( is_null($this->index) )
		{
			$cacheFile	= $this->resourcePath();
			$fileExists	= file_exists($cacheFile);

			// set the build time
			if( $fileExists )
				$this->buildTime = filemtime($cacheFile);
			else
				$this->buildTime = 0;

			// conditions to use index
			if( $fileExists && ($this->accessTime - filemtime($cacheFile)) <= $this->refresh )
			{
				$index = file_get_contents($cacheFile);
			}
			else
			{
				$index= $this->buildIndex();
				$this->buildTime = $this->accessTime;

				$tmpFile = $cacheFile . "." . time();
				file_put_contents( $tmpFile, $index );
				rename($tmpFile, $cacheFile);
			}
			
			$this->index = $index;
		}
		
		return $this->index;
	}


	/**
	 * buildIndex
	 * @return string
	 */
	protected function &buildIndex () {

		$index = "";

		foreach( $this->paths as $path )
		{
			exec('find '. $path .' -type f -not -path \'*/.*\' '.
				'-printf "%T@\t/%f\t%p\n" | sort -k1 -n -r | '.
				'cut -f2,3', $output);

			$index .= implode("\n", $output) . "\n";
		}

		return $index;
	}
	
	
	/**
	 * getResourceKey
	 * @return string
	 */
	protected function resourceKey () {
		
		return hash('md5', implode($this->paths));
	}

}
