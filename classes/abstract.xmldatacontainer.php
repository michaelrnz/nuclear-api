<?php
	/*
		nuclear.framework
		altman,ryan,2008

		NuclearXMLData
		==========================================
			data building in xml format
	*/

	require_once('abstract.datacontainer.php');
	require_once('class.xmlcontainer.php');

	abstract class NuclearXMLDataContainer extends NuclearDataContainer
	{
		//
		// root table of xml
		protected $rootTag = "data";

		//
		// xml node is root
		protected $root;

		//
		// override, open on XML object means to create DOM
		//
		protected function open()
		{

			//
			// instance the document
			$this->object = new XMLContainer("1.0","utf-8",$this->request->ATIME);

			// create a root
			$r = $this->object->createElement( $this->rootTag );
			$this->root = $r;

			// get the root attributes (dynamic)
			$atts = $this->rootAttributes();

			// set attributes for root
			foreach( $atts as $a=>$v )
			{
				$r->setAttribute( $a, $v );
			}

			//
			// append and format
			$this->object->appendRoot( $r );
			$this->object->formatOutput = true;

			return true;

		}

		//
		// override, wrap parent build
		//
		protected function build()
		{
			if( parent::build() )
			{
				$this->object->normalize();
				return true;
			}
			return false;
		}

		public function append( &$node, $element )
		{
			$node->appendChild( $element );
		}

		//
		// override, using root as node
		//
		protected function firstNode()
		{
			return $this->root;
		}

		//
		// iXML implement
		//
		protected function rootName()
		{
			return self::$rootTag;
		}

		protected function rootAttributes()
		{
			return array();
		}

	}

?>
