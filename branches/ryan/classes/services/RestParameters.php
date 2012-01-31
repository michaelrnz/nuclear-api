<?php
/*
 * RestParameters
 *
 * Copyright 2011 Ryan <altaokami@gmail.com>
 * Nuclear Framework
 * Revised 2011
 *
 * ==========================================
 * Represet REST parameters as a set
 */

class RestParameters {


	/**
	 * Component Types
	 */
	const COMPONENT_ARRAY = 1;
	const COMPONENT_OBJECT = 2;


	/**
	 * Constructor
	 * 
	 * @param string data
	 * @return void
	 */
	function __construct ($data=null) {

		if (is_string($data)) {

			// process REST parameters via encoded string
			foreach (array_filter(explode("&", $data)) as $parameter) {
				$this->Parameter($parameter);
			}
		}
	}


	/**
	 * Process a given parameter (e.g. key=val)
	 *
	 * @param string
	 * @return void
	 */
	protected function Parameter ($parameter) {

		// explode the parameter
		$parameter = explode("=", $parameter, 2);

		// get the key/value
		$key		= trim(urldecode($parameter[0]));
		$value		= urldecode($parameter[1]);
		$components	= array_filter(explode(".", $key));

		// check for complex properties
		foreach ($components as &$c) {
			$c = $this->Component($c);
		}

		$this->Expand($this, $components, $value);
	}


	/**
	 * Expand
	 *
	 * @param stdClass $node
	 * @param array $components
	 * @param string $value
	 * @return stdClass
	 */
	protected function Expand ($node, &$components, $value) {

		$parent = &$node;
		$count = count($components);
		foreach ($components as $def) {

			$key = $def->key;

			// base case
			if ($count-- == 1) {

				// assign as array
				if ($def->type == self::COMPONENT_ARRAY) {

					// create or overwrite as array
					if (!(isset($parent->{$key}) && is_array($parent->{$key}))) {
						$parent->{$key} = array();
					}

					// push
					if (strlen($def->index)==0) {
						array_push($parent->{$key}, $value);

					// associate
					} else {
						$arr = &$parent->{$key};
						$arr[$def->index] = $value;
					}

				// assign to array
				} else if (is_array($parent)) {
					array_push($parent, $value);

				// assign to object
				} else if (is_object($parent)) {
					$parent->{$key} = $value;
				}

				return $node;
			}

			// assign value
			if ($def->type == self::COMPONENT_ARRAY) {

				// ensure array
				if (!(isset($parent->{$key}) && is_array($parent->{$key}))) {
					$parent->{$key} = array();
				}

				$arr = &$parent->{$key};
				$arr[$def->index] = new stdClass();
				$parent = $arr[$def->index];

			// object component
			} else {
				if (!isset($parent->{$key})) {
					$parent->{$key} = new stdClass();
				}

				$parent = $parent->{$key};
			}
		}

		// return the last node in the hierarchy
		return $node;
	}


	/**
	 * Component
	 *
	 * @param string $key
	 * @param string $index
	 * @param int $type
	 * @return stdClass
	 **/
	protected function Component ($key, $index=null, $type=self::COMPONENT_OBJECT) {

		// check for array keying []
		if (substr($key,-1)==']' && ($left = strpos($key,'['))) {

			// array index string
			$index = substr($key, $left+1, strlen($key) - $left - 2);

			if (strlen($index)>0) {
				$key = substr($key, 0, $left);
				$type = self::COMPONENT_ARRAY;
			} else {
				$index = null;
			}
		}

		return (object) array("type"=>$type, "key"=>$key, "index"=>$index);
	}

}
