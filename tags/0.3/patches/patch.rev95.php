<?php

    function unpack_token( $token )
    {
        $data =  unpack("H*", base64_decode( str_replace( '_', '/', $token ) ) );
        if( isset($data[1]) )
            return $data[1];

        return "";
    }
  
    $host = "localhost";
    $db   = "nu_fed0";
    $user = "ryan";
    $pass = "twistabit$1";

    mysql_select_db( $db, mysql_connect($host,$user,$pass) );

    /*
        restructured tables

        nuclear_userkey (key CHAR => auth BINARY)
        nuclear_api_auth (auth_key CHAR => auth BINARY)

    */

    $api_auth_result = mysql_query("select user, id, auth_key from nuclear_api_auth") or die("Cannot select nuclear_api_auth");

    $tuples = array();

    if( mysql_num_rows($api_auth_result)>0 )
    {
        while( $row = mysql_fetch_row($api_auth_result) )
        {
            $tuples[] = "({$row[0]}, {$row[1]}, UNHEX('". unpack_token( $row[2] ) ."'))";
        }
    }

    if( count($tuples) )
    mysql_query("insert into nuclear_api_auth (user, id, auth) values ". implode(",", $tuples) ." on duplicate key update auth=values(auth);") or die("Cannot insert nuclear_api_auth");

    unset($tuples);


    $api_auth_result = mysql_query("select id, pass from nuclear_userkey") or die("Cannot select nuclear_userkey");

    $tuples = array();

    if( mysql_num_rows($api_auth_result)>0 )
    {
        while( $row = mysql_fetch_row($api_auth_result) )
        {
            $tuples[] = "({$row[0]}, UNHEX('". unpack_token( $row[1] ) ."'))";
        }
    }

    if( count($tuples) )
    mysql_query("insert into nuclear_userkey (id, auth) values ". implode(",", $tuples) ." on duplicate key update auth=values(auth);") or die("Cannot insert nuclear_api_auth");

    unset($tuples);
?>
