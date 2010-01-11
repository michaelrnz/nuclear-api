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
      else if( is_numeric($this->call->user_id) )
      {
        $user = new Object();
	$user->id = intval($this->call->user_id);
	$user->name = $this->call->user_name;

	return $user;
      }

      if( $force )
        throw new Exception("Missing identified user", 5);

      return null;
    }
  }

?>
