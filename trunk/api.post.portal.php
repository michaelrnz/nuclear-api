<?php

      /*
        nuclear.framework
        altman,ryan,2010

        Portal
        ================================
            Federated endpoint

    */

    require_once( 'abstract.apimethod.php' );

    class postPortal extends NuclearAPIMethod
    {
        protected function build()
        {
            $mode   = $this->call->mode;
            $party  = $this->call->party;
            $data   = $this->call->data;


        }
    }

    return "postPortal";

?>