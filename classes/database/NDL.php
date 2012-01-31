<?php
/*
 * NDL - Nuclear Data Layer
 *
 * Copyright 2011 Ryan <altaokami@gmail.com>
 * Nuclear Framework
 * Revised 2011
 *
 * ==========================================
 * 
 */

class NDL implements IDLDriver {


	const NDL_MYSQL = 1;


	/**
	 * Retrieve instance of NDO type
	 *
	 * @param int $type
	 * @return NDO
	 */
	public static function Instance ($type) {

		switch ($type) {

			case self::NDL_MYSQL:
				return new NDL_MySQLi();

			default:
				throw new Exception("Unknown NDL driver");
		}
	}


	/**
	 * Execute NDLQuery
	 *
	 * @return bool
	 */
	public function Execute (NDLQuery $query) {
		throw new Exception("NDL::Execute not implemented in DLDriver");
	}


	/**
	 * Return result of last execution
	 *
	 * @return NDLResult
	 */
	public function Result () {
		throw new Exception("NDL::Result not implemented in NDLDriver");
	}


	/**
	 * Return Id of last inserted data
	 *
	 * @return int
	 */
	public function Id () {
		throw new Exception("NDL::Id not implemented in NDLDriver");
	}


	/**
	 * Quote a value using the driver interface
	 *
	 * @param mixed $value
	 * @return string
	 */
	public function Quote ($value) {
		throw new Exception("NDL::Quote not implemented in NDLDriver");
	}


	/**
	 * Return an error using the driver interface
	 *
	 * @return NDLError
	 */
	public function Error () {
		throw new Exception("NDL::Error not implemented in NDLDriver");
	}

}
