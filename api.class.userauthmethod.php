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
      $user = AuthorizedUser::getInstance();

      if( is_null($user) || !$user->isLocal() )
        throw new Exception("Unauthorized", 2);

      return $user;
    }

    function getUser( $force=true )
    {
      $user = LocalUser::getInstance();

      if( is_null($user) && $force )
        throw new Exception("Unidentified user",5);

      return $user;
    }
  }

?>
