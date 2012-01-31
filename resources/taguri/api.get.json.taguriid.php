<?php
    
    require_once( 'api.class.taguriid.php' );

    class getTagURIIdJSON extends TagURIId
    {
        protected function build()
        {
            $tag = $this->process();

            $resp = new JSON();
            $resp->status = "ok";

            if( $tag->id )
            {
                $resp->id = $tag->id;
            }
            else
            {
                $resp->id = null;
                $resp->message = "Taguri not found";
            }

            return $resp;
        }
    }

    return "getTagURIIdJSON";

?>
