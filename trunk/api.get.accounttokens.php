<?php
  
  /*
    get listing of keys
  */

  require_once( 'api.class.userauthmethod.php' );
  require_once( 'lib.keys.php' );

  class getTokens extends apiUserAuthMethod
  {
    private function query($user)
    {
      return WrapMySQL::q(
                "select id, LOWER(HEX(auth)) as auth, ts ".
                "from nuclear_api_auth ".
                "where user={$user};", 
                "Unable to query auth keys");
    }

    protected function initJSON()
    {
      $user = $this->getAuth();
      $result = $this->query($user->id);

      $resp = new JSON( $this->time );
      $resp->status = "ok";

      $keys = array();
      while($row = mysql_fetch_row($result))
      {
	$key = new Object();
	$key->id = $row[0];
	$key->hash= $row[1];
	$key->timestamp = $row[2];

	$keys[] = $key;
      }

      $resp->tokens = $keys;
      return $resp;
    }

    protected function initXML()
    {
      $user = $this->getAuth();
      $result = $this->query($user->id);

      require_once('class.xmlresponse.php');
      $resp = new XMLResponse($this->time );

      $resp->status = "ok";
      $resp->request= "get.account/tokens";

      while($row = mysql_fetch_row($result))
      {
        $token = $resp->attach("token");
        $token->setAttribute( "id", $row[0] );
        $token->setAttribute( "timestamp", $row[2] );
        $token->setAttribute( "auth", KeyObject::pack($row[1]) );

        $resp->append( $token );
      }

      return $resp;
    }
  }

  return "getTokens";

?>
