<?php
	/*
		nuclear.framework
		altman,ryan,2008

		NuclearXMLWrapper
		==========================================
			data building in xml format
			Wrap DOMDoc to enable stringing

		TODO
		copy Exception handling DOM Document
		implement toString()
		expire this wrapper
	*/

	class NuclearXMLWrapper
	{
		//
		// xml node is root
		protected $doc;
		protected $root;

		function __construct( &$doc )
		{
			$this->doc = $doc;
		}

		function __toString()
		{
			if( is_object($this->doc) && get_class($this->doc)=='DOMDocument' )
			{
				return $this->doc->saveXML();
			}
			return false;
		}

	}

?>
