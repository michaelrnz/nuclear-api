<?php

  require_once("abstract.callwrapper.php");
  require_once("lib.nufederated.php");

  class postFederatedDispatch extends CallWrapper
  {
    /*
	PARAMS
	content	  // <fps packet
	publisher // should be known from local USER_CONTROL
	[ID]	  // local identification of packet
	[ns]	  // namespace of the packet
    */

    protected function initJSON()
    {
      $publisher    = $GLOBALS['USER_CONTROL']['id'];

      if( !$publisher || !is_numeric($publisher) )
	throw new Exception("Invalid publisher", 5);

      $packet_data = $this->call->data;

      if( !strlen($packet_data) )
	throw new Exception("Missing data", 4);

      $fps_params  = array(
		      "packet"=> $packet_data,
		     );
      
      if( $this->call->id )
	$fps_params['id'] = $this->call->id;

      if( $this->call->ns )
	$fps_params['ns'] = $this->call->ns;

      $subscribers  = NuFederatedRelation::federatedSubscribers( $publisher );

      if( !$subscribers )
	throw new Exception("Missing subscribers", 11);

      if( mysql_num_rows($subscribers)>0 )
      {
	$domain = false;

	while( $subscriber = mysql_fetch_array( $subscribers ) )
	{
	  // publish once per domain
	  if( $domain == $subscriber['domain'] ) continue;
	  
	  // assign domain
	  $domain = $subscriber['domain'];

	  // create OAuth
	  $oauth_params = new NuOAuthParameters(
				$subscriber['consumer_key'],
				$subscriber['consumer_secret'],
				$subscriber['token'],
				$subscriber['token_secret']);
	  
	  // publish to domain
	  $access_resp  = NuOAuthRequest::text( $oauth_params, "http://{$domain}/api/fps/publish.json", "POST", $fps_params );

	  // log resp
	  file_put_contents( $GLOBALS['CACHE'] . '/publishing.log', time() . ": {$access_resp}\n", FILE_APPEND );
	}
      }

      // dispatching complete
      $o = new JSON($this->time);
      $o->status = "ok";
      $o->message = "Packet dispatched";

      return $o;
    }
  }

  return postFederatedDispatch;

?>
