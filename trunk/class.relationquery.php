<?php
  
  class RelationQuery extends NuQuery
  {
    function __construct( $user, $mode='user' )
    {
      if( !is_numeric($user) )
        throw new Exception("Invalid relation user");

      if( !isType('user|party', $mode) )
        throw new Exception('Invalid relation model');

      $join_user = $mode=='user' ? 'party' : 'user';

      parent::__construct('nu_relation R');
      $this->join(
       'NuclearUser U',
       "U.id=R.{$join_user}");

      $this->field( array(
       'U.id', 'U.name', 'U.domain') );

      $this->where( "U.{$mode}=$user" );

      // FILTER EVENT
      NuQuery::eventFilter( 
        $this, 
	'nu_relation_query', 
	array('fields'=>'premerge', 'joins'=>'postmerge', 'conditions'=>'postmerge')
      );
    }
  }

  class FollowerQuery extends RelationQuery
  {
    function __construct($user, $page=1)
    {
      parent::__construct($user,'party');
      $this->page(is_numeric($page) ? $page : 1, 100, 10, 100);
    }
  }

  class FollowingQuery extends RelationQuery
  {
    function __construct($user, $page=1)
    {
      parent::__construct($user,'party');
      $this->page(is_numeric($page) ? $page : 1, 100, 10, 100);
    }
  }

?>
