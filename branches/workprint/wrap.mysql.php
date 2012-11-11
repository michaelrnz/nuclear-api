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
            return Database::getInstance()->id( $str, $errmsg, $errcode );
        }

		public static function void( $str, $errmsg=false, $errcode=7 )
		{
            Database::getInstance()->void( $str, $errmsg, $errcode );
		}

		public static function &q( $str, $errmsg, $errcode=7 )
		{
            return Database::getInstance()->execute( $str, $errmsg, $errcode );
		}

		public static function affected( $str, $errmsg=false )
		{
            return Database::getInstance()->affected( $str, $errmsg, $errcode );
		}

		public static function single( $str, $errmsg )
		{
            return Database::getInstance()->single( $str, $errmsg, $errcode );
		}
	}
?>
