<?php
/*
 * Response
 *
 * Copyright 2011 Ryan <altaokami@gmail.com>
 * Nuclear Framework
 * Revised 2011
 *
 * ==========================================
 * Dummy request class (Mehod return)
 * Only works for REST methods
 */

class DummyRequest implements IRequest {


	protected $content;


	/**
	 * Construct
	 *
	 * @return void
	 */
	public function __construct ($content=null) {

		if (is_object($content)) {
			$this->content = $content;

		} else {
			$this->content = new stdClass();
		}
	}


	/**
	 * Magic set
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function __set ($key, $value) {

		$this->content->{$key} = $value;
	}


	/**
	 * Magic get
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function __get ($key) {

		if (isset($this->content->{$key})) {
			return $this->content->{$key};
		}

		return null;
	}


	/**
	 * Method accessor
	 *
	 * @return int
	 */
	public function Method () {}


	/**
	 * Header accessor
	 *
	 * @return string
	 */
	public function Header ($key) {}


	/**
	 * Headers accessor
	 *
	 * @return array
	 */
	public function Headers () {}


	/**
	 * Content accessor
	 *
	 * @return mixed
	 */
	public function Content () {

		return $this->content;
	}

}
