<?php
	
	/*
		nuclear.framework
		altman,ryan,2008

		NuclearCache
		========================================
			iCacher and Cacher models general
			parent-type class, extended for
			various Nuclear classes
	*/

	require_once('lib.cache.php');
	require_once('class.cachecontrol.php');
	require_once('interface.nuclear.php');

	abstract class NuclearCacher implements iCacher
	{
		//
		// protected meta variables of cached data
		protected $name;
		protected $object;
		protected $expiration;
		protected $auto;
		protected $doCache;
		protected $dataType;


		//
		// create new Cacher( name, type, expires, auto uncache )
		// will by default take the existing file and uncache
		//
		public function __construct($exp=false,$auto=true)
		{
			$this->expiration = $exp;

			//
			// tries to uncache if possible
			//
			if( $auto )
			{
				if( !$this->uncache() )
				{
					$this->object = null;
				}
			}
			else
			{
				$this->object = null;
			}
		}

		//
		// caches using Cache library
		//
		public function cache()
		{
			Cache::in($this->object, $this->cacheName());
		}

		//
		// uncache using Cache library
		//
		public function uncache()
		{
			if( ($this->object = Cache::out( $this->cacheName(), $this->dataType, $this->expiration )) )
			{
				return true;
			}
			return false;
		}

		//
		// requires implementation
		//
		public function cacheName(){}

		//
		// sends protected member
		//
		public function type(){ return $this->dataType; }

		//
		// isCached, part of abstract class
		//
		protected function isCached()
		{
			if( $this->object === null )
			{
				return false;
			}
			else
			{
				return true;
			}
		}

		public function getObject()
		{
			return $this->object;
		}

	}

?>
