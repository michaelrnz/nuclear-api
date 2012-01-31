<?php
/*
 * IFactory
 *
 * Copyright 2011 Ryan <altaokami@gmail.com>
 * Nuclear Framework
 * Revised 2011
 *
 * ==========================================
 *
 */

interface IFactory {


	/**
	 * Build the instance on type
	 *
	 * @param string type
	 * @return mixed
	 */
	public function Build ($type);


	/**
	 * Register a type
	 *
	 * @param string type
	 * @param mixed handler
	 * @return IFactory
	 */
	public function Register ($type, $handler);



	/**
	 * Unregister a type
	 *
	 * @param string type
	 * @return IFactory
	 */
	public function Unregister ($type);


}
