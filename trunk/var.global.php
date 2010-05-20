<?php

    /*
        nuclear.framework
        altman,ryan,2008

        global variable funcs
        ==================================
            simple functions related
            to globals, and code flow
    */


/* POSSIBLY TRASH */
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

    /* END POSSIBLY TRASH */


    //
    // FROM php.net/magic_quotes
    // we don't want magic quotes
    //
    if (get_magic_quotes_gpc()) {
        function stripslashes_deep($value)
        {
            $value = is_array($value) ?
            array_map('stripslashes_deep', $value) :
            stripslashes($value);

            return $value;
        }

        $_POST = array_map('stripslashes_deep', $_POST);
        $_GET = array_map('stripslashes_deep', $_GET);
        $_COOKIE = array_map('stripslashes_deep', $_COOKIE);
        $_REQUEST = array_map('stripslashes_deep', $_REQUEST);
    }


    //
    // Typing function, utility
    // can save a regex for url testing
    //
    function isType( $group, $type )
    {
        return strpos("-|{$group}|","|{$type}|");
    }

    function is_type( $group, $type )
    {
        return strpos("-|{$group}|","|{$type}|");
    }


    //
    // Make caching directory (checks presence)
    //
    function mk_cache_dir( $dir, $mode=0775 )
    {
        if( is_dir( $dir ) ) return;
        mkdir( $dir, $mode, true );
    }


    //
    // invasive slash management
    // two-stage process
    //
    function safe_slash($f)
    {
        $find = array("/\\\+'/","/([^\\\])\\\([^'\\\])/");
        $rep = array("\'",'\1\\\\\\\\\2');
        return preg_replace( $find, $rep, str_replace("'","\'",$f) );
    }

    function safe_unslash( $f )
    {
        return str_replace("\'", "'", $f);
    }

    //
    // base64url version
    //
    function base64url_encode( $input )
    {
        return strtr( base64_encode( $input ), "+/", "-_" );
    }

    function base64url_decode($input)
    {
        return base64_decode( strtr( $input, '-_', '+/' ) );
    }



    //
    // GLOBALS get/set wrapper
    //
    function set_global( $f, &$data )
    {
        $GLOBALS[$id] = $data;
    }

    function &get_global( $f )
    {
        if( array_key_exists($f, $GLOBALS) )
        {
            return $GLOBALS[$f];
        }
        //return null; TODO
        return false;
    }

    //
    // Nuclear database prefix
    //
    function nu_db()
    {
        return get_global('NU_DB');
    }


    //
    // GET, POST, REQUEST, SESSION fetching
    //
    function GET($f)
    {
        if( array_key_exists($f, $_GET) && strlen($_GET[$f])>0 )
        {
            return $_GET[$f];
        }
        return false;
    }

    function POST($f)
    {
        if( array_key_exists($f, $_POST) )
        {
            return $_POST[$f];
        }
        return false;
    }

    function REQUEST($f)
    {
        if( array_key_exists($f, $_REQUEST) )
        {
            return $_REQUEST[$f];
        }
        return false;
    }


    //
    // real request uri, without GET
    //
    $_REAL_REQUEST_URI = false;
    function real_request_uri()
    {
        global $_REAL_REQUEST_URI;
        if( !$_REAL_REQUEST_URI )
        {
            $uri = explode('?', $_SERVER['REQUEST_URI']);
            $_REAL_REQUEST_URI = $uri[0];
        }
        return $_REAL_REQUEST_URI;
    }


    //
    // convert an xml doc to object recursively
    // requires only a few miliseconds to convert 20 fmp packets
    //
    function &xml_to_object( &$xml )
    {
        $result = new Object();

        // attribute check
        foreach( $xml->attributes as $attrName => $attrNode )
        {
            $result->$attrName = $attrNode->nodeValue;
        }

        foreach( $xml->childNodes as $node )
        {
            $key = $node->nodeName;

            // assume no more nodes
            if( strpos($key,'#')===0 )
            {
                return $node->nodeValue;
            }

            // recursive check
            if( $node->hasChildNodes() )
            {
                $value  = xml_to_object( $node );
            }
            else
            {
                $value  = $node->nodeValue;
            }

            // collision check
            if( !is_null($result->$key) )
            {
                if( !is_array($result->$key) )
                {
                    $data           = $result->$key;
                    $result->$key   = array( $data );
                }

                array_push( $result->$key, $value );
            }
            else
            {
                $result->$key = $value;
            }
        }

        return $result;
    }

    function object_to_xml( $object, $doc, $name )
    {
        $node = $doc->createElement($name);

        foreach( $object as $k=>$o )
        {
            if( is_array( $o ) )
            {
                $k_clean = preg_replace('/([^aiou])s$/', '\1', $k);
                foreach( $o as $el )
                {
                    if( is_string( $el ) )
                    {
                        $node->appendChild( $doc->createElement( $k_clean, $el ) );
                    }
                    else
                    {
                        $node->appendChild( object_to_xml( $el, $doc, $k_clean ) );
                    }
                }
            }
            else if( !is_object($o) )
            {
                $node->appendChild( $doc->createElement($k,nuXmlChars($o)) );
            }
            else
            {
                $node->appendChild( object_to_xml($o, $doc, $k) );
            }
        }

        return $node;
    }

    function array_to_xml( $array, $doc, $name, $nodeName )
    {
        $node = $doc->createElement($name);

        foreach( $array as $el )
        {
            $node->appendChild( object_to_xml( $el, $doc, $nodeName ) );
        }

        return $node;
    }

    //
    // TODO migrate ATIME to the Service abstract
    //

    $GLOBALS['ATIME']= microtime(true);

?>
