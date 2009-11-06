<?php
	/*
		nuclear.framework
		altman,ryan,2008

		XMLContainer
	*/

	require_once('class.domdocumentexceptor.php');

	class XMLContainer extends DOMDocumentExceptor
	{
		private static $processor = null;
		private $xsl;
		private $loaded;
		private $time;
		private $root;

		function __construct($version=false, $encoding=false, $time=false)
		{
			$this->time = $time;
			parent::__construct($version,$encoding);
			$this->root = null;
		}

		function __toString()
		{
			try
			{
				if( !is_null( $this->xsl ) )
				{
					// string to the transform
					return $this->transformToXML();
				}
				else
				{
					// string to the xml value
					if( $this->time && $this->root!=null )
					{
						$this->root->setAttribute("ms", number_format((microtime(true)-$this->time)*1000,5));
					}

					// check headers
					if( !headers_sent() )
					{
						header('Content-type: application/xml');
					}

					return $this->saveXML();
				}
			}
			catch( Exception $e )
			{
				return "";
			}
		}

		//
		// initializes the processor if needed
		//
		private static function initProcessor()
		{
			if( is_null(self::$processor) )
			{

				self::$processor = new XSLTProcessor();
				self::$processor->registerPHPFunctions();
			}
		}

		//
		// private loads stylesheet into processor
		//
		private function loadStylesheet( $x )
		{
			if( !is_null( $x ) && is_a( $x, "DOMDocument" ) )
			{
				self::initProcessor();
				self::$processor->importStylesheet( $x );
			}
			else
			{
				throw new Exception("XMLContainer: stylesheet must be DOMDocument");
			}
		}

		//
		// public add of stylesheet as DOMDoc or string filename
		//
		public function addStylesheet( $x )
		{
			//
			// free the local
			if( !is_null( $this->xsl ) )
			{
				unset( $this->xsl );
			}

			if( is_object( $x ) && is_a( $x, 'DOMDocument' ) )
			{
				// attached to variable
				$this->xsl = $x;
			}
			else if( strlen( $x )>0 )
			{
				// instantiate
				$this->xsl = new DOMDocumentExceptor('1.0','utf-8');
				$this->xsl->load( $x );
			}
		}


		//
		// transparency to XSLTProc methods
		//
		public function transformToURI( $uri )
		{
		  $this->loadStylesheet( $this->xsl );
		  self::$processor->transformToURI( $this, $uri );
		}

		public function transformToXML()
		{
			if( $this->xsl->hasChildNodes() )
			{
			$this->loadStylesheet( $this->xsl );
			return self::$processor->transformToXML( $this );
			}
			return "";
		}

		public function transformToDoc()
		{
			if( $this->xsl->hasChildNodes() )
			{
			$this->loadStylesheet( $this->xsl );
			return self::$processor->transformToDoc( $this );
			}
			return "";
		}

		//
		// root proc
		//
		public function appendRoot( &$node )
		{
			$this->root = $node;
			$this->appendChild( $node );
		}
	}

?>
