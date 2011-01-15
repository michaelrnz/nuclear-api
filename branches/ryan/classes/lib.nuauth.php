<?php
  
  /*
    Nuclear API Auth
  */

  require_once('lib.keys.php');

  class NuAuth
  {
    public static function generate( $user_name, $timestamp=false, $app_key=false )
    {
      // generally hash( lower(name) . $app-key . '-' . $ts )
    }
  }

?>
