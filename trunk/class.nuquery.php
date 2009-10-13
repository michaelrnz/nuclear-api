<?php
  
  /*
      NuQuery - Nuclear
      altman.ryan 2009 Fall
      ==============================
      database query abstraction
  */

  class NuQuery
  {
    private $table;
    private $fields;
    private $joins;
    private $conditions;
    private $grouping;
    private $order;
    private $limit;
    private $offset;

    private $result;

    function __construct( $T )
    {
      $this->table = $T;
      $this->__init();
    }

    private function __init()
    {
      $this->fields = array();
      $this->joins = array();
      $this->conditions = array();
      $this->grouping = array();
      $this->order = array();
    }

    function __toString()
    {
      $f = count($this->fields)>0 ? implode(', ', $this->fields) : "*";

      $str = "select {$f} from {$this->table}";

      if( count($this->joins)>0 )
      {
	$str.= " " . implode(' ', $this->joins);
      }

      if( count($this->conditions)>0 )
      {
	$str.= " where " . trim(implode(' ', $this->conditions), " &|");
      }

      if( count($this->grouping)>0 )
      {
	$str.= " group by " . implode(', ', $this->grouping);
      }

      if( count($this->order)>0 )
      {
	$str.= " order by " . implode(', ', $this->order);
      }

      if( $this->limit )
	$str.= " limit {$this->limit}";

      if( $this->offset )
	$str.= " offset {$this->offset}";

      return $str . ';';
    }

    public function field( $f )
    {
      if( !is_array($f) )
	$f = array($f);

      foreach( $f as $v )
	$this->fields[] = $v;
    }

    public function join( $t, $c=false, $type="left" )
    {
      $this->joins[] = "{$type} join {$t}" . ($c ? " on $c" : "");
    }

    public function where( $c, $type="&&" )
    {
      $this->conditions[] = "{$type} $c";
    }

    public function group( $f )
    {
      if( !is_array($f) )
	$f = array($f);

      foreach( $f as $v )
	$this->grouping[] = $v;
    }

    public function order( $f, $s=false )
    {
      $this->order[] = $f . ($s ? " $s" : "");
    }

    public function page( $page, $limit=100, $default=10, $min=1, $max=100 )
    {
      $page = self::paging( $page, $limit, $default, $min, $max );
      $this->limit = $page['limit'];
      $this->offset= $page['offset'];
    }

    public static function paging( $page, $limit, $default=20, $min=10, $max=100 )
    {
      $l = intval($limit);
      if( $l<=0 )
      {
	$l = $default;
      }
      else if( $l < $min )
      {
	$l = $min;
      }
      else if( $l > $max )
      {
	$l = $max;
      }
      else if( ($l % $min) != 0 )
      {
	$l = floor( $l / $min ) * $min;
      }

      // offset paging
      $p = intval($page);
      if( $p<=0 )
      {
	$offset = 0;
	$p = 1;
      }
      else
      {
	$offset = $l * ($p - 1);
      }

      return array("limit"=>$l,"offset"=>$offset,"page"=>$p);
    }


    //
    // QUERYING
    //
    public function void( $errmsg=false, $errcode=7 )
    {
      $r = mysql_query($this->__toString());

      if( !$r && $errmsg )
	throw new Exception("{$errmsg}: ". mysql_error(), $errcode);
    }

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
      {
	return $tuple;
      }

      return null;
    }

    public function &row()
    {
      if( ($this->result != null) && ($tuple = mysql_fetch_row($this->result)) )
      {
	return $tuple;
      }

      return null;
    }
  }

  /*
  $user = 3;

  // example
  $q = new NuQuery('packet_inbox');
  $q->field( "Inbox.ts" );

  $q->where( "Inbox.subscriber={$user}" );

  // plugin join
  $q->join( "plug_tags AS P", "P.packet=Inbox.id", "inner" );
  $q->field( array( "P.tagname", "P.count" ) );
  $q->where( "P.tag=234" );

  // action join
  $q->join( "actions", "actions.event=Inbox.id", "left" );
  $q->field( "actions.name" );

  $q->order( "Inbox.id", "desc" );

  $q->page( 6, 20 );

  echo $q;

  $q->select();
  while( $d = $q->row() )
  {
    print_r($d);
  }
  
  echo "\n";
  */

?>
