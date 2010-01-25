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

            $o->valid = $resp[0] ? 1 : 0;
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
