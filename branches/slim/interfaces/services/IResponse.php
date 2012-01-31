<?php
/*
 * iResponse
 *
 * Copyright 2011 Ryan <altaokami@gmail.com>
 * Nuclear Framework
 * Revised 2011
 *
 * ==========================================
 * Response interface, our interface relies
 * on accessor methods (get-only). Objects
 * handling the Response will typically only
 * rely on getting members
 */

interface IResponse {


	/**
	 * Status accessor
	 *
	 * @return iResponse
	 */
	public function Status ();


	/**
	 * Get a response header
	 *
	 * @param string key
	 * @return string
	 */
	public function Header ($key);


	/**
	 * Headers accessor
	 *
	 * @return Headers
	 */
	public function Headers ();


	/**
	 * Content accessor
	 *
	 * @return mixed
	 */
	public function Content ();


	/**
	 * __toString() 
	 * 
	 * @return string
	 */
	public function __toString ();


}
