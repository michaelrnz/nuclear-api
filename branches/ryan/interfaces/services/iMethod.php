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

interface iMethod {


	/**
	 * Core functionality of 
	 * a service method. Implementation will
	 * execute the body/specifics of the command.
	 * 
	 * @return iResponse
	 */
	public function execute ();
	
	
	/**
	 * return the current
	 * parameters passed to the method.
	 * 
	 * @return Object
	 */
	public function getParameters ();
	
	
	/**
	 * set the parameter object
	 * within the method.
	 * 
	 * @param Object params
	 * @return iMethod
	 */
	public function setParameters ($params);
	
	
	/**
	 * handle an exception
	 * throw within the method, usually
	 * relaying the exception to a
	 * higher layer
	 * 
	 * @return void
	 */
	public function commandException ($e);
	
}
