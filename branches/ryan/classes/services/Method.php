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
	 * Controlling parameters for the method
	 * @var Object $parameters
	 */
	protected $parameters;


	/**
	 * Suggested response type (format)
	 * @var string $type
	 */
	protected $type;


	/**
	 * Default constructor for the command.
	 * Receives the parameters and output setting.
	 * 
	 * @param Object params
	 * @param string output
	 * @return void
	 */
	function __construct ($params) {

		$this->parameters = is_object($params) ? $params : (object) $params;
	}


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
	 * Default implementation to return
	 * parameters.
	 *
	 * @param Object parameters
	 * @return Object
	 */
	public function Parameters ($parameters=null) {

		if ($parameters) {

			if (is_object($parameters)) {
				$this->parameters = $parameters;

			} else {
				$this->parameters = (object) $parameters;

			}

			return $this;
		}

		return $this->parameters;
	}


	/**
	 * Type accessor
	 * Set the type hinting (suggested response format)
	 *
	 * @param string $type
	 * @return Method
	 */
	public function Type ($type=null) {

		if (is_scalar($type)) {
			$this->type = (string) $type;
			return $this;
		}

		return $this->type;
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