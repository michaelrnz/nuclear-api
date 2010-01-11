<?php
	
	/*
		example api call
	*/

	require_once( 'abstract.callwrapper.php' );

	class getExample extends CallWrapper
	{
		protected function initJSON()
		{
			//
			// include the json
			$o = new JSON( $this->time );

			if( rand(0, 99999) % 2 == 0 )
			{
				$o->valid = 1;
			}
			else
			{
				$o->valid = 0;
			}

			return $o;
		}

		protected function initXML()
		{
			$o = $this->initJSON();
			$o->outputMessage = "No xml format for this method";
			return $o;
		}
	}

	return getExample;

?>
