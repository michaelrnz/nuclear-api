<?php

  /*
    NuSelect - database selection
    altman.ryan - 2009
  */

  require_once("class.nuquery.php");

  class NuSelect extends NuQuery
  {
    function __construct( $table )
    {
      parent::__construct(
        "select [FIELDS] from [TABLE] [JOINS] [CONDITION] [GROUP] [ORDER] [LIMIT]", $table);
    }

    //
    // MERGING METHODS
    //
    public function premerge( $field, $values )
    {
      switch( $field )
      {
        case 'fields':
	  $this->fields = array_merge( $values, $this->fields );
	  break;
	
	case 'joins':
	  $this->joins  = array_merge( $values, $this->joins );
	  break;

	case 'conditions':
	  $this->conditions = array_merge( $values, $this->conditions );
	  break;
      }
    }

    public function postmerge( $field, $values )
    {
      switch( $field )
      {
        case 'fields':
	  $this->fields = array_merge( $this->fields, $values );
	  break;
	
	case 'joins':
	  $this->joins  = array_merge( $this->joins, $values );
	  break;

	case 'conditions':
	  $this->conditions = array_merge( $this->conditions, $values );
	  break;
      }
    }

    //
    // QUERY METHODS
    //
    public function &select( $errmsg=false, $errcode=7 )
    {
      if( !($r = mysql_query($this->__toString())) )
	throw new Exception(($errmsg ? "{$errmsg}: " : "Error selecting from {$this->table}: "). mysql_error(), $errcode);

      $this->result = $r;
      return $r;
    }

    public function single()
    {
      $this->limit = 1;
      $this->select();
      if( $this->result )
        return $this->hash();

      return null;
    }

    public function &hash()
    {
      if( ($this->result != null) && ($tuple = mysql_fetch_array($this->result)) )
	return $tuple;

      return null;
    }

    public function &row()
    {
      if( ($this->result != null) && ($tuple = mysql_fetch_row($this->result)) )
	return $tuple;

      return null;
    }

    public function &object()
    {
        $tuple = null;

        if( $this->result != null )
            $tuple = mysql_fetch_assoc($this->result);

        if( $tuple && !is_null($tuple) )
            return (object) $tuple;

        return null;
    }

    public static function eventFilter($query, $filter_name, $attributes)
    {
      $filter_query = new NuSelect('_void_');
      $filter_query = NuEvent::filter($filter_name, $filter_query);

      foreach( $attributes as $att=>$type )
      {
	if( !isType('fields|joins|conditions', $att) ) continue;

        if( $type == 'premerge' )
	{
	  $query->premerge( $att, $filter_query->$att );
	}
	else if( $type == 'postmerge' )
	{
	  $query->postmerge( $att, $filter_query->$att );
	}
      }
    }
    
        public function filter( $aspect, $attributes )
        {
            $ev     = Events::getInstance();
            
            if( $ev->isObserved( $aspect ) )
            {
                $dummy  = new NuSelect('_void_');
                $dummy  = $ev->filter( $aspect, $dummy );
                
                foreach( $attributes as $att=>$type )
                {
                    if( !isType('fields|joins|conditions', $att) )
                        continue;
                    
                    if( $type == 'premerge' )
                    {
                        $this->premerge( $att, $dummy->$att );
                    }
                    else
                    {
                        $this->postmerge( $att, $dummy->$att );
                    }
                }
            }
        }
  }

?>
