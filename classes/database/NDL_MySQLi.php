<?php
/*
 * MySQLi Nuclear Data Layer
 *
 * Copyright 2011 Ryan <altaokami@gmail.com>
 * Nuclear Framework
 * Revised 2011
 *
 * ==========================================
 * 
 */

class NDL_MySQLi extends NDL {

	/**
	 * @var NDL_MySQLiDecorator
	 */
	protected static $_decorator;


	/**
	 * @var array
	 */
	protected $_connection;


	/**
	 * @var MySQLiResult
	 */
	protected $_result;



	public function __construct () {

		if (!(self::$_decorator instanceof NDL_MySQLiDecorator)) {
			self::$_decorator = new NDL_MySQLiDecorator();
		}
	}


	public function Connect ($host, $user, $pass, $db=null) {

		$this->_connection = new MySQLi($host, $user, $pass, $db);
		if ($this->_connection->connect_errno) {
			throw new Exception("Failed to connect to MySQL");
		}

		return $this;
	}


	/**
	 * Execute a query against the persistence layer
	 * 
	 * @param NDLQuery
	 * @return bool
	 */
	public function Execute (NDLQuery $query) {

		$result = $this->_connection->query(self::$_decorator->SQL($query));
		$this->_result = $result;

		if ($result) {
			return true;
		}

		return false;
	}


	/**
	 * Return result from last execute as NDLResult
	 *
	 * @return NDLResult
	 */
	public function Result () {

		if ($this->_result) {
			return $this->_result;
			//return new NDLResult_MySQLi($this->_result);
		}

		return null;
	}


	/**
	 * Return the last insert id
	 * 
	 * @return int
	 */
	public function Id () {

		return $this->_connection->insert_id();
	}


	/**
	 * Quote a value using the driver interface
	 *
	 * @param mixed $value
	 * @return string
	 */
	public function Quote ($value) {

		return $this->_connection->real_escape_string($value);
	}


	/**
	 * Return an error using the driver interface
	 *
	 * @return PersistenceError
	 */
	public function Error () {
		
	}

}
