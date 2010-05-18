<?php

	/*
		Object base class
		Taiga Project
		2008 Summer
	*/

	class Object
    {
        function __construct( $data=null )
        {
            if( is_array($data) )
                $data = (object) $data;

            if( is_object($data) )
            {
                foreach( $data as $f=>$v )
                {
                    if( is_numeric( $f ) )
                        continue;

                    $this->$f = $v;
                }
            }
        }
    }

	class ObjectContainer extends Object
	{
		protected $_fields;

		function __construct(&$a=null)
		{
			if(is_array($a))
			{
				$this->_fields=$a;
			}
			else
			{
				$this->_fields=array();
			}
		}

		function __get($f)
		{
			if( isset($this->_fields[$f]) )
			{
			    switch( true )
			    {
				default:
					return $this->_fields[$f];
			    }
			}
			else
			{
				return false;
			}
		}

		function __set($f,$v)
		{
			$this->_fields[$f]=$v;
		}

		function __toString()
		{
			$s = array_map( array($this,"__walker"), $this->_fields, array_keys( $this->_fields ) );
			return implode("\r\n", $s);
		}

		function __walker($v,$k)
		{
			return "$k: $v";
		}
	}

	class JSON
	{
		protected $inner;
		protected $time;

		function __construct($time=false)
		{
			$this->inner= new Object();
			$this->time = $time;
		}

		function __toString()
		{
			//
			// execute javascript via call back
			if( $cb = GET('callback') )
			{
				$pre = $cb ."(";
				$pos = ");";
			}

			//
			// testing ms processing time
			if( $this->time )
				$this->inner->ms = 1000*(microtime(true) - $this->time);

			//
			// return encoded
			return $pre . json_encode( $this->inner ) . $pos;
		}

		function __get($f)
		{
			return $this->inner->$f;
		}

		function __set($f,$v)
		{
		  if( $f == 'time' )
		    $this->time = $v;
		  else
		    $this->inner->$f = $v;
		}

		public function isValid()
		{
			return $this->inner->valid > 0 ? true : false;
		}

		public function getObject()
		{
			if( $this->time )
				$this->inner->ms = 1000*(microtime(true) - $this->time);
			return $this->inner;
		}
	}

?>
