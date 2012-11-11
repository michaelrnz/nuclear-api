<?php
	/*
		nuclear.framework
		altman,ryan,2008

		NuclearXMLData
		==========================================
			data building in xml format
	*/

	require_once('abstract.datacacheobject.php');
	require_once('class.xmlcontainer.php');

	abstract class NuclearXMLDataObject extends NuclearDataCacheObject implements iXML
	{
		//
		// override, type is xml
		protected $dataType = "xml";
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
			$time = $this->request->time;
			$this->object = new XMLContainer("1.0","utf-8",$time);

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
			$this->object->appendChild( $r );
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
