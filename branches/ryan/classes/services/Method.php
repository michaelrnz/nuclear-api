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
	 * Default implementation to return
	 * parameters.
	 * 
	 * @return Terminal
	 * @return Method
	 */
	public function Terminal ($terminal=null) {

		if ($terminal instanceof Terminal) {
			$this->terminal = $terminal;
			return $this;
		}

		return $this->terminal;
	}


	/**
	 * Request accessor
	 *
	 * @param Request request
	 * @return Request
	 */
	public function Request (Request $request=null) {

		if ($request) {
			$this->request = $request;
			return $this;
		}

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
	 * @return Response
	 */
	abstract public function Execute ();

}