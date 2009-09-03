<?php
	
	/*
		nuclear Framework
		Altman, Ryan - 2008

		API JSON parent class
	*/

	//
	// requirements
	if( !class_exists('Object') )
		require( $GLOBALS['PHP'] . 'class.object.php' );
	
	//
	// JSON format API return
	//
	class apiJSON extends JSON
	{
		protected $gid;
		protected $uid;
		protected $call;

		function __construct($globalTime=false,$output=true)
		{
			parent::__construct($globalTime);

			//
			// set call
			$this->call = $GLOBALS['APICALL'];

			//
			// self process
			$this->_process();

			//
			// output
			if( $output)
			{
				echo $this;
			}
		}

		//
		// for extending
		protected function _process(){}
	}

?>
