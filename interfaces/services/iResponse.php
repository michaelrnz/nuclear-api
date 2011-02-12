<?php
/*
 * iResponse
 *
 * Copyright 2011 Ryan <altaokami@gmail.com>
 * Nuclear Framework
 * Revised 2011
 *
 * ==========================================
 * Response interface
 */

interface iResponse {

	/**
	 * __get() magic method
	 * 
	 * @param string key
	 * @return mixed
	 */
	public function __get ($key);
	
	
	/**
	 * __set() magic method 
	 * 
	 * @param string key
	 * @param mixed value
	 * @return void
	 */
	public function __set ($key,$value);
	
	
	/**
	 * __toString() 
	 * 
	 * @return string
	 */
	public function __toString ();
	
	
	/**
	 * Get the response status.
	 * 
	 * @return iResponse
	 */
	public function getStatus ();
	
	
	/**
	 * Set the response status.
	 * 
	 * @return iResponse
	 */
	public function setStatus ($status);
	
}
