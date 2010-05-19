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

    return "getWebFingerXRD";

?>