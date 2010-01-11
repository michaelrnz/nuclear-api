<?php

	/*
		nuclear.framework
		altman,ryan,2008

		URI Driver - abstract class
		=================================================
			provides url driven templating
			extend for full url breakdown
	*/

	abstract class URIDriver
	{
		//
		// get-set value container
		private $innerValues;

		//
		// 
		protected $requestURI;
		protected $segments;

		//
		// parent contructor
		// segments designates the identifying labels of a url structure
		//
		function __construct( $uri=false, $segments=array('a','b','c'), $auto_parse=true )
		{
			//
			// init inner value array
			$this->innerValues = new ObjectContainer();

			//
			// assign request uri for later use
			$this->requestURI = $this->clean ( $uri ? $uri : $_SERVER['REQUEST_URI'] );

			//
			// generic segment structure
			$this->segments = $segments;

			//
			// initiate parsing
			if( $auto_parse && strlen($this->requestURI)>0 )
			{
				$this->parse();
			}
		}

		//
		// basic get-set via ObjectContainer
		//
		function __get($f)
		{
			return $this->innerValues->$f;
		}
		function __set($f,$v)
		{
			$this->innerValues->$f = $v;
		}

		//
		// simple clean
		//
		protected function clean($u)
		{
			return preg_replace( array('/^\/+/','/\/+$/'), array('',''), urldecode($u) );
		}

		//
		// extend for custom parsing
		// very simple explode on /
		// match to segment fields
		//
		protected function parse()
		{
			$this->parseTail( $this->requestURI );
		}

		//
		// takes string in which to parse into segments
		// allows offset when previous segments have been set
		//
		protected function parseTail( $tail, $offset=0 )
		{
			//
			// limit to segments
			$segs = count($this->segments) + $offset;

			//
			// default parsing by /
			$ex = explode('/', $tail, $segs);

			//
			// make judgement on size
			$size = $segs>count($ex) ? count($ex) : $segs;

			//
			// assign to instance
			for( $a=$offset; $a<($size+$offset); $a++ )
			{
				$f = $this->segments[$a];
				$this->$f = $ex[$a - $offset];
			}
		}

		//
		// determine if field is static of variable
		//
		protected function isStaticField($f)
		{
			return true;
		}


		//
		// include various template sections
		// based on parsed uri structure
		// format would be section.static1.static2. ... staticN.php
		//
		public function includer( $section, $static_fields=2, $fill=false )
		{
			//
			// open return array
			$inc_array = array();
			$inc_array[] = $section;

			$a=0;
			foreach( $this->segments as $s )
			{
				// break on static field limit
				if( $static_fields==0 ) break;

				// break when segment is false
				if( $this->$s === false ) break;

				if( $this->isStaticField( $s ) )
				{
					// assign to template
					$inc_array[] = $this->$s;

					// dec statics
					$static_fields--;
				}
				$a++;
			}

			if( $fill && $static_fields>0 && count($inc_array)==$a )
			{
				$inc_array[] = $fill;
			}

			// close
			$inc_array[] = "php";

			// return
			return implode('.', $inc_array);
		}

		//
		// generic keyword gen from url structure
		//
		public function keywords()
		{
			$kw = array();
			foreach( $this->segments as $f )
				if( $v = $this->$f ) array_push($kw, $v);

			return str_replace('"','', implode(', ',$kw));
		}

		function __toString()
		{
			// MOD: for html
			return preg_replace('/&/', '&amp;', $this->innerValues->__toString());
		}
	}
?>
