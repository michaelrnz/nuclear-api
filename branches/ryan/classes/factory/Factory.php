<?php
/*
 * Response
 *
 * Copyright 2011 Ryan <altaokami@gmail.com>
 * Nuclear Framework
 * Revised 2011
 *
 * ==========================================
 * 
 */

class Factory implements IFactory {


	/**
	 * @var array handlers
	 */
	protected $handlers;


	/**
	 * @var string basetype
	 */
	protected $parentClass;


	/**
	 * Initialize the handlers and set baseType
	 *
	 * @param string parentClass
	 * @return void
	 */
	public function __construct ($parentClass=false) {

		$this->handlers		= array();
		$this->parentClass	= $parentClass;
	}


	/**
	 * IFactory implementation
	 * -Construct an instance based on registered handlers
	 * -Check for type constraint
	 */
	public function Build ($type) {

		if (array_key_exists($type, $this->handlers)) {

			$className = $this->handlers[$type];
			if (class_exists($className)) {
				$instance = new $className();

				if ($this->parentClass==false || $instance instanceof $this->parentClass) {
					return $instance;
				}
			}
		}

		throw new Exception("Factory could not load instance for type '{$type}'");
	}


	/**
	 * IFactory implementation
	 */
	public function Register ($type, $handler) {

		$this->handlers[$type] = $handler;
		return $this;
	}


	/**
	 * IFactory implementation
	 */
	public function Unregister ($type) {

		if (isset($this->handlers[$type])) {
			unset($this->handlers[$type]);
		}

		return $this;
	}


}
