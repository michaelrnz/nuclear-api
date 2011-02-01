<?php
/*
 * JSON
 *
 * Copyright 2011 Ryan <altaokami@gmail.com>
 * Nuclear Framework
 * Revised 2011
 *
 * ==========================================
 * JSON object for API output
 */

class JSON {

	/**
	 * @var Object inner
	 * @var float time
	 */
	protected $inner;
	protected $time;


	/**
	 * Constructor
	 *
	 * @param float time
	 * @return void
	 */
	function __construct ($time=false) {

		$this->inner= new Object();
		$this->time = $time;
	}


	/**
	 * Stringify the JSON
	 * @return string
	 */
	function __toString () {
		
		// execute javascript via call back
		if( $cb = GET('callback') )
		{
			$pre = $cb ."(";
			$pos = ");";
		}

		// testing ms processing time
		if( $this->time )
			$this->inner->ms = 1000*(microtime(true) - $this->time);

		// return encoded
		return $pre . json_encode( $this->inner ) . $pos;
	}


	/**
	 * Magic get method
	 *
	 * @param string f
	 * @return mixed
	 */
	function __get ($f) {

		return $this->inner->$f;
	}


	/**
	 * Magic set method
	 *
	 * @param string f
	 * @param mixed v
	 * @return void
	 */
	function __set ($f,$v) {

	  if( $f == 'time' )
		$this->time = $v;
	  else
		$this->inner->$f = $v;
	}


	/**
	 * Check valid key of inner object
	 * @return bool
	 */
	public function isValid () {
		
		return $this->inner->valid > 0 ? true : false;
	}


	/**
	 * Get the internal object
	 * @return Object
	 */
	public function getObject () {

		if( $this->time )
			$this->inner->ms = 1000*(microtime(true) - $this->time);
		return $this->inner;
	}
	
}
