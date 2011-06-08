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
	 * @var array headers (HTTP headers)
	 * @var mixed content
	 */
	protected $status	= 200;
	protected $headers	= array();
	protected $content;


	/**
	 * Status accessor
	 *
	 * @param int status
	 * @return int
	 */
	public function Status ($status=null) {

		if (is_numeric($status)) {
			$this->status = intval($status);
			return $this;

		} else {
			return $this->status;

		}
	}


	/**
	 * Header accessor
	 *
	 * @param string key
	 * @param string value
	 * @return string
	 */
	public function Header ($key, $value=null) {

		if (empty($value)) {
			return empty($this->headers[$key]) ?
				null : $this->headers[$key];

		} else {
			$this->header[$key] = (string) $value;
			return $this;

		}

	}


	/**
	 * Headers accessor
	 *
	 * @param array headers
	 * @return array
	 */
	public function Headers ($headers=null) {

		if (is_array($headers)) {
			foreach ($headers as $key=>$value) {
				$this->Header($key, $value);
			}

			return $this;

		} else {
			return $this->headers;

		}
	}


	/**
	 * Content accessor
	 *
	 * @param mixed content
	 * @return mixed
	 */
	public function Content ($content=null) {

		if (is_null($content)) {
			return $this->content;

		} else {
			$this->content = content;
			return $this;

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

		} else if (is_string($this->content)) {
			return $this->content;

		}

		throw new Exception("Unknown response content", 500);
	}

}
