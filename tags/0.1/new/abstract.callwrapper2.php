<?php
	
	/*
		nuclear.framework
		altman,ryan,2008

		CallWrapper
		====================================
			abstract class for API calls
	*/

	abstract class CallWrapper2
	{
		protected static $globalField = "APICALL";
		protected $call;
		protected $response;
		protected $format;
		protected $time;
		protected $output;

		function __construct($time=false,$format="json",$output=true)
		{
			$this->call = $GLOBALS[self::$globalField];
			$this->time = $time;
			$this->format = $GLOBALS['API_FORMAT'] ? $GLOBALS['API_FORMAT'] : $format;

			$this->process();

			if( $output )
			{
				echo $this;
			}
		}

		function __toString()
		{
			return $this->response->__toString();
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
		  $this->response = $this->build();
		}

		//
		// initializations throw exceptiosn when missing overrids
		//
		protected function build()
		{
			throw new Exception("API call does not buil", 5);
		}
	}

?>
