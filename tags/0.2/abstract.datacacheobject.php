<?php
	/*
		nuclear.framework
		altman,ryan,2008

		NuclearData
		==========================================
			abstract data building class
			no specific data type
	*/

	require_once('abstract.cacher.php');

	abstract class NuclearDataCacheObject extends NuclearCacher implements iQuery,iBuilder
	{
		protected $node;
		protected $request;

		function __construct( $request, $expires=false )
		{
			//
			// TODO move to other
			$this->dataType = "json";

			parent::__construct( $expires );

			//
			// check for cache
			if( !$this->isCached() )
			{
				// assign request for naming, etc
				$this->request = $request;

				// open
				$this->open();

				// build
				if( $this->build() )
				{
					// cache the new data
					$this->cache();
				}
			}
		}

		//
		// interface methods
		public function compose( &$row ){}
		public function append( &$node, $element ){}
		public function shift( &$node, &$queue=null, &$attrs=null ){}
		public function doShift(){ return false; }

		//
		// protected abstract
		protected function open(){}
		protected function &firstNode(){}

		//
		// general build algorithm
		protected function build()
		{
			// using NuclearDataAspect
			require_once('aspect.nucleardata.php');

			// get the query, first node
			$r = $this->query();
			$n = $this->firstNode();

			//
			// call the composing aspect
			return NuclearDataAspect::build( $r, $this, $n );
		}

		//
		// iQuery
		public function query(){}

	}

?>
