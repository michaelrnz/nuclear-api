<?php
/*
 * ContentFactory
 *
 * Copyright 2011 Ryan <altaokami@gmail.com>
 * Nuclear Framework
 * Revised 2011
 *
 * ==========================================
 * 
 */

class ContentFactory extends Factory {


	/**
	 * @var ContentFactory
	 */
	protected static $instance;


	/**
	 * Singleton implementation
	 *
	 * @return ContentFactory
	 */
	public static function getInstance () {

		if (!(self::$instance instanceof self)) {
			self::$instance = new self("IContentHandler");
		}

		return self::$instance;
	}


	/**
	 * Build override
	 * -default to ContentHandler for REST
	 *
	 * @param string type
	 * @return IContentHandler
	 */
	public function Build ($type) {

		switch ($type) {

			case "":
			case "application/x-www-form-urlencoded":
			case "multipart/form-data":
				return new ContentHandler();

			default:
				return parent::Build($type);
		}
	}


}
