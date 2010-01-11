<?php
	
	/*
		nuclear.framework
		Altman,Ryan,2009
		===============================
		CacheControl, informs cache lib
		of directories
	*/

	class CacheControl extends Object
	{
		private $object_dir;
		private $xml_dir;
		private $html_dir;
		private $tmp_dir;

		function __construct( $objs, $xml, $html, $tmp)
		{
			$this->object_dir = $objs;
			$this->xml_dir = $xml;
			$this->html_dir = $html;
			$this->tmp_dir = $tmp;
		}

		function __get( $f )
		{
			switch( $f )
			{
				case 'object': return $GLOBALS['CACHE'] . $this->object_dir;
				case 'xml': return  $GLOBALS['CACHE'] . $this->xml_dir;
				case 'html': return $GLOBALS['CACHE'] . $this->html_dir;
				case 'tmp': return $GLOBALS['CACHE'] . $this->tmp_dir;
				default: return $GLOBALS['CACHE'];
			}
		}
	}
?>
