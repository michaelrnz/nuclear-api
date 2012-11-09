<?php
    
    /**
     *
     * OAuth Consumers
     * List a user's apps
     *
    **/

    require_once('api.class.userauthmethod.php');
    require_once('class.ConsumerManager.php');

    class getOAuthConsumers extends NuUserAuthMethod
    {
        protected function build()
        {
            $auth = $this->getAuth();
            $mngr = ConsumerManager::getInstance();
            $list = $mngr->ownerClients( $auth->id, $this->call->show_secret==1 );
            return $list;
        }
    }

    return "getOAuthConsumers"

?>
