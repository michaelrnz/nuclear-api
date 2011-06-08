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

interface IResponse {


	/**
	 * Status accessor
	 *
	 * @param int status
	 * @return iResponse
	 */
	public function Status ($status);	
	

	/**
	 * Add a response header
	 *
	 * @param string key
	 * @param string value
	 * @return iResponse
	 */
	public function Header ($key, $value);


	/**
	 * Headers accessor
	 *
	 * @param array headers
	 * @return array
	 */
	public function Headers ($headers);


	/**
	 * Content accessor
	 *
	 * @param mixed content
	 * @return mixed
	 */
	public function Content ($content);


	/**
	 * __toString() 
	 * 
	 * @return string
	 */
	public function __toString ();


}
