<?php

        // application specific global definitions
        // required for nuclear framework

	class NuclearGlobals
	{
	  protected $settings;

	  function __construct( $file )
	  {
	    if( file_exists( $file ) )
	    {
	      $xml = new DOMDocument('1.0','utf-8');
	      $xml->load( $file );

	      $this->settings = $xml;
	      $this->assign($xml);
	    }
	    else
	    {
	      throw new Exception("Running without globals");
	    }
	  }

	  private function assign($xml)
	  {
	    $globals = $xml->getElementsByTagName('global');
	    foreach( $globals as $g )
	    {
	      if( $name = $g->getAttribute('name') )
	      {
	        $GLOBALS[$name] = $g->textContent;
	      }
	    }
	  }
	}

?>
