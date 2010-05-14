<?php

    /*
        nuclear.framework
        altman,ryan,2010

        SSL
        ================================
            singleton for user ssl keys,
            signatures, and verification.
            Can be used for Salmon magic.

    */

    require_once('interface.nuclear.php');

    class SSL implements iSingleton
    {
        private static $_instance;

        public static function getInstance()
        {
            if( is_null(self::$_instance) )
                self::$_instance = new SSL();
            return self::$_instance;
        }

        public function createKeys( $config = array('digest_alg'=>'sha256', 'private_key_bits'=>512) )
        {
            $keys   = array();

            // create with openssl module
            $res    = openssl_pkey_new( $config );

            // acquire public key
            $pub    = openssl_pkey_get_details( $res );
            $keys['public']     = $pub['key'];

            // acquire private key
            openssl_pkey_export( $res, $priv );
            $keys['private']    = $priv;

            // free the resource
            openssl_pkey_free( $res );

            return $keys;
        }

        public function encrypt( $key, $signature )
        {
            $res    = openssl_get_privatekey( $key );

            openssl_public_encrypt(
                $data, $crypted, $res );
            openssl_pkey_free( $res );

            return $crypted;
        }

        public function decrypt( $key, $data )
        {
            $res    = openssl_get_publickey( $key );

            openssl_public_decrypt(
                $data, $decrypted, $res );
            openssl_pkey_free( $res );

            return $decrypted;
        }

    }

?>