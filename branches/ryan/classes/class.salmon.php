<?php

    /*
        nuclear.framework
        altman,ryan,2010

        Salmon
        ================================
            Salmon class wraps protocol
            signing and verification.
            Depends on SSL.

    */

    require_once('class.ssl.php');

    class Salmon
    {
        private $ssl;

        function __construct()
        {
            $this->ssl = SSL::getInstance();
        }

        public function prefix()
        {
            return pack("H*","3031300d060960864801650304020105000420");
        }

        public function emsa( $key, $hash )
        {
            // clean and measure public key
            $k      = strlen(str_replace("\n","",preg_replace('/^\-+[ \w]+\-+/m', '', $k)));

            // get prefix
            $prefix = $this->prefix();

            // compute fill length
            $f      = $k - (strlen($prefix . $hash)) - 3;

            return "\x00" . "\x01" . str_repeat("\xFF", $f) . "\x00" . $prefix . $hash;
        }

        public function sign( $keys, $hash )
        {

            $crypted    = $this->ssl->encrypt(
                            $keys['private'],
                            $this->emsa( $keys['public'], $hash )
                         );

            return base64url_encode( $crypted );
        }

        public function compute( $keys, $signature )
        {
            $emsa      = $this->ssl->decrypt(
                            $keys['public'],
                            base64url_decode( $signature )
                         );

            return $emsa;
        }

        public static function verify( $keys, $signature, $data, $data_type, $encoding, $alg )
        {
            // fabricate the emsa
            $emsa   = $this->emsa(
                        $keys['public'],
                        hash("sha256", "{$data}.{$data_type}.{$encoding}.{$alg}") );

            if( strcmp( $emsa, $this->compute( $keys, $signature ) ) == 0 )
            {
                return true;
            }

            return false;
        }
    }

?>