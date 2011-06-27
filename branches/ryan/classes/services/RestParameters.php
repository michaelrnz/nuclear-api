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

class RestParameters extends stdClass {


	/**
	 * Constructor
	 * 
	 * @param Iterator data
	 * @return void
	 */
	function __construct ($data=null) {

		if (is_string($data)) {

			// process REST parameters via encoded string
			foreach (array_filter(explode("&", $data)) as $parameter) {
				$this->parameter($parameter);
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
		$key	= trim(urldecode($parameter[0]));
		$value	= urldecode($parameter[1]);

		// .property depths
		$properties	= array_filter(explode(".", $key));

		if (count($properties)==1) {
			$this->Assign($this, $properties[0], $value);

		} else {

			$final = array_pop($properties);
			$this->Assign($this->Expand($this, $properties), $final, $value);
		}
	}


	/**
	 * Assign a key to a node, do not display values
	 *
	 * @param stdClass node
	 * @param string key
	 * @param string value
	 * @return void
	 */
	protected function Assign ($node, $key, $value) {

		if (isset($node->{$key})) {
			if (!is_array($node->{$key})) {
				$node->{$key} = array($node->{$key});
			}

			array_push($node->{$key}, $value);

		} else {
			$node->{$key} = $value;
		}
	}


	/**
	 * Expand a node with property hierarchy
	 *
	 * @param stdClass node
	 * @param array properties
	 * @return stdClass
	 */
	protected function Expand ($node, &$properties) {

		$last = $node;
		foreach ($properties as $p) {

			if (!isset($last->{$p})) {
				$last->{$p} = new stdClass();

			} else if (!($last->{$p} instanceof stdClass)) {
				if (!is_array($last->{$p})) {
					$last->{$p} = array($last->{$p});
				}

				$item = new stdClass();
				array_push($last->{$p}, $item);
				$last = $item;
				continue;
			}

			$last = $last->{$p};
		}

		// return the last node in the hierarchy
		return $last;
	}


}
