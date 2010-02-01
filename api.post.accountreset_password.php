<?php
    
    require_once( 'abstract.callwrapper.php' );

    class postResetPassword extends CallWrapper
    {
        protected function initJSON()
        {
            //
            // include userpost lib
            require_once('lib.userpost.php');

            //
            // include the json
            $o = new JSON( $this->time );

            $email = $this->call->email;

            if( !$email )
            {
              $o->status = "error";
              $o->message = "Please provide a valid email";
              return $o;
            }

            $resp = UserPost::resetPassword( $email );

            $o->status = "ok";
            $o->message = $resp[1];
            return $o;

        }

        protected function initXML()
        {
            $this->initJSON();
        }
    }

    return "postResetPassword";

?>
