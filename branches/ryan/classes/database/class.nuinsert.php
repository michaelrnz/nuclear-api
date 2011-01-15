<?php
  
  /*
    NuInsert - database insertion
    altman.ryan 2009
  */

  require_once("class.nuquery.php");

  class NuInsert extends NuQuery
  {
    function __construct($table)
    {
      parent::__construct("insert into [TABLE] ([FIELDS]) values [VALUES]", $table);
    }

    public function  duplicates( $values )
    {
      $vals = is_array($values) ? $values : array($values);
      $this->template .= " on duplicate key update " . implode(", ", $vals);
    }

    //
    // QUERY METHODS
    //
    public function id( $errmsg=false, $errcode=7 )
    {
      $this->void($errmsg,$errcode);
      return mysql_insert_id();
    }

    public function affected( $errmsg=false, $errcode=7 )
    {
      $this->void($errmsg,$errcode);
      return mysql_affected_rows();
    }
  }

  /*
  $q = new NuInsert("vanity_mirror");
  $q->field("type,id,type,id,data");
  $q->value( array(1,2,3,4,5) );
  $q->value( array(1,4,14,232,4) );

  echo $q;
  */
?>
