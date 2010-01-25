<?php

	/*
		nuclear.framework
		altman,ryan,2008

		MySQL connection
		========================================
			connection class initialized for
			mysql queries
	*/

	class MySQLConnection
	{
		public static  $c = null;

		//
		// instantiate connection
		public static function init($u, $p, $h, $db)
		{
			if( is_null(MySQLConnection::$c) )
			{
				//
				// connect
				if( !($_c = mysql_pconnect( $h, $u, $p)) )
				{
					throw new Exception('Unable to connect to the database: ' . mysql_error());
				}

				//
				// select db
				if( $db ) mysql_select_db($db, $_c);

				MySQLConnection::$c = $_c;
			}
		}
	}

?>
