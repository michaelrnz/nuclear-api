<?php

        // application specific global definitions
        // required for nuclear framework

        $appdef = array(
                "HOME"             => "/home/user/www",
                "APPLICATION_NAME" => "Nuclear",
                "APPLICATION_SESSION"=>"example_session",
                "DOMAIN"           => "example.com",
                "REGISTRATION_MAIL"=> "registration@example.com",
                "PASSWORD_MAIL"    => "reset@example.com",
                "SUPPORT_MAIL"     => "support@example.com",
		"AUTH_MAIL"	   => "services@example.com"
        );

        foreach( $appdef as $f=>$v )
        {
                $GLOBALS[$f] = $v;
        }

?>
