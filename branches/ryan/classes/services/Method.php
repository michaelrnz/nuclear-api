<?php
/*
 * Method
 *
 * Copyright 2011 Ryan <altaokami@gmail.com>
 * Nuclear Framework
 * Revised 2011
 *
 * ==========================================
 * Default method class
 */

abstract class Method implements IMethod {

	/**
	 * Output terminal passed from Proxy
	 * @var Terminal $terminal
	 */
	protected $terminal;


	/**
	 * Controlling request for the method
	 * @var Request $request
	 */
	protected $request;


	/**
	 * Terminal accessor
	 * 
	 * @return Terminal
	 */
	public function Terminal () {

		return $this->terminal;
	}


	/**
	 * Request accessor
	 *
	 * @return Request
	 */
	public function Request (Request $request=null) {

		return $this->request;
	}


	/**
	 * Default implementation to
	 * throw exception to higher classes.
	 * 
	 * @return void
	 */
	public function MethodException ($e) {

		throw $e;
	}


	/**
	 * Execute the method and return
	 * a response; to be handled by
	 * child class.
	 *
	 * @param Request $request
	 * @param Terminal $terminal
	 * @return Response
	 */
	abstract public function Execute (Request $request, Terminal $terminal);

}