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
	protected $headers;
	protected $content;


	/**
	 * Construct - auto-load
	 *
	 * @return void
	 */
	function __construct () {

		$this->headers = array();
		$this
			->loadMethod()
			->loadHeaders()
			->loadContent();
	}


	/**
	 * Set the request method
	 *
	 * @return Request
	 */
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


	/**
	 * Generate HTTP headers from _SERVER
	 *
	 * @return Request
	 */
	protected function loadHeaders () {

		$this->headers = new Headers();

		// collect the headers via majksner at gmail dot com (php.net - getallheaders)
		foreach ($_SERVER as $key => $value) {
			if (substr($key,0,5) == 'HTTP_') {
				$key = str_replace(" ","-",
					ucwords(
						strtolower(
							str_replace("_"," ",substr($key,5)))));

				$this->headers->Set($key, $value);
			}
		}

		return $this;
	}


	/**
	 * Generate content object via ContentFactory
	 *
	 * @return Request
	 */
	protected function loadContent () {

		$this->content = ContentFactory::getInstance()
							->Build($this->Header("Content-Type"))
							->Content($this->Body())
							->Content();

		return $this;
	}


	/**
	 * Method accessor
	 *
	 * @return int
	 */
	public function Method () {

		return $this->method;
	}


	/**
	 * Header accessor
	 *
	 * @return string
	 */
	public function Header ($key) {

		return $this->headers->Get($key);
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


	/**
	 * Body
	 *
	 * @return string
	 */
	public function Body () {

		// determine content
		switch ($this->method) {

			case self::GET:
			case self::DELETE:

				return $_SERVER['QUERY_STRING'];

			default:

				// TODO - handle FILE uploads
				if ($this->Header("Content-Type") == "multipart/form-data") {
					return null;
				} else {
					return file_get_contents("php://input");
				}
		}
	}

}
