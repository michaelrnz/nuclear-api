<?php

    require_once('abstract.apimethod.php');

    class getTestDomainResolver extends NuclearAPIMethod
    {
        protected function build()
        {
            echo "one";

            return new JSON();
        }
    }

    return "getTestDomainResolver";

?>