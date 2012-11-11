<?php
	
	/*
		nuclear.framework
		altman,ryan,2008

		Atomic Cache events
		===============================================
			handles locking and wait of cache gen
			listens to Cache library
	*/

	class AtomicCache
	{
		private static $tag = '.generating';
		private static $sleep = 500;
		
		//
		// onCheckCache handler
		//
		public static function check( $o )
		{
			// make local
			$f = $o->file . self::$tag;
			$s = self::$sleep;

			// wait for flag to vanish
			while( file_exists( $f ) )
			{
				usleep( $s );
			}
		}

		//
		// onCheckCacheFail handler
		//
		public static function fail( $o )
		{
			// touch the observer flag
			touch( $o->file . self::$tag );

			// listen for cache even
			Cache::addEventListener('onCache', array("AtomicCache","cache"));
		}

		//
		// onCache handler
		//
		public static function cache( $o )
		{
			// remove observer flag
			unlink( $o->file . self::$tag );

			// remove self
			Cache::removeEventListener('onCache', array("AtomicCache","cache"));
		}

	}

?>
