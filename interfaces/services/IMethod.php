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
	 * Request accessor
	 *
	 * @return Request
	 */
	public function Request ();


	/**
	 * Prepare, set Request
	 *
	 * @return IMethod
	 */
	public function Prepare (Request $request);


	/**
	 * Core functionality of 
	 * a service method. Implementation will
	 * execute the body/specifics of the command.
	 *
	 * @param Request $request
	 * @return IResponse
	 */
	public function Execute (Request $request);


}
