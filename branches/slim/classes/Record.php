<?php
/*
 * Record
 *
 * Copyright 2011 Ryan <altaokami@gmail.com>
 * Nuclear Framework
 * Revised 2011
 *
 * ==========================================
 * General record class implements iterator
 */

class Record implements Iterator {

	/**
	 * @var _values
	 * @var _map
	 */
	protected $_values;
	protected static $_map = array();


	/**
	 * Magic setter
	 *
	 * @param k
	 * @param v
	 * @return void
	 */
	function __set ($k, $v) {

		if( array_key_exists( $k, $this->getMap() ) )
		{
			$this->_values[$k] = $v;
		}
	}


	/**
	 * Magic getter
	 * 
	 * @param k
	 * @return mixed
	 */
	function __get ($k) {

		if( array_key_exists( $k, $this->_values ) )
		{
			return $this->_values[$k];
		}

		return null;
	}


	/**
	 * Stringify record via json
	 * @return string
	 */
	function __toString () {

		return json_encode( (object) $this->_values );
	}


	/**
	 * Return the static _map array
	 * (uses late static binding)
	 * 
	 * @return array
	 */
	protected function getMap () {

		return self::$_map;
	}


	/**
	 * Iterator implementation
	 * @return void
	 */
	public function rewind () {

		reset( $this->_values );
	}


	/**
	 * Iterator implementation
	 * @return mixed
	 */
	public function current () {

		return current( $this->_values );
	}


	/**
	 * Iterator implementation
	 * @return string
	 */
	public function key() {

		return key( $this->_values );
	}


	/**
	 * Iterator implementation
	 * @return mixed
	 */
	public function next () {

		return next( $this->_values );
	}


	/**
	 * Iterator implementation
	 * @return bool
	 */
	public function valid() {

		return $this->current() !== false;
	}


	/**
	 * Return Object represetation of the record
	 * @return Object
	 */
	public function getObject () {

		$o = new Object();
		foreach( $this as $k=>$v )
		{
			$o->$k = $v;
		}
		return $o;
	}
	
}
