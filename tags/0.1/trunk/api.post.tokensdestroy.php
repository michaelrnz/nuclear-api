<?php
  
  /*
    /api/keys/generate - Nuclear
    altman,ryan,2009
    =============================
     generate a key using SECRET
     and timestamp.

    REQUIRES AUTHORIZATION
  */

  require_once( 'abstract.callwrapper.php' );
  require_once( 'lib.keys.php');

  class postTokensDestroy extends CallWrapper
  {
    private function query( $user_id, $token_id )
    {
      return WrapMySQL::affected(
	    "delete from nuclear_api_auth where user={$user_id} && id={$token_id} limit 1;", 
	    "Error removing token");
    }

    protected function initJSON()
    {
      $resp = new JSON( $this->time );

      //
      // get user_id
      $user_id= $GLOBALS['USER_CONTROL']['id'];

      if( !$user_id )
	throw new Exception("Unauthorized", 2);

      //
      // check key count
      $token_id = intval($this->call->token_id);

      if( $token_id<=0 )
	throw new Exception("Invaid token_id.",5);

      $affected = $this->query( $user_id, $token_id );

      if( $affected )
      {
	$resp->status = "ok";
	$resp->message = "Token {$token_id} removed";
      }
      else
      {
	$resp->status = "error";
	$resp->message = "Token {$token_id} does not exist";
      }

      return $resp;
    }

    protected function initXML()
    {
      $o = $this->initJSON();
      header('Content-type: text/xml');
      echo "<response status=\"{$o->status}\" ms=\"". number_format((microtime(true) - $this->time)*1000,3) ."\"><message>{$o->message}</message></response>";
      exit();
    }
  }

  return postTokensDestroy;

?>
