<?php
    
    /*
        example api call
    */

    require_once( 'abstract.callwrapper.php' );

    abstract class baseNuclearAuthorizedMethod extends CallWrapper
    {
      protected function getUser()
      {
	if( !isset($GLOBALS['USER_CONTROL']) )
	  throw new Exception("Unauthorized", 2);
	
	$user = new Object();
	$user->id   = $GLOBALS['USER_CONTROL']['id'];
	$user->name = $GLOBALS['USER_CONTROL']['name'];

	return $user;
      }
    }

    abstract class baseNuclearUserMethod extends CallWrapper
    {
      protected function getUser()
      {
	if( !isset($GLOBALS['USER']) )
	  throw new Exception("Unknown user", 3);
	
	return $GLOBALS['USER'];
      }
    }

    require_once('class.nupacketquery.php');
    require_once('lib.nupackets.php');

    class getFMPPacketInbox extends baseNuclearAuthorizedMethod
    {
        private function packets()
	{
	  $user = $this->getUser();

	  if( isset($this->call->namespace) )
	  {
	    $ns_id = NuPacketNamespace::lookup($this->call->namespace);
	    
	    if( $ns_id == 0 )
	      throw new Exception("Unknown namespace", 5);
	  }

	  $filter_query = new NuQuery('_void_');
	  $filter_query = NuEvent::filter('nu_fmp_inbox_query', $filter_query);

	  if( isset($ns_id) )
	  {
	    $packets = new NuPacketInboxNSQuery($user->id, $ns_id, $this->call->page, $this->call->limit);
	  }
	  else
	  {
	    $packets = new NuPacketInboxQuery($user->id, $this->call->page, $this->call->limit);
	  }

	  if( $filter_fields = $filter_query->fields )
	    $packets->premerge( 'fields', $filter_fields );

	  if( $filter_joins  = $filter_query->joins )
	    $packets->postmerge( 'joins', $filter_joins );

	  if( $filter_conds  = $filter_query->conditions )
	    $packets->postmerge( 'conditions', $filter_conds );

	  return $packets;
	}

        protected function initXML()
        {
	  $packets = $this->packets();

	  require_once('class.xmlcontainer.php');

	  $resp = new XMLContainer("1.0", "utf-8", $this->time);

	  $root = $resp->createElement('response');

	  if( $packets->select() )
	  {
	    while($packet = $packets->hash())
	    {
	      $data = NuPacketStorage::read($packet['packet']);

	      if( strlen($data)==0 ) continue;

	      $packet_xml = new DOMDocumentExceptor();
	      $packet_xml->preserveWhiteSpace = false;
	      $packet_xml->formatOutput = true;
	      $packet_xml->loadXML( $data );
	      
	      //
	      // append id/time data
	      $ts = $packet['ts'];
	      $id = $packet['packet'];
	      $packet_xml->documentElement->insertBefore( $packet_xml->createElement('created_at', gmdate('r',$ts)), $packet_xml->documentElement->firstChild );
	      $packet_xml->documentElement->insertBefore( $packet_xml->createElement('timestamp', $ts), $packet_xml->documentElement->firstChild );
	      $packet_xml->documentElement->insertBefore( $packet_xml->createElement('id', $id), $packet_xml->documentElement->firstChild );

	      //
	      // append user/data
	      $user = $packet_xml->createElement('user');
	      $user->appendChild($packet_xml->createElement('id', $packet['publisher']));
	      $user->appendChild($packet_xml->createElement('name', $packet['name']));
	      $user->appendChild($packet_xml->createElement('domain', $packet['domain']));

	      //
	      // replace user packet
	      $pre_user = $packet_xml->getElementsByTagName('user');
	      if( $pre_user->length>0 )
	      {
	        $packet_xml->documentElement->replaceChild( $user, $pre_user->item(0) );
	      }
	      else
	      {
	        $packet_xml->documentElement->appendChild($user);
	      }

	      //
	      // filter
	      $packet_xml = NuEvent::filter('nu_fmp_inbox_packet_xml', $packet_xml, $packet);

	      $packet_node = $resp->importNode( $packet_xml->firstChild, true );
	      $root->appendChild($packet_node);

	    }
	  }

	  $resp->appendRoot($root);
	  $resp->preserveWhiteSpace = false;
	  $resp->formatOutput = true;
	  return $resp;
        }
    }

    return getFMPPacketInbox;

?>
