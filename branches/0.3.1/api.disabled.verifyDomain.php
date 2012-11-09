<?php
	
	/*
		nuclear.framework
		altman,ryan,2008

		Verification API
		=================================================
			verification of domain, generic
	*/

	require_once( 'class.json.php' );
	class postVerifyDomain extends CallWrapper
	{
		protected function initJSON()
		{
			//
			// include the json
			$o = new JSON( $this->time );

			require_once( 'lib.verification.php' );

			if( Verification::domain( $this->call ) )
			{
				$this->json->valid = 1;
				$this->json->msg = "Your domain has been verified.";
			}
			else
			{
				$this->json->valid = 0;
				$this->json->msg = "Your verification was not found on the given domain";
			}

			return $o;

		}

		protected function initXML()
		{
			return $this->initJSON();
		}
	}

	return postVerifyDomain;

?>
