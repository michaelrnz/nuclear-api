<?php
  
  /*
    /nuclear/accounts/destroy/verify( $user, $hash )
  */

  require_once( 'abstract.callwrapper.php' );

  class postNuclearAccountsDestroyVerification extends CallWrapper
  {
    protected function initJSON()
    {
      //
      // include userpost lib
      require_once('lib.userpost.php');

      //
      // include the json
      $o = new JSON( $this->time );

      $resp = UserPost::accountDestroyVerification( $this->call );

      if( $resp[0] )
	$o->status = "ok";
      else
	$o->status = "error";

      $o->message = $resp[1];

      return $o;

    }

    protected function initXML()
    {
      return $this->initJSON();
    }
  }

  return postNuclearAccountsDestroyVerification;

?>
