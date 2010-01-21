<?php
    
    /*
        nuclear.framework
        altman,ryan,2008

        LoginAPI call
        =======================================
            logins in, starts session
            returns session id
    */

    require_once( 'abstract.callwrapper.php' );

    class postSessionCreate extends CallWrapper
    {
      
        private function login()
        {
            // check for already logged
            if( isset($_SESSION['USER_CONTROL']) && $_SESSION['USER_CONTROL']['id']>0 )
                throw new Exception("User already logged in", 0);

            // include the lib
            require_once( 'lib.userlog.php' );
            return UserLog::in( $this->call );
        }

        protected function initJSON()
        {
            $logged = $this->login();

            // make return object
            $o = new JSON( $this->time );

            if( $logged )
            {
                $o->status  = "ok";
                $o->valid   = 1;
                $o->message = "You are now logged in.";
                $o->session = $logged;
            }
            else
            {
                $o->status  = "error";
                $o->valid   = 0;
                $o->message = "Please check your credentials.";
            }

            return $o;

        }

        protected function initXML()
        {
            $logged = $this->login();

            require_once('class.xmlresponse.php');

            $resp = new XMLResponse($this->time);

            if( $logged )
            {
                $status = "ok";
                $message = "You are now logged in.";
            }
            else
            {
                $status = "error";
                $message = "Please check your credentials";
            }

            $resp->status = $status;

            if( $logged )
              $resp->session = $logged;

            $resp->append( $resp->attach("message", $message) );

            return $resp;
        }
    }

    return "postSessionCreate";

?>
