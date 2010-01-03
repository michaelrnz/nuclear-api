<?php
  
  /*
    Preference Handler Singleton
    altman,ryan Dec 2009
  */

  require_once("class.nuselect.php");
  require_once("class.nuinsert.php");

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

    public function get($id, $label)
    {
      $q = new NuSelect( self::$_pref_table );
      $q->field( "data" );
      $q->where( "id={$id}" );
      $q->where( "label='{$label}'" );

      if( $d = $q->single() )
        return unserialize($d['data']);

      return null;
    }

    public function set($id, $label, $value)
    {
      $q = new NuInsert( self::$_pref_table );
      $q->field( "id,label,data" );
      $q->value( array($id,$label,"'". safe_slash( serialize($value) ) ."'") );
      $q->duplicates( "data=values(data)" );
      $q->void();

      return $this;
    }
  }

  /*
    Usage:

    NuPreference::getInstance()->set( 3, "ratings-film", $rating_obj );

    $p = NuPreference::getInstance();
    $p->set( 3, "stream-privacy", $priv )->set( 3, "stream", $prefs );

  */

?>
