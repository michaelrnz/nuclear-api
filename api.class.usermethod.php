<?php
  
  /*
    UserMethod extends CallWrapper
    requires a paramertized user
  */

  require_once('abstract.callwrapper.php');

  abstract class apiUserMethod extends CallWrapper
  {
    function getUser()
    {
      if( isset($GLOBALS['USER']) && is_object($GLOBALS['USER']) )
      {
        return $GLOBALS['USER'];
      }
      return null;
    }
  }

?>
