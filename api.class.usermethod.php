<?php
  
  /*
    UserMethod extends CallWrapper
    requires a paramertized user
  */

  require_once('abstract.callwrapper.php');

  abstract class apiUserMethod extends CallWrapper
  {
    function getUser( $force=true )
    {
      if( isset($GLOBALS['USER']) && is_object($GLOBALS['USER']) )
      {
        return $GLOBALS['USER'];
      }

      if( $force )
        throw new Exception("Missing identified user", 5);

      return null;
    }
  }

?>
