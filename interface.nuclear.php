<?php
	/*
		nuclear.framework
		altman,ryan,2008

		NuclearData
		==========================================
			interface ensures processing libs
			of methods required for general
			data building.
	*/

	interface iCacher 
	{
		//function isCached();
		public function cache();
		public function uncache();
		public function cacheName();
		public function type();
	}

	interface iQuery
	{
		public function query();
	}

	interface iXML
	{
		//function rootName();
		//function rootAttributes();
	}

	interface iBuilder
	{
		public function compose( &$row );
		public function append( &$node, $element );
		public function shift( &$node, &$queue=null, &$attrs=null );
		public function doShift();

		//function open();
		//function node();
		//function build();
	}


        interface iSingleton
        {
            public static function getInstance();
            public static function setInstance( &$object );
        }

?>
