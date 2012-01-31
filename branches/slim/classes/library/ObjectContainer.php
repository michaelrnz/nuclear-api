<?php
/*
 * ObjectContainer
 *
 * Copyright 2011 Ryan <altaokami@gmail.com>
 * Nuclear Framework
 * Revised 2011
 *
 * ==========================================
 * Object container which interalizes the
 * properties into an array.
 */

class ObjectContainer extends Object {
	
	/**
	 * @var array
	 */
	protected $_fields;


	/**
	 * Constructor
	 *
	 * @param Array a
	 * @return void
	 */
	function __construct (&$a=null) {

		if(is_array($a))
		{
			$this->_fields=$a;
		}
		else
		{
			$this->_fields=array();
		}
	}


	/**
	 * __get magic method
	 *
	 * @param string f
	 * @return mixed
	 */
	function __get ($f) {
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


	/**
	 * Magic set method
	 *
	 * @param string f
	 * @param mixed v
	 * @return void
	 */
	function __set ($f,$v) {
		
		$this->_fields[$f]=$v;
	}
	

	/**
	 * Stringify method
	 * @return string
	 */
	function __toString () {

		$s = array_map( array($this,"__walker"), $this->_fields, array_keys( $this->_fields ) );
		return implode("\r\n", $s);
	}


	/**
	 * Array walk callback
	 *
	 * @param mixed $v
	 * @param string $k
	 * @return string
	 */
	function __walker ($v,$k) {
		return "$k: $v";
	}
	
}
