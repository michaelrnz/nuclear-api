<?php

	/*
		nuclear.framework
		altman,ryan,2008

		Text Method Library
		===================================
			various text transforms and
			checking methods
	*/

	class Text
	{
	  public static function htmlchars( $str, $mode=0 )
	  {
	    $html = array('&amp;', '&lt;', '&gt;');
	    $bare = array('&','<','>');
	    if( $mode==1 )
	    {
	      return str_replace($html, $bare, $str);
	    }
	    else
	    {
	      return str_replace($bare, $html, $str);
	    }
	  }
		public static function &xmlChars( &$str,$mode=0){
			$rstr= $str;
			if( $mode==1 ){
				$rstr = preg_replace(
				array('/&#38;|&amp;/', '/&#60;|&lt;/', '/&#62;|&gt;/'),
				array('&', '<', '>'),
				$str);
			} else {
				$rstr = preg_replace(
				array('/&/', '/</', '/>/'),
				array('&#38;', '&#60;', '&#62;'),
				$str);
			}
			return $rstr;
		}

		public static function slash(&$f)
		{
			if( preg_match('/(^\'|[^\\\\][\'\"])/', $f, $m)==1  )
			{
				return addslashes($f);
			}
			return $f;
		}

	}

?>
