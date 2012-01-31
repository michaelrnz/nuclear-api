<?php
    
    /**
     *
     * OAuth Update App
     * Update an application's details
     *
    **/

    require_once('api.class.userauthmethod.php');
    require_once('class.ConsumerManager.php');

    class postOAuthUpdateApp extends NuUserAuthMethod
    {
        protected function build()
        {
            $auth   = $this->getAuth();
            
            if( $auth->auth_type != 'cookie' )
                throw new Exception("Remote registration of Application denied");

            $id     = $this->call->app_id;
            $name   = $this->call->app_name;
            $domain = $this->call->app_domain;
            $cb     = $this->call->app_callback;

            $mngr   = ConsumerManager::getInstance();
            $app    = $mngr->update( $id, $auth->id, $name, $domain, $cb );

            return $app;
        }
    }

    return "postOAuthUpdateApp";

?>
