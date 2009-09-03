<?php
	
	require( 'api.post.updaterequeststatus.php' );

	class postRequestIgnore extends postUpdateRequestStatus
	{
		protected function getStatus()
		{
			return 'ignored';
		}
	}

	return postRequestIgnore;

?>
