<?php
	
	/*
		nuclear.framework
		altman,ryan,2008

		CallWrapper
		====================================
			abstract class for API calls
	*/

	abstract class CallWrapper
	{
		protected static $globalField = "APICALL";
		protected $call;
		protected $response;
		protected $format;
		protected $time;
		protected $output;

		function __construct($time=false,$format=false,$output=true)
		{
			$this->call = $GLOBALS[self::$globalField];
			$this->time = $time;
			$this->format = strlen($format )>0? $format : ($GLOBALS['API_FORMAT'] ? $GLOBALS['API_FORMAT'] : "json");

			$this->process();

			if( $output )
			{
				echo $this;
			}
		}

		function __toString()
		{
			if( is_callable( array($this->response,"__toString") ) )
			{
				return $this->response->__toString();
			}
			
			if( is_object( $this->response ) || is_array( $this->response ) )
			{
				return json_encode( $this->response );
			}
			
			return "";
		}

		function __get($f)
		{
			switch($f)
			{
				case 'response':
					return $this->response;

				default:
					if( $this->format == "json" )
					{
						return $this->response->$f;
					}
					return null;
			}
		}

		//
		// auto-processing for calls
		//
		private function process()
		{
			switch( $this->format )
			{
				case 'xml':
					$this->response = $this->initXML();
					break;

				case 'json':
				default:
					$this->response = $this->initJSON();
					break;
			}
		}

		//
		// initializations throw exceptiosn when missing overrids
		//
		protected function initJSON()
		{
			throw new Exception("API call does not support JSON");
		}

		protected function initXML()
		{
			throw new Exception("API call does not support XML");
		}
	}

?>
