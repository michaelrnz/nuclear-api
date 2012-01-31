<?php
/*
 * ContentHandler
 *
 * Copyright 2011 Ryan <altaokami@gmail.com>
 * Nuclear Framework
 * Revised 2011
 *
 * ==========================================
 * Default content handler for REST requests
 * 
 */

class ContentHandler implements IContentHandler {


	/**
	 * @var RestParameters
	 */
	protected $content;



	/**
	 * Content accessor
	 *
	 * @param string content
	 * @return RestParameters
	 */
	public function Content ($content=null) {

		if (is_null($content)) {
			return $this->content;
		}

		$this->content = new RestParameters($content);
		return $this;
	}


}
