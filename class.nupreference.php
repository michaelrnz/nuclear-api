<?php

  /* mock pref handler */

  class NuPreference
  {
    private static $_pref_table = 'nu_preference';
    private static $_instance = null;

    public static function getInstance()
    {
      if( is_null(self::$_instance) )
        self::$_instance = new NuPreference();
      return self::$_instance;
    }

    public function getBlob($id, $label)
    {
      $q = "select blob_store from ". self::$_pref_table ." where id={$id} && label='{$label}' limit 1;";

      if( $d = WrapMySQL::single($q,"Unable to get preference") )
        return unserialize($d['blob_store']);

      return null;
    }

    public function setBlob($id, $label, $value)
    {
      $q = "insert into ". self::$_pref_table ." (id, label, blob_store) values ({$id},'{$label}','" . safe_slash( serialize($value) ) ."')";
      $q.= " on duplicate key update blob_store=values(blob_store);";
      WrapMySQL::void($q, "Unable to set preference");
    }

    public function getInteger($id,$label)
    {
      $q = "select int_store from ". self::$_pref_table ." where id={$id} && label='{$label}' limit 1;";

      if( $d = WrapMySQL::single($q,"Unable to get preference") )
        return $d['int_store'];

      return null;
    }

    public function setInteger($id,$label,$value)
    {
      $q = "insert into ". self::$_pref_table ." (id, label, int_store) values ({$id},'{$label}',{$value})";
      $q.= " on duplicate key update int_store=values(int_store);";
      WrapMySQL::void($q, "Unable to set preference");
    }

    public function increment($id, $label, $inc=1)
    {
      $q = "insert into ". self::$_pref_table ." (id, label, int_store) values ({$id},'{$label}',{$inc}) " .
           "on duplicate key update int_store=int_store+values(int_store);";
      WrapMySQL::void($q, "Unable to set preference");
    }

    public function decrement($id, $label, $dec=1)
    {
      $q = "insert into ". self::$_pref_table ." (id, label, int_store) values ({$id},'{$label}',{$dec}) " .
           "on duplicate key update int_store=int_store-values(int_store);";
      WrapMySQL::void($q, "Unable to set preference");
    }

    public function delete( $id, $label )
    {
      $q = "delete from ". self::$_pref_table ." where id={$id} && label='{$label}' limit 1;";
      WrapMySQL::void($q, "Unable to delete preference");
    }
  }

?>
