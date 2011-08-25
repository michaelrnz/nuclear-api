<?php
/*
 * Container
 *
 * Copyright 2011 Ryan <altaokami@gmail.com>
 * Nuclear Framework
 * Revised 2011
 *
 * ==========================================
 * Basic K-V storage class
 */

class ValueMap {


	/**
	 * @var array
	 */
	protected $data = array();


	/**
	 * Get Value by Key
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function Get ($key) {

		if (isset($this->data[$key])) {
			return $this->data[$key];

		else {
			return null;
		}
	}


	/**
	 * Set Value by key
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return mixed
	 */
	public function Set ($key, $value) {
		$this->data[$key] = $value;
	}


	/**
	 * Reset mapping
	 * @return void
	 */
	public function Reset () {
		unset($this->data);
		$this->data = array();
	}


	/**
	 * Retrieve data container
	 * @return array
	 */
	public function All () {
		return $this->data;
	}

}
