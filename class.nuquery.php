<?php
  
  /*
      NuQuery - Nuclear
      altman.ryan 2009 Fall
      ==============================
      database query abstraction
  */

  abstract class NuQuery
  {
    protected $template;

    protected $table;
    protected $fields;
    protected $values;

    protected $joins;
    protected $conditions;
    protected $grouping;
    protected $order;
    protected $limit;
    protected $offest;

    protected $result;

    function __construct( $O, $T )
    {
      $this->template = $O;
      $this->table = $T;
      $this->__init();
    }

    protected function __init()
    {
      $this->fields = array();
      $this->values = array();
      $this->joins = array();
      $this->conditions = array();
      $this->grouping = array();
      $this->order = array();
    }

    function __get($f)
    {
      switch($f)
      {
        case 'fields':
	case 'joins':
	case 'conditions':
	  return $this->$f;
      }

      return null;
    }

    function __set($f,$v)
    {
      if( !is_array($v) ) return null;

      switch($f)
      {
        case 'fields':
	case 'joins':
	case 'conditions':
	  $this->$f = $v;
      }

      return $this;
    }

    function __toString()
    {
      $sql = $this->template;

      $fields = array(
        "[TABLE]",
	"[FIELDS]",
	"[VALUES]",
	"[JOINS]",
	"[CONDITION]",
	"[GROUP]",
	"[ORDER]",
	"[LIMIT]"
      );
      $values = array();

      $values[0] = $this->table;

      $values[1] = count($this->fields)>0 ? implode(', ', $this->fields) : "*";

      $values[2] = count($this->values)>0 ? implode(', ', $this->values) : "";

      if( count($this->joins)>0 )
      {
	$values[3] = implode(' ', $this->joins);
      }
      else
      {
        $values[3] = "";
      }

      if( count($this->conditions)>0 )
      {
	$values[4] = "where " . trim(implode(' ', $this->conditions), " &|");
      }
      else
      {
        $values[4] = "";
      }

      if( count($this->grouping)>0 )
      {
	$values[5] = "group by " . implode(', ', $this->grouping);
      }
      else
      {
        $values[5] = "";
      }

      if( count($this->order)>0 )
      {
	$values[6] = "order by " . implode(', ', $this->order);
      }
      else
      {
        $values[6] = "";
      }

      $limit = "";
      if( $this->limit )
      {
	$limit .= "limit {$this->limit}";
        if( $this->offset )
	  $limit= "offset {$this->offset}";
      }
      $values[7] = $limit;

      return str_replace($fields,$values,$sql);
    }

    public function field( $f )
    {
      if( !is_array($f) )
	$f = array($f);

      foreach( $f as $v )
	$this->fields[] = $v;
    }

    public function value( $f )
    {
      if( is_array($f) )
      {
        $this->values[] = "(" . implode(",",$f) . ")";
      }
      else
      {
        $this->values[] = "({$f})";
      }
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

    public function void( $errmsg=false, $errcode=7 )
    {
      $r = mysql_query($this->__toString());

      if( !$r && $errmsg )
	throw new Exception("{$errmsg}: ". mysql_error(), $errcode);

      return $r;
    }
  }

  /*
  $user = 3;
  $insert_template= "insert into [TABLE] ([FIELDS]) values [VALUES]";
  $delete_template= "delete from [TABLE] [CONDITION] [LIMIT]";
  $select_template= "select [FIELDS] from [TABLE] [JOINS] [CONDITION] [GROUP] [LIMIT]";

  // example
  $q = new NuQuery($delete_template,'packet_inbox Inbox');
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

  $q = new NuQuery($insert_template, 'vanity_mirror');
  $q->field(array("entity_type", "entity_id", "reference_type", "reference_id", "data"));

  $q->value( array("1","2","3","4","5") );
  $q->value( array("3","4","1","2","5") );

  echo $q;

  echo "\n";
  /**/

?>
