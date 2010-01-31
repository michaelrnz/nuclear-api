<?php
    
    /*
        example api call
    */

    require_once( 'api.class.usermethod.php' );
    require_once('class.nupacketquery.php');
    require_once('lib.nupackets.php');

    class getFMPPackets extends apiUserMethod
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
	    $packets = new NuPacketNSQuery($user->id, $ns_id, $this->call->page, $this->call->count);
	  }
	  else
	  {
	    $packets = new NuPacketQuery($user->id, $this->call->page, $this->call->count);
	  }

            if( ($since_id = intval($this->call->since_id)) )
            {
                $packets->where("P.packet > {$since_id}");
            }
            
            if( !$since_id && ($max_id = intval($this->call->max_id)) )
            {
                $packets->where("P.packet <= {$max_id}");
            }
	  
            NuSelect::eventFilter( $packets, 'nu_fmp_packet_query', array("fields"=>"premerge", "joins"=>"postmerge", "conditions"=>"postmerge") );

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
	      $packet_xml->documentElement->insertBefore( $packet_xml->createElement('created_at', gmdate('D M d G:i:s O Y',$ts)), $packet_xml->documentElement->firstChild );
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
	      // filter xml
	      $packet_xml = NuEvent::filter('nu_fmp_user_packet_xml', $packet_xml, $packet);

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

    return getFMPPackets;

?>
