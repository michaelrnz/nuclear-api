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
	 * @param Request $request
	 * @param Terminal $terminal
	 * @return IResponse
	 */
	public function Execute (Request $request, Terminal $terminal);


	/**
	 * Request accessor
	 *
	 * @return Request
	 */
	public function Request ();


	/**
	 * Terminal setter
	 *
	 * @return Terminal
	 */
	public function Terminal ();


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
