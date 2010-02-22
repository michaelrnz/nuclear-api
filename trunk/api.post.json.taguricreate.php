<?php
    
    require_once('api.class.taguricreate.php');

    class postTagURICreateJSON extends TagURICreate
    {
        protected function build()
        {
            $tag = $this->process();

            $resp = new Object();

            if( $tag )
            {
                $resp->status   = "ok";
                $resp->id       = $tag->id;
                $resp->taguri   = $tag->urlencode();
                $resp->message  = "Taguri created";
            }
            else
            {
                $resp->status   = "error";
                $resp->taguri   = $tag->__toString();
                $resp->message  = "Taguri not created";
            }

            return $resp;
        }
    }

    return "postTagURICreateJSON";

?>
