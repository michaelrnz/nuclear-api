<?php
  
  /*
    get listing of keys
  */

  require_once( 'abstract.callwrapper.php' );

  class getTokens extends CallWrapper
  {
    private function query($user)
    {
      $q = "select id, auth_key, ts from nuclear_api_auth where user={$user};";
      return WrapMySQL::q($q, "Unable to query auth keys");
    }

    private function getUser()
    {
      $user_id = $GLOBALS['USER_CONTROL']['id'];
      if( !$user_id )
	throw new Exception("Unauthorized", 2);
      return $user_id;
    }

    protected function initJSON()
    {
      $user_id = $this->getUser();

      $result = $this->query($user_id);

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
      $user_id = $this->getUser();

      $result = $this->query($user_id);

      require_once('class.xmlcontainer.php');
      $resp = new XMLContainer("1.0","utf-8", $this->time );
      $root = $resp->createElement("response");
      $root->setAttribute("status","ok");
      $root->setAttribute("request","get.tokens");

      $atts = array("id","auth_key","timestamp");
      while($row = mysql_fetch_row($result))
      {
	$key = $resp->createElement("token");
	foreach($atts as $i=>$a)
	  $key->setAttribute($a,$row[$i]);
	
	$root->appendChild($key);
      }

      $resp->appendChild($root);
      return $resp;
    }
  }

  return getTokens;

?>
