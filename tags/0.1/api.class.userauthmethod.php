<?php
  
  /*
    UserAuthMethod extends CallWrapper
    requires an authorized user
    requires a paramertized user
  */

  require_once('abstract.callwrapper.php');

  abstract class apiUserAuthMethod extends CallWrapper
  {
    function getAuth()
    {
      if( isset($GLOBALS['USER_CONTROL']) )
      {
        $user = new Object();
	$user->id   = $GLOBALS['USER_CONTROL']['id'];
	$user->name = $GLOBALS['USER_CONTROL']['name'];

	return $user;
      }

      throw new Exception("Unauthorized", 2);
    }

    function getUser( $force=true )
    {
      if( isset($GLOBALS['USER']) && is_object($GLOBALS['USER']) )
      {
        return $GLOBALS['USER'];
      }

      if( $force )
        throw new Exception("Missing identified user",5);

      return null;
    }
  }

?>
