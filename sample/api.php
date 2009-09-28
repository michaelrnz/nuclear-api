<?php
	$path = array(
		"/home/nuclear",
		"/home/user/www/example"
		);

	set_include_path( get_include_path() . PATH_SEPARATOR . implode( PATH_SEPARATOR, $path ) );

	//error_reporting( E_ERROR | E_PARSE );

	include('class.api.php');

	$a = new API();
?>
