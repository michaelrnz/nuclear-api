<?php
	
	require( 'api.post.updaterequeststatus.php' );

	class postRequestRead extends postUpdateRequestStatus
	{
		protected function getStatus()
		{
			return 'read';
		}
	}

	return postRequestRead;

?>
