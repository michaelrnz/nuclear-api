<?php

    /*
        nuclear.framework
        altman,ryan,2008

        Verification processing
        ====================================
        based on
        Verification checking class
    */

    require_once('lib.nuuser.php');
    require_once('lib.fields.php');

    class Verification
    {

        public static function post( $d )
        {
            if( !($h = str_replace(' ','+',$d->hash)) )
                throw new Exception("Missing hash", 4);

            if( !($u = str_replace("'","",$d->user)) )
                throw new Exception("Missing user", 4);

            //
            // fixed length hash
            if( Fields::isVerification($h)==0 )
                throw new Exception("Invalid hash format", 5);

            $verified = WrapMySQL::single(
                            "select * from nuclear_verify ".
                            "where hash='$h' && user='$u' LIMIT 1;",
                            "Unable to verify user" );

            if( !$verified )
                throw new Exception("Invalid hash");

            if( strlen($verified['auth'])==0 )
                $verified['auth'] = "0";
			//
            // Add User
            $id = NuUser::add( $u, $GLOBALS['DOMAIN'], 0 );

            if( $id>0 )
            {
                try
                {

                    $q = "INSERT INTO nuclear_user (id, name, email, ts) VALUES ($id, '$u', '". $verified['email'] ."', '". $verified['ts'] ."');";
                    WrapMySQL::affected( $q, "Unable to insert user", 10 );

                    $q = "INSERT INTO nuclear_userkey (id, auth) VALUES ($id, UNHEX('". $verified['auth'] ."'));";
                    WrapMySQL::affected( $q, "Unabled to insert userkey", 11 );

                    // NOTICE userapi has been removed, user tokens

                    $q = "INSERT INTO nuclear_system (id) VALUES ($id);";
                    WrapMySQL::affected( $q, "Unabled to insert system", 13 );

                    //
                    // remove the verification
                    self::remove( $u, $h );

                    //
                    // raise action with User
                    $o          = new Object();
                    $o->id      = $id;
                    $o->name    = $u;
                    $o->email   = $verified['email'];
                    $o->user_id = $id;

                    NuEvent::action('nu_registration_verified', $o);

                    return $id;
                }
                catch( Exception $e )
                {
                    //
                    // unroll insertions
                    switch( $e->getCode() )
                    {
                        case 14:
                            mysql_query( "DELETE FROM nuclear_system WHERE id=$id LIMIT 1;" );
                        case 13:
                            mysql_query( "DELETE FROM nuclear_userapi WHERE id=$id LIMIT 1;" );
                        case 12:
                            mysql_query( "DELETE FROM nuclear_userkey WHERE id=$id LIMIT 1;" );
                        case 11:
                            mysql_query( "DELETE FROM nuclear_user WHERE id=$id LIMIT 1;" );
                        case 10:
                            mysql_query( "DELETE FROM nuclear_username WHERE id=$id LIMIT 1;" );
                            break;
                    }

                    mysql_query( "DELETE FROM nu_user WHERE id=$id LIMIT 1;" );
                }
            }

            //
            // raise action on fail
            NuEvent::action('nu_verification_failed', $d);

            return false;
        }


        public static function remove($u,$h)
        {
            return WrapMySQL::affected(
                    "delete from nuclear_verify ".
                    "where user='$u' && hash='$h' limit 1;");
        }

    }

?>