<?php
    
    /*
        nuclear.framework
        altman,ryan,2008

        Verification API
        ================================================
            generic verification model
    */

    require_once( 'abstract.callwrapper.php' );

    class postVerifyRegistration extends CallWrapper
    {
        protected function initJSON()
        {
            //
            // include the json
            $o = new JSON( $this->time );
            
            require_once( 'lib.verification.php' );

            //
            // proceed with process
            if( ($id = Verification::post( $this->call )) )
            {
                $o->status = "ok";
                $o->id = $id;
                $o->message = "You may now proceed to login";
            }
            else
            {
                $o->valid = "error";
                $o->message= "Your verification was invalid or expired, please register again";
            }

            return $o;

        }

        protected function initXML()
        {
            return $this->initJSON();
        }
    }

    return "postVerifyRegistration";

?>
