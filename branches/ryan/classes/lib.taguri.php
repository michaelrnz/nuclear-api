<?php

    /*
      TagURI lib
    */

    class NuTagURI
    {
        public static function authorityId( $name, $auto=false )
        {
            $name = safe_slash($name);

            $authority = WrapMySQL::single(
                            "select id from nu_authority ".
                            "where name='{$name}' limit 1",
                            "Unable to get authority id");

            if( $authority )
                return $authority[0];

            if( $auto )
            {
                $id = WrapMySQL::id(
                        "insert into nu_authority (name) ".
                        "values ('{$name}');",
                        "Unable to insert authority");

                return $id;
            }
        }
    }
?>
