<?php
	
	require( 'api.post.updaterequeststatus.php' );

	class postRequestDeny extends postUpdateRequestStatus
	{
		protected function getStatus()
		{
			return 'denied';
		}
	}

	return postRequestDeny;

?>
