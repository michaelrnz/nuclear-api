<?php
    
    /**
     *
     * OAuth Register App
     * Register an application
     *
    **/

    require_once('api.class.userauthmethod.php');
    require_once('class.ConsumerManager.php');

    class postOAuthRegisterApp extends NuUserAuthMethod
    {
        protected function build()
        {
            $auth   = $this->getAuth();
            
            if( $auth->auth_type != 'cookie' )
                throw new Exception("Remote registration of Application denied");

            $name   = $this->call->app_name;
            $domain = $this->call->app_domain;
            $cb     = $this->call->app_callback;

            if( !$domain )
                throw new Exception("Missing app_domain");

            if( !$name )
                throw new Exception("Missing app_name");

            if( !$cb )
                throw new Exception("Missing app_callback");

            $mngr   = ConsumerManager::getInstance();

            $token  = $mngr->register( $auth->id, $name, $domain, $cb );

            $token->name = $name;
            $token->domain = $domain;
            $token->callback = $cb;

            return $token;
        }
    }

    return "postOAuthRegisterApp";

?>
