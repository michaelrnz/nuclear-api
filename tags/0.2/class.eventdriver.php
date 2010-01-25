<?php
	/*
		nuclear.framework
		altman,ryan,2008

		Event Driver
		================================
			adds functionality to classes
			similar to that of javascript
			events.

	*/

	class EventDriver
	{

		private $listeners;

		function __construct()
		{
			$this->listeners = array();
		}

		public function addEventListener( $t, $l )
		{
			if( !$t || !$l ) return;

			$t = preg_replace('/^on/', '', strtolower( $t ));

			// check for array
			if( !is_array( $this->listeners[$t] ) )
			{
				$this->listeners[$t] = array($l);
			}
			else // push listener
			{
				array_push( $this->listeners[$t], $l );
			}
		}

		public function removeEventListener( $t, $l )
		{
			if( !$t || !$l )
				return;

			// clean 'on'
			$t = preg_replace('/^on/', '', strtolower( $t ));

			// check if listeners is an array
			if( !is_array( $this->listeners[$t] ) )
				return;
			
			// check if listener is listening
			if( ($index = array_search( $this->listeners[$t], $l ))===false )
				return;

			// splice listener out of listeners
			$this->listeners = array_splice( $this->listeners, $index, 1 );
		}

		public function fire( $t, &$o=null )
		{
			$t = strtolower( $t );

			// check for listeners
			if( !is_array( $this->listeners[$t] ) || count($this->listeners[$t])==0 )
				return;

			// chain the listeners
			foreach( $this->listeners[$t] as $l )
			{
				call_user_func( $l, $o );
			}
		}

	}
?>
