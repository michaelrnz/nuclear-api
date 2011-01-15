<?php
	/*
		nuclear Framework
		Altman, Ryan - 2008

		Event Library
			:allows firing of events by static libraries
	*/

	require_once('class.eventdriver.php');

	class EventLibrary
	{
		protected static $driver = null;

		//
		// initialize per-libarary driver
		//
		public static function init()
		{
			if( self::$driver === null )
			self::$driver = new EventDriver();
		}
		
		//
		// pass-thru methods to initiated driver
		// 
		public static function addEventListener($t,$l)
		{
			if( !is_null(self::$driver) )
				self::$driver->addEventListener($t,$l);
		}

		public static function removeEventListener($t,$l)
		{
			if( !is_null(self::$driver) )
				self::$driver->removeEventListener($t,$l);
		}

		protected static function fire($t,&$o)
		{
			if( !is_null(self::$driver) )
				self::$driver->fire($t,&$o);
		}
	}

?>
