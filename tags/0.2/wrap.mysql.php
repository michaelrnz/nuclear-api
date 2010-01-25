<?php
	
	/*
		nuclear.framework
		altman,ryan,2008

		MySQL query wrapper
		=====================================
			basic query methods, allows 
			exception throwing
	*/

	class WrapMySQL
	{
	  public static function id( $str, $errmsg=false, $errcode=7 )
	  {
	    $r = mysql_query($str);
	    if( !$r && $errmsg ) throw new Exception($errmsg .": ". mysql_error(), $errcode);
	    return mysql_insert_id();
	  }

		public static function void( $str, $errmsg=false, $errcode=7 )
		{
			$r = mysql_query($str);
			if( !$r && $errmsg ) throw new Exception($errmsg .": ". mysql_error(), $errcode);
		}

		public static function &q( $str, $errmsg, $errcode=7 )
		{
			if( !($r = mysql_query($str)) ) throw new Exception($errmsg .": ". mysql_error(), $errcode);
			return $r;
		}

		public static function affected( $str, $errmsg=false )
		{
			self::void( $str, $errmsg );
			return mysql_affected_rows();
		}

		public static function single( $str, $errmsg )
		{
			$r = self::q( $str, $errmsg );
			if( $r )
				return mysql_fetch_array( $r );
			return null;
		}
	}
?>
