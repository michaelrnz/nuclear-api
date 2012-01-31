<?php
/*
 * Object
 *
 * Copyright 2011 Ryan <altaokami@gmail.com>
 * Nuclear Framework
 * Revised 2011
 *
 * ==========================================
 * Base Object class
 */

class Object {
	
	/**
	 * Constructor
	 * 
	 * @param Iterator data
	 * @return void
	 */
	function __construct ($data=null) {

		if (is_string($data)) {
			$dataStr = $data;
			$data = array();
			parse_str($dataStr, $data);
		}
		if( !is_null($data) )
			$this->merge( $data );
	}


	/**
	 * Merge an iterator with this.
	 *
	 * @param Iterator data
	 * @return Object
	 */
	public function merge ($data) {

		if( is_array($data) )
			$data = (object) $data;

		if( is_object($data) )
		{
			foreach( $data as $f=>$v )
			{
				if( is_numeric( $f ) )
					continue;

				$this->$f = $v;
			}
		}

		return $this;
	}
	
}
