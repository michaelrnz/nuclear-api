<?php
  
  require_once('api.class.usermethod.php');

  class getFMPUsersFollowers extends apiUserMethod
  {
    protected function query()
    {
      $user = $this->getUser();
      if( !$user->id )
        throw new Exception("Missing valid user",4);

      require_once('class.relationquery.php');
      $query = new FollowersQuery( $user->id, $this->call->page );
      return $query;
    }

    protected function initXML()
    {
      require_once('class.xmlcontainer.php');
      $resp = new XMLContainer('1.0','utf-8',$this->time);
      $root = $resp->createElement('response');

      $result = $this->query();

      $root->setAttribute('request', 'fmp.user.followers');
      $root->setAttribute('status', 'ok');

      if( $result->select() )
      {
	$first = array('id','name','domain');
        while( $data = $result->hash() )
	{
	  $user = $resp->createElement('user');
	  foreach( $first as $f )
	  {
	    $user->appendChild( $resp->createElement($f, $data[$f]) );
	  }

	  foreach( $data as $f=>$v )
	  {
	    if( is_numeric($f) ) continue;
	    if( isType('id|name|domain', $f) ) continue;

	    $user->appendChild( $resp->createElement($f, $v) );
	  }

	  $root->appendChild($user);
	}
      }

      $resp->appendRoot($root);

      return $resp;
    }
  }

  return getFMPUsersFollowers;

?>
