<?php
  
  /*
    /nuclear/accounts/destroy
    (email)
  */

  require_once( 'abstract.callwrapper.php' );

  class postNuclearAccountsDestroy extends CallWrapper
  {
    protected function initJSON()
    {
      //
      // include userpost lib
      require_once('lib.userpost.php');

      //
      // include the json
      $o = new JSON( $this->time );

      $email = $this->call->email;

      if( !$email )
	throw new Exception("Please provide a valid email address", 4);

      $resp = UserPost::accountDestroyRequest( $email );

      if( $resp[0] )
	$o->status = "ok";
      else
	$o->status = "error";

      //
      // log the user out
      if( $resp[0] )
	Sessions::killSession();

      $o->message = $resp[1];
      return $o;

    }

    protected function initXML()
    {
      $this->initJSON();
    }
  }

  return postNuclearAccountsDestroy;

?>
