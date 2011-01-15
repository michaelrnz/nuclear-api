<?php
	/*
		nuclear.framework
		altman,ryan,2008

		NuclearData
		==========================================
			abstract data building class
			no specific data type
	*/

	require_once('interface.nuclear.php');

	abstract class NuclearDataContainer implements iQuery,iBuilder
	{
		protected $object;
		protected $node;
		protected $request;

		function __construct( $request )
		{
			$this->request = $request;
			$this->open();
			$this->build();
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

		public function getObject()
		{
			return $this->object;
		}
	}

?>
