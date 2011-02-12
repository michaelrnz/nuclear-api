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

class Response extends ObjectContainer implements iResponse {


	/**
	 * @var int $status (relates to HTTP status codes)
	 * @var string $output (type of output)
	 */
	protected $status = 200;
	protected $output;
	

	/**
	 * Default constructor sets
	 * output type
	 */
	function __construct ($output="json") {

		$this->output = $output;
	}
	
	
	/**
	 * Default string; return is to json_encode self
	 * 
	 * @return string
	 */
	public function __toString () {
	
		return json_encode( $this );
	}
	
	
	/**
	 * Default implementation to
	 * return the response status.
	 * 
	 * @return int
	 */
	public function getStatus () {
		
		return $this->status;
	}
	
	
	/**
	 * Default implementation to
	 * set the response status.
	 * 
	 * @return Response
	 */
	public function setStatus ($status) {
		
		$this->status = $status;
		return $this;
	}
	
}
