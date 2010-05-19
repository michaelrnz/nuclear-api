<?php

    require_once('abstract.apimethod.php');
    require_once('class.webfinger.php');
    require_once('class.events.php');
    require_once('class.xmlcontainer.php');

    class getWebFingerXRD extends NuclearAPIMethod
    {
        protected function build()
        {
            $uri    = $this->call->uri;
            $webf   = Webfinger::getInstance();
            $acct   = $webf->parse( $uri );

            if( !$acct )
                throw new WebfingerException('Invalid uri');

            $events = Events::getInstance();
            $resp   = new XMLContainer('1.0', 'UTF-8');

            //
            // append the XRD root
            //
            $xrd    = $resp->createElementNS(
                        'http://docs.oasis-open.org/ns/xri/xrd-1.0',
                        'XRD');

            $resp->appendChild($xrd);

            $xrd->appendChild(
                $resp->createElement(
                    'Subject',
                    "acct:{$acct['user']}@{$acct['domain']}"
                )
            );

            $xrd->appendChild(
                $resp->createElement(
                    'Alias',
                    "http://{$acct['domain']}/{$acct['user']}"
                )
            );

            // filter through observers with acct
            $resp   = $events->filter( 'nu_webfinger_xrd', $resp, $acct );

            return $resp;
        }
    }


    function attach_public_key( $resp, $acct )
    {
        require_once('lib.nuuser.php');
        $user_id = NuUser::userID( $acct['user'], $acct['domain'] );

        if( $user_id )
        {
            // do the magic
            require_once('lib.magic.php');
            $nuclear_magic = new NuUserMagic( $user_id, $acct['domain']==get_global('DOMAIN') );
            $href = $nuclear_magic->load()->href();

            $link   = $resp->createElement('Link');
            $link->setAttribute('rel', 'magic-public-key');
            $link->setAttribute( 'href', $href );
            $resp->firstChild->appendChild( $link );

        }
        else
        {
            throw new WebfingerException("Invalid or unknown user");
        }

        return $resp;
    }

    Events::getInstance()->attach( 'nu_webfinger_xrd', 'attach_public_key' );

    return "getWebFingerXRD";

?>