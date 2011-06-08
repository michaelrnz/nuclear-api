<?php
/*
 * iMethod
 *
 * Copyright 2011 Ryan <altaokami@gmail.com>
 * Nuclear Framework
 * Revised 2011
 *
 * ==========================================
 * Nuclear Method interface
 */

interface IMethod {


	/**
	 * Core functionality of 
	 * a service method. Implementation will
	 * execute the body/specifics of the command.
	 * 
	 * @return IResponse
	 */
	public function Execute ();


	/**
	 * Parameters accessor
	 *
	 * @param object parameters
	 * @return Object
	 */
	public function Parameters ($parameters);


	/**
	 * Terminal setter
	 *
	 * @param Terminal terminal
	 * @return IMethod
	 */
	public function Terminal ($terminal);


	/**
	 * Type accessor
	 *
	 * @param string type
	 * @return string
	 */
	public function Type ($type);


	/**
	 * handle an exception
	 * throw within the method, usually
	 * relaying the exception to a
	 * higher layer
	 * 
	 * @return void
	 */
	public function MethodException ($e);


}
