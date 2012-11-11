<?php
    
    /**
     *
     * oauth/request_token (nuclear)
     * provides method for consumers to init handshake
     *
    **/

    require_once('abstract.apimethod.php');
    require_once('lib.oauth.php');

    class getOauthAuthorize extends NuclearAPIMethod
    {
        protected function build()
        {
            $auth = AuthorizedUser::getInstance();

            if( !$auth )
                throw new Exception("Unauthorized");

            if( $auth->auth_type != "cookie" )
                throw new Exception("Cannot authorize remotely");
                
            $oauth_token    = $this->call->oauth_token;
            if( !$oauth_token )
                throw new Exception("Missing oauth_token");

            $oauth_callback = $this->call->oauth_callback;
            if( !$oauth_callback )
                throw new Exception("Missing oauth_callback");

            // authorize token for user
            //
            OAuthManager::getInstance()->authorize( $auth->id, $oauth_token );

            header("Location: {$oauth_callback}");
        }
    }

    return "getOauthAuthorize";

?>
