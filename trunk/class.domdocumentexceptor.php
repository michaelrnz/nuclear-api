<?php

	/*
		nuclear.framework
		altman,ryan,2008

		DOMDocumentExceptor
		=============================================
			extends the DOMDocument to allow
			exception throw-catch
	*/

	//
	// necessary for function
	//
	libxml_use_internal_errors(true);

	class DOMDocumentExceptor extends DOMDocument
	{
		//
		// call parent then handle error
		public function load( $xml, $version=false )
		{
			if( !@parent::load( $xml, $version ) )
			{
				self::handleError();
			}
		}

		//
		// call parent then handle error
		public function loadXML( $xml, $options=false )
		{
			if( !@parent::loadXML( $xml, $options ) )
			{
				self::handleError();
			}
		}

		//
		// handleError, by checking and format
		private static function handleError()
		{
			$errors = libxml_get_errors();

			$bubble = "";
			foreach( $errors as $e )
			{
				$bubble .= self::formatXmlError($e);
			}

			//
			// clear buffer
			libxml_clear_errors();

			//
			// throw exception
			throw new Exception( "libxml Exception: " . $bubble );
		}
		
		//
		// checks the error level and 
		private static function formatXmlError( $error )
		{
			$rv = "\n";
			switch( $error->level )
			{
				case LIBXML_ERR_WARNING:
					$rv .= "Warning $error->code: ";
					break;
				case LIBXML_ERR_ERROR:
					$rv .= "Error $error->code: ";
					break;
				case LIBXML_ERR_FATAL:
					$rv .= "Fatal Error $error->code: ";
					break;
				default:
					$rv .= "Unknown Error $error->code: ";
					break;
			}

			$rv .= ", Line: $error->line, Column: $error->column";

			if( $error->file )
			{
				$rv .= ", File: $error->file";
			}

			return $rv;
		}
	}

?>
