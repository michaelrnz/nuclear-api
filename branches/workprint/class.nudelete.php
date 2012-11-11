<?php
  
  /*
    NuInsert - database insertion
    altman.ryan 2009
  */

  require_once("class.nuquery.php");

  class NuDelete extends NuQuery
  {
    function __construct($table)
    {
      parent::__construct("delete from [TABLE] [CONDITION] [LIMIT];", $table);
    }

    //
    // QUERY METHODS
    //
    public function affected( $errmsg=false, $errcode=7 )
    {
      $this->void($errmsg,$errcode);
      return mysql_affected_rows();
    }
  }

?>
