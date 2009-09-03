<?php

	/*
		nuclear.framework
		altman,ryan,2008

		global variable funcs
		==================================
			simple functions related
			to globals, and code flow

	*/

  function &nuXmlChars( &$str,$mode=0)
  {
    $rstr= $str;
    if( $mode==1 )
    {
      $rstr = preg_replace(
	array('/&#38;|&amp;/', '/&#60;|&lt;/', '/&#62;|&gt;/'),
	array('&', '<', '>'),
	$str);

    }
    else
    {
      $rstr = preg_replace(
	array('/&/', '/</', '/>/'),
	array('&#38;', '&#60;', '&#62;'),
	$str);
     }
     return $rstr;
  }

  function nuTitleChars( $str )
  {
    $f = array('&', '?', '+', ' ');
    $r = array('%26', '%3F', '%2B', '+');
    return str_replace( $f, $r, $str );
  }

  function nurlclean( $str )
  {
    return str_replace( array('+',' '), array('%2B','+'), $str);
  }

	      function safe_slash($f)
	      {
		$find = array("/\\\+'/","/([^\\\])\\\([^'\\\])/");
		$rep = array("\'",'\1\\\\\\\\\2');
		return preg_replace( $find, $rep, str_replace("'","\'",$f) );
	      }

	function old_safe_slash( $f )
	{
		// first delimit all ', then replace multi \
		//
		//return preg_replace($find, $rep, ,str_replace("'","\'",$f));
		return preg_replace("/\\\+'/","\'",str_replace("'","\'",$f));
	}

	function safe_unslash( $f )
	{
		return str_replace("\'", "'", $f);
	}

	function includer( $files )
	{
		if( !is_array($files) )
			$files = array($files);

		foreach( $files as $f )
		{
			include($f);
		}
	}

	function passive_include( $f )
	{
		if( file_exists( $f ) )
		{
			return include( $f );
		}
		return false;
	}

	function set_global($id, &$data)
	{
		$GLOBALS[$id] = $data;
	}

	function &get_global($id)
	{
		if( isset($GLOBALS[$id]) )
		{
			return $GLOBALS[$id];
		}
		return false;
	}

	function get_server($id)
	{
		return isset($_SERVER[$id])?$_SERVER[$id]:false;
	}

	function &get_session($id)
	{
		if( isset($_SESSION[$id]) )
		{
			return $_SESSION[$id];
		}
		return false;
	}

	function &GET($f)
	{
		if( isset($_GET[$f]) && strlen($_GET[$f])>0 )
		{
			return $_GET[$f];
		}
		return false;
	}

	function &POST($f)
	{
		if( isset($_POST[$f]) )
		{
			return $_POST[$f];
		}
		return false;
	}

	//
	// setup global directories
	//

	// home defined in application.globals
	$home = $GLOBALS['HOME'];

	// assigns nuclear director, but not used
	$GLOBALS['NUCLEAR']=$home. '/nuclear/';
	$GLOBALS['XSL']=$home. '/xsl/';
	$GLOBALS['CACHE']=$home. '/cache/';
	$GLOBALS['ATIME']= microtime(true);

?>
