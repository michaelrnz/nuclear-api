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
	 * search for a filename in the path
	 * 
	 * @param string $fileName
	 * @return mixed
	 */
	public function search ($fileName) {
		
		// ensure the filename starts with slash
		if( substr($fileName,0,1) != '/' )
			$fileName= "/" . trim($fileName);
		
		// get the listing (text)
		$index = $this->getIndex();

		// check for position of fileName in text		
		if( ($pos = strpos($index, $fileName . "\n")) )
		{
			// get a 1k chunk of the text before match
			$buffer = substr($index,
							($pos<1024) ? 0 : $pos-1024,
							($pos<1024) ? $pos : 1024);
			
			// reverse and find newline (this is a length)
			$firstNewline = $pos - strpos(strrev($buffer), "\n");
			
			// find the next newline from the original match
			$lastNewline = strpos($index, "\n", $pos-1);

			return substr($index,
						$firstNewline,
						($lastNewline-$firstNewline));
		}
		
		return false;
	}
	
	
	/**
	 * getListing - check the cachefile and create new listing or load
	 * 
	 * @return string
	 */
	protected function getIndex () {
		
		if( is_null($this->index) )
		{
			$cacheFile	= $this->getResourcePath();
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

		$index = "\n";
		
		foreach( $this->paths as $path )
		{
			exec("find ". $path ." -type f -not -path '*/.*'", $output);
			$index .= implode("\n", $output) . "\n";
		}

		return $index;
	}
	
	
	/**
	 * getResourceKey
	 * @return string
	 */
	protected function getResourceKey () {
		
		return hash('md5', implode($this->paths));
	}
	
	
	/**
	 * getResourcePath
	 * @return string
	 */
	protected function getResourcePath () {
		
		return "/tmp/path.". $this->getResourceKey();
	}
	
}
