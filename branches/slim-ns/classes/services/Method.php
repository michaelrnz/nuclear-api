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
	 * Controlling request for the method
	 * @var Request $request
	 */
	protected $request;


	/**
	 * Request accessor
	 *
	 * @return Request
	 */
	public function Request () {

		return $this->request;
	}


	/**
	 * Prepare by injecting Request
	 * should be used for Request validation
	 *
	 * @param Request $request
	 * @return Method
	 */
	public function Prepare (IRequest $request) {

		$this->request = $request;
		return $this;
	}


	/**
	 * Execute the method and return
	 * a response; to be handled by
	 * child class.
	 *
	 * @param Request $request
	 * @return Response
	 */
	public function Execute (IRequest $request=null) {

		throw new Exception("Method::Execute() not implemented in child class");
	}


	/**
	 * Default implementation to
	 * throw exception to higher classes.
	 * 
	 * @return void
	 */
	protected function MethodException ($e) {

		throw $e;
	}

}