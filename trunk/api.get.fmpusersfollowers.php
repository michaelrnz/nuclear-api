<?php
  
  require_once('api.class.usermethod.php');

  class getFMPUsersFollowing extends apiUserMethod
  {
    protected function query()
    {
      $user = $this->getUser();
      if( !$user->id )
        throw new Exception("Missing valid user",4);

      require_once('class.relationquery.php');
      $query = new FollowerQuery( $user->id, $this->call->page );
      return $query;
    }

    protected function initXML()
    {
      require_once('class.xmlcontainer.php');
      $resp = new XMLContainer('1.0','utf-8',$this->time);
      $root = $resp->createElement('response');

      $result = $this->query();

      $root->setAttribute('request', 'fmp.user.following');
      $root->setAttribute('status', 'ok');

      if( $result->select() )
      {
        while( $data = $result->hash() )
	{
	  $user = $resp->createElement('user');
	  foreach( $data as $f=>$v )
	  {
	    if( is_numeric($f) ) continue;

	    $user->appendChild( $resp->createElement($f, $v) );
	  }

	  $root->appendChild($user);
	}
      }

      $resp->appendRoot($root);

      return $resp;
    }
  }

  return getFMPUsersFollowing;

?>
