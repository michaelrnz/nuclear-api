<?php
/*
 * IDLDriver - Nuclear Data Layer Driver
 *
 * Copyright 2011 Ryan <altaokami@gmail.com>
 * Nuclear Framework
 * Revised 2011
 *
 * ==========================================
 * 
 */

interface IDLDriver {


	/**
	 * Execute a query against the persistence layer
	 * 
	 * @param DLQuery
	 * @return bool
	 */
	public function Execute (NDLQuery $query);


	/**
	 * Return DLResult of execution
	 *
	 * @return DLResult
	 */
	public function Result ();


	/**
	 * Return the last insert id
	 * 
	 * @return int
	 */
	public function Id ();


	/**
	 * Quote a value using the driver interface
	 *
	 * @param mixed $value
	 * @return string
	 */
	public function Quote ($value);


	/**
	 * Return an error using the driver interface
	 *
	 * @return DLError
	 */
	public function Error ();

}
