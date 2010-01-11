<?php
  require_once('class.nuselect.php');
  
  class RelationQuery extends NuSelect
  {
    function __construct( $user, $model )
    {
      if( !is_numeric($user) )
        throw new Exception("Invalid relation user");

      if( !is_numeric($model) )
        throw new Exception('Invalid relation model');

      parent::__construct('nu_relation R');
      $this->join(
       'NuclearUser U',
       "U.id=R.party");

      $this->field( array(
       'U.id', 'U.name', 'U.domain') );

      $this->where( "R.user=$user" );
      $this->where( "R.model=$model" );

      // FILTER EVENT
      NuSelect::eventFilter( 
        $this, 
	'nu_relation_query', 
	array('fields'=>'premerge', 'joins'=>'postmerge', 'conditions'=>'postmerge')
      );
    }
  }

  class FollowersQuery extends RelationQuery
  {
    function __construct($user, $page=1)
    {
      parent::__construct($user,1);
      $this->page(is_numeric($page) ? $page : 1, 100, 10, 100);
    }
  }

  class FollowingQuery extends RelationQuery
  {
    function __construct($user, $page=1)
    {
      parent::__construct($user,0);
      $this->page(is_numeric($page) ? $page : 1, 100, 10, 100);
    }
  }

?>
