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

	  if( isset($ns_id) )
	  {
	    $packets = new NuPacketInboxNSQuery($user->id, $ns_id, $this->call->page, $this->call->limit);
	  }
	  else
	  {
	    $packets = new NuPacketInboxQuery($user->id, $this->call->page, $this->call->limit);
	  }

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

	      $packet_xml = NuEvent::filter('fmp_inbox_packet_xml', $packet_xml);

	      //echo '<pre>' . nuXmlChars($data) .'</pre>';
	      //echo '<pre>' . nuXmlChars($packet_xml->saveXML()) .'</pre>';

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
