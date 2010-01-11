<?php
  
  require_once('class.xmlcontainer.php');

  class XMLResponse extends XMLContainer
  {
    function __construct( $ts, $root='response' )
    {
      parent::__construct( '1.0', 'utf-8', $ts );
      $root = $this->createElement($root);
      $this->appendRoot($root);
    }

    function __set( $f, $v )
    {
      switch( $f )
      {
        case 'status':
	  $this->root->setAttribute('status', $v);
	  break;
      }
    }

    function attach( $n, $v=false )
    {
      if( $v !== false )
        return $this->createElement($n,$v);
      return $this->createElement($n);
    }

    function append( &$node )
    {
      $this->root->appendChild( $node );
    }
  }

?>
