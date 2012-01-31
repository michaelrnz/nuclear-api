<?php
    
    require_once( 'abstract.callwrapper.php' );

    class postVerifyPassword extends CallWrapper
    {
        protected function initJSON()
        {
            //
            // include userpost lib
            require_once('lib.userpost.php');

            //
            // include the json
            $o = new JSON( $this->time );

            $resp = UserPost::verifyResetPassword( $this->call );

            $o->status = $resp[0] ? "ok" : "error";
            $o->message = $resp[1];

            return $o;

        }

        protected function initXML()
        {
            return $this->initJSON();
        }
    }

    return "postVerifyPassword";

?>
