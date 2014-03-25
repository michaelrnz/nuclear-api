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
	 * @var string
	 */
	const DELIMITER = "$";

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
		
		if (is_numeric($refresh))
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
		
		array_push($this->paths, rtrim($path,'/'));
		return $this;
	}
	
	
	/**
	 * prependPath
	 * 
	 * @param string $path
	 * @return DirectoryIndex
	 */
	public function prependPath ($path) {
		
		$this->paths = array_merge(array(rtrim($path,'/')), $this->paths);
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
		$fileName = self::DELIMITER . ltrim($fileName, "/");

		// get the listing (text)
		$index = $this->index($this->refresh);

		// check for position of fileName in text
		if (($pos = strpos($index, $fileName . self::DELIMITER))!==false) {

			// get the position of the next newline
			$end = strpos($index, "\n", $pos);

			// tab before newline, use reverse search
			$tab = strrpos($index, self::DELIMITER, $end-strlen($index));

			if ($tab>$pos) {
				return substr($index, $tab+1, ($end-$tab-1));
			}
		}

		return false;
	}
	
	
	/**
	 * getListing - check the cachefile and create new listing or load
	 * 
	 * @return string
	 */
	protected function index ($refresh=null) {

		if (is_null($refresh)) {
			$refresh = $this->refresh;
		}
		
		if (is_null($this->index)) {

			$cacheFile	= $this->resourcePath();
			$fileExists	= file_exists($cacheFile);

			// set the build time
			if ($fileExists)
				$this->buildTime = filemtime($cacheFile);
			else
				$this->buildTime = 0;

			// conditions to use index
			if( $fileExists && ($this->accessTime - filemtime($cacheFile)) <= $refresh )
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
		foreach ($this->paths as $path) {
			$index .= $this->buildReferenceText($path, $this->extract($path));
		}
		return $index;
	}

	/**
	 * Extract the file structure from the path
	 *
	 * @param string $path
	 * @return array<string>
	 */
	protected function &extract ($path) {
		$sources = glob($path.'/*.php');
		foreach (glob($path.'/*',GLOB_ONLYDIR) as $subpath) {
			foreach ($this->extract($subpath) as $source) {
				$sources[] = $source;
			}
		}
		return $sources;
	}

	/**
	 * Build the references from the path sources
	 *
	 * @param string $path
	 * @param array<string> $sources
	 * @return string
	 */
	protected function &buildReferenceText ($path, &$sources) {
		$referenceText = "";
		foreach ($sources as $source) {
			$referenceText .= 
				self::DELIMITER . str_replace($path.'/','',$source) .
				self::DELIMITER . basename($source) .
				self::DELIMITER . $source . "\n";
		}
		return $referenceText;
	}

	/**
	 * getResourceKey
	 * @return string
	 */
	protected function resourceKey () {
		
		return hash('md5', implode($this->paths));
	}

}
