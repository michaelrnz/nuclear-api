<?php

	/*
		nuclear.framework
		altman,ryan,2008

		Nuclear Event
		================================
			adds functionality to classes
			similar to that of javascript
			events or WP hooks.

	*/

	class NuEvent
	{
		private static $handlers;

		public static function init()
		{
			self::$handlers = array();
		}
		
		public function hook( $aspect, $handler )
		{
			if( !$aspect || !$handler ) return;

			$aspect = preg_replace('/^on/', '', strtolower( $aspect ));

			if( !isset( self::$handlers[$aspect] ) )
				self::$handlers[$aspect] = array($handler);

			else
				array_push( self::$handlers[$aspect], $handler );
		}

		public function unhook( $aspect, $handler )
		{
			if( !$aspect || !$handler ) return;

			$aspect = preg_replace('/^on/', '', strtolower( $aspect ));

			if( !isset( self::$handlers[$aspect] ) )
				return;

			if( ($index = array_search( self::$handlers[$aspect], $handler ))===false )
				return;

			self::$handlers[$aspect] = array_splice( self::$handlers[$aspect], $index, 1 );
		}

		public function raise( $aspect, &$o=null )
		{
			$aspect = preg_replace('/^on/', '', strtolower( $aspect ));

			if( !isset( self::$handlers[$aspect] ) || count(self::$handlers[$aspect])==0 )
				return;

			foreach( self::$handlers[$aspect] as $H )
			{
				call_user_func( $H, $o );
			}
		}
	}

	NuEvent::init();
	@include("application.hooks.php");

?>
