<?php

	/*
		nuclear.framework
		altman,ryan,2008

		MySQL connection
		========================================
			connection class initialized for
			mysql queries
	*/
    
    require_once('class.database.php');

	class MySQLConnection
	{
		public static function init($u, $p, $h, $db)
		{
            $dbo = Database::getInstance();
            
			if( !($c = mysql_connect( $h, $u, $p)) )
			{
                throw new Exception('Unable to connect to the database: ' . mysql_error());
            }

			if( $db ) 
                mysql_select_db($db, $c);
            
            $dbo->addConnection( $c );
		}
	}

?>