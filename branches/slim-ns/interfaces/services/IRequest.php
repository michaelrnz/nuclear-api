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

interface IRequest {


	/**
	 * Method accessor
	 *
	 * @return iResponse
	 */
	public function Method ();


	/**
	 * Add a response header
	 *
	 * @param string key
	 * @return iResponse
	 */
	public function Header ($key);


	/**
	 * Headers accessor
	 *
	 * @return array
	 */
	public function Headers ();


	/**
	 * Content accessor
	 *
	 * @return mixed
	 */
	public function Content ();

}
