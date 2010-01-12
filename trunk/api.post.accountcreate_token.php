<?php
  
  /*

    /account/create_token - Nuclear
    altman,ryan,2009
    =============================
     generate a key using SECRET
     and timestamp.

    REQUIRES AUTHORIZATION

  */

  require_once( 'abstract.callwrapper.php' );
  require_once( 'lib.keys.php');

  class postCreateToken extends CallWrapper
  {

    private function maxId( $user_id )
    {
      $r = WrapMySQL::single(
	    "select max(id) from nuclear_api_auth where user=$user_id;", 
	    "Error getting max auth key");
      return $r[0];
    }

    protected function initJSON()
    {

      $resp = new JSON( $this->time );

      //
      // can default to time()
      if( ($lifetime = $this->call->lifetime) && is_numeric($lifetime) )
      {
	// minimum 600 second lifetime
	$lifetime = $lifetime < 60 ? 60 : $lifetime;

	// default expire in 10 years
	$ts = time() + $this->call->lifetime;
      }
      else
      {
	// default expire in 10 years
	$ts = time() + 315360000;
      }


      // get username
      $user = strtolower($GLOBALS['USER_CONTROL']['name']);
      $user_id= $GLOBALS['USER_CONTROL']['id'];

      if( strlen($user)==0 )
	throw new Exception("Missing valid user name", 4);


      // check key count
      $max_id = $this->maxId( $user_id );

      if( $max_id && $max_id>16 )
	throw new Exception("User out of token keys, please destroy.");

      $key_id = $max_id ? $max_id+1 : 1;


      // generate, rely on APPLICATION_AUTH_SECRET
      $new_token = new NuclearAuthToken( $user, $ts );

      // store key for validity
      // FUTURE auth_key will be removed

      $q = "insert into nuclear_api_auth ".
           "(user, id, auth, ts) ".
           "values ($user_id, $key_id, ".
           "UNHEX('". $new_token->token ."'), $ts);";

      $r = WrapMySQL::affected($q, "Unable to insert authorization key");

      $resp->token_id = $key_id;
      $resp->token = $new_token->user_token;
      $resp->timestamp = $ts;
	
      return $resp;
    }

    protected function initXML()
    {
      $o = $this->initJSON();
      header('Content-type: text/xml');
      echo'<response status="ok" ms="'. 
           number_format((microtime(true) - $this->time)*1000,3) .
           "\"><token id=\"{$o->token_id}\" auth_key=\"{$o->token}\" timestamp=\"{$o->timestamp}\" /></response>";

      exit();
    }
  }

  return "postCreateToken";

?>
