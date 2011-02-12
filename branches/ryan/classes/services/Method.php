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

abstract class Method implements iMethod {

	/**
	 * @param Object $parameters
	 * @param string $output
	 */
	protected $parameters;
	protected $output;
	
	
	/**
	 * Default constructor for the command.
	 * Receives the parameters and output setting.
	 * 
	 * @param Object params
	 * @param string output
	 * @return void
	 */
	function __construct ($params,$output) {

		$this->parameters	= is_object($params) ? $params : (object) $params;
		$this->output		= $output;
	}
	
	
	/**
	 * Default implementation to return
	 * parameters.
	 * 
	 * @return Object
	 */
	public function getParameters () {
		
		return $this->parameters;
	}
	
	
	/**
	 * Default implementation to set
	 * parameters.
	 * 
	 * @param Object params
	 * @return Response
	 */
	public function setParameters ($params) {
		
		$this->parameters = is_object($params) ? $params : (object) $params;
		return $this;
	}
	
	
	/**
	 * Default implementation to
	 * throw exception to higher classes.
	 * 
	 * @return void
	 */
	public function commandException ($e) {
		
		throw $e;
	}
	
	
	/**
	 * Execute the method and return
	 * a response; to be handled by
	 * child class.
	 * 
	 * @return Response
	 */
	abstract public function execute ();
	
}