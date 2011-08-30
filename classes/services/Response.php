<?php
/*
 * Response
 *
 * Copyright 2011 Ryan <altaokami@gmail.com>
 * Nuclear Framework
 * Revised 2011
 *
 * ==========================================
 * Default response class (Mehod return)
 */

class Response implements IResponse {


	/**
	 * @var int status (HTTP status)
	 */
	protected $status	= 200;


	/**
	 * @var Header headers (HTTP headers)
	 */
	protected $headers;


	/**
	 * @var mixed content
	 */
	protected $content;


	/**
	 * Construct init headers
	 * @return void
	 */
	public function __construct () {

		$this->headers = new Headers();
	}


	/**
	 * Status accessor
	 *
	 * @return int
	 */
	public function Status () {

		return $this->status;
	}


	/**
	 * Header accessor
	 *
	 * @param string key
	 * @return string
	 */
	public function Header ($key) {

		return $this->headers->Get($key);
	}


	/**
	 * Headers accessor
	 *
	 * @return Header
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
	 * Magic Set
	 *
	 * @param string key
	 * @param mixed value
	 * @return void
	 */
	public function __set ($key, $value) {

		switch ($key) {

			case 'status':
				$this->status = (int) $value;
				break;

			case 'content':
				$this->content = $value;
				break;

		}
	}


	/**
	 * Default string; return is to json_encode self
	 * 
	 * @return string
	 */
	public function __toString () {

		// Check for generic content
		if (empty($this->content)) {
			return "";

		} else if ($this->content instanceof IGeneric) {
			$content = $this->content->toGeneric();

		} else {
			$content = $this->content;

		}

		// Hint the string conversion
		if ($this->content instanceof DOMDocument) {
			return $this->content->saveXML();

		} else if (is_object($this->content) || is_array($this->content)) {
			return json_encode($this->content);

		} else if (is_scalar($this->content)) {
			return (string) $this->content;

		}

		return "";
	}

}
