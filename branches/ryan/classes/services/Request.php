<?php
/*
 * Response
 *
 * Copyright 2011 Ryan <altaokami@gmail.com>
 * Nuclear Framework
 * Revised 2011
 *
 * ==========================================
 * Default request class (Mehod return)
 */

class Request implements IRequest {

	const GET		= 1;
	const POST		= 2;
	const PUT		= 3;
	const DELETE	= 4;


	/**
	 * @var int method (HTTP request method)
	 * @var array headers (HTTP headers)
	 * @var mixed content
	 */
	protected $method;
	protected $headers = array();
	protected $content;


	function __construct () {

		$this
			->loadMethod()
			->loadHeaders()
			->loadContent();
	}


	protected function loadMethod () {

		// set the method
		switch ($_SERVER['REQUEST_METHOD']) {

			case 'GET':
				$this->method = self::GET;
				break;

			case 'POST':
				$this->method = self::POST;
				break;

			case 'PUT':
				$this->method = self::PUT;
				break;

			case 'DELETE':
				$this->method = self::DELETE;
				break;

			default:
				$this->method = self::GET;
				break;
		}

		return $this;
	}


	protected function loadHeaders () {

		// collect the headers via majksner at gmail dot com (php.net - getallheaders)
		foreach ($_SERVER as $key => $value) {
			if (substr($key,0,5) == 'HTTP_') {
				$key = str_replace(" ","-",
					ucwords(
						strtolower(
							str_replace("_"," ",substr($key,5)))));

				$this->headers[$key] = $value;
			}
		}

		return $this;
	}


	protected function loadContent () {

		// determine content
		switch ($this->method) {

			case self::GET:
				// create basic content object from GET
				$this->content = new Object($_GET);
				break;

			default:
				// check content-type
				switch ($this->Header("Content-Type")) {

					case "application/x-www-form-urlencoded":
					case "multipart/form-data":
					default:
						$this->content = new Object($_REQUEST);
						break;
				}
				break;
		}

		return $this;
	}


	/**
	 * Method accessor
	 *
	 * @param int method
	 * @return int
	 */
	public function Method ($method=null) {

		if (is_numeric($method)) {
			$this->method = intval($method);
			return $this;

		}

		return $this->method;
	}


	/**
	 * Header accessor
	 *
	 * @return string
	 */
	public function Header () {

		return empty($this->headers[$key]) ?
				null : $this->headers[$key];
	}


	/**
	 * Headers accessor
	 *
	 * @return array
	 */
	public function Headers () {

		return $this->headers;
	}


	/**
	 * Content accessor
	 *
	 * @return mixed
	 */
	public function Content () {

		return $this->content;
	}

}
