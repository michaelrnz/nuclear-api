<?php
    
    require_once('api.class.taguridestroy.php');

    class postTagURIDestroyJSON extends TagURIDestroy
    {
        protected function build()
        {
            $id = $this->process();

            $resp = new Object();

            if( $id>0 )
            {
                $resp->status   = "ok";
                $resp->id       = $id;
                $resp->message  = "Taguri {$id} destroyed";
            }
            else
            {
                $resp->status   = "error";
                $resp->message  = "Taguri not destroyed";
            }

            return $resp;
        }
    }

    return "postTagURIDestroyJSON";

?>
