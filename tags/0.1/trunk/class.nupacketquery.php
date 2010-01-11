<?php

  /*
    Nuclear Packet Queries
    altman.ryan 2009
    ===========================
    generates queries for
    selects inbox/index pub/sub
  */

  require_once('class.nuselect.php');

  class NuPacketQuery extends NuSelect
  {
    function __construct($publisher, $page=1,$limit=20, $order='desc')
    {
      parent::__construct('nu_packet_index as P force index(publisher)');
      $this->field( array(
	  'P.id as packet','P.ts','P.publisher','N.name','D.name as domain'
      ));

      $this->join( "nu_user as U",	    "U.id=P.publisher" );
      $this->join( "nu_name as N",	    "N.id=U.name" );
      $this->join( "nu_domain as D",	    "D.id=U.domain" );

      $this->where("P.publisher={$publisher}");
      $this->order( 'P.ts', isType('asc|desc', $order)? $order : 'desc' );
      $this->page( $page, $limit, 20, 10, 100 );
    }
  }

  class NuPacketNSQuery extends NuPacketQuery
  {
    function __construct($publisher, $namespace, $page=1, $limit=20)
    {
      parent::__construct($publisher, $page, $limit);
      $this->join( 'nu_federated_packet_namespace as NS', 'NS.packet=P.id', 'inner' );
      $this->where( "NS.namespace={$namespace}" );
    }
  }

  class NuPacketInboxQuery extends NuSelect
  {
    function __construct($subscriber, $page=1, $limit=20, $order='desc')
    {
      parent::__construct('nu_packet_inbox as P');
      $this->field( array(
	  'P.packet','P.ts','I.publisher','I.global_id', 'N.name','D.name as domain'
      ));

      $this->join( "nu_packet_index as I",  "I.id=P.packet" );
      $this->join( "nu_user as U",	    "U.id=I.publisher" );
      $this->join( "nu_name as N",	    "N.id=U.name" );
      $this->join( "nu_domain as D",	    "D.id=U.domain" );

      $this->where( "P.subscriber={$subscriber}");
      $this->order( 'P.ts', isType('asc|desc', $order)? $order : 'desc' );
      $this->page(  $page, $limit, 20, 10, 100 );
    }
  }

  class NuPacketInboxNSQuery extends NuPacketInboxQuery
  {
    function __construct($subscriber, $namespace, $page=1, $limit=20)
    {
      parent::__construct($subscriber, $page, $limit);
      $this->join( 'nu_federated_packet_namespace as NS', 'NS.packet=P.packet', 'inner' );
      $this->where( "NS.namespace={$namespace}" );
    }
  }

?>
