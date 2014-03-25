<?php
/*
 * Terminal
 *
 * Copyright 2011 Ryan <altaokami@gmail.com>
 * Nuclear Framework
 * Revised 2011
 *
 * ==========================================
 * FileWriter
 */

class FileWriter extends Terminal {


	protected $res;


	public function __construct ($name, $mode="w") {

		if ($this->verifyWriteMode($mode)) {
			$this->res = @fopen($name, $mode);

			if ($this->res === false) {
				throw new Exception("");
			}
		}
	}


	protected function verifyWriteMode ($mode) {		
	}


	public function Write ($content) {
	}


}