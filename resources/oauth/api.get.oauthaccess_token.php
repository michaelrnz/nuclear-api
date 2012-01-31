<?php
    
    /**
     *
     * oauth/request_token (nuclear)
     * provides method for consumers to init handshake
     *
    **/

    require_once('abstract.apimethod.php');
    require_once('lib.oauth.php');

    class getOauthAccessToken extends NuclearAPIMethod
    {
        protected function build()
        {
            $params = array();
            foreach( $this->call as $f=>$v )
            {
                $params[$f] = $v;
            }

            $req = new OAuthRequest('GET', "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], $params);

            $test_server = new OAuthServer(new NuOAuthDataStore());
            $hmac_method = new OAuthSignatureMethod_HMAC_SHA1();
            $plaintext_method = new OAuthSignatureMethod_PLAINTEXT();

            $test_server->add_signature_method($hmac_method);
            $test_server->add_signature_method($plaintext_method);
            $tok = $test_server->fetch_access_token($req);

            return (object) array("status"=>"ok", "oauth_token"=>$tok->key, "oauth_token_secret"=>$tok->secret);
        }
    }

    return "getOauthAccessToken";

?>
