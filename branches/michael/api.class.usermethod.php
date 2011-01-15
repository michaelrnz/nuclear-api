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
        $user = LocalUser::getInstance();

        if( is_null($user) )
        {
            if( is_object($this->call->user) )
            {
                return $this->call->user;
            }
            else if( is_numeric($this->call->user_id) )
            {
                $user = new Object();
                $user->id = intval($this->call->user_id);
                $user->name = $this->call->user_name;
	        return $user;
            }
        }
        else
        {
            return $user;
        }

        if( $force )
            throw new Exception("Missing identified user", 5);

        return null;
    }
  }

 require_once('abstract.apimethod.php');
  
  abstract class NuUserMethod extends NuclearAPIMethod
  {
    function getUser( $force=true )
    {
        $user = LocalUser::getInstance();

        if( is_null($user) )
        {
            if( is_object($this->call->user) )
            {
                return $this->call->user;
            }
            else if( is_numeric($this->call->user_id) )
            {
                $user = new Object();
                $user->id = intval($this->call->user_id);
                $user->name = $this->call->user_name;
                return $user;
            }
        }
        else
        {
            return $user;
        }

        if( $force )
            throw new Exception("Missing identified user", 5);

        return null;
    }
  }

?>
