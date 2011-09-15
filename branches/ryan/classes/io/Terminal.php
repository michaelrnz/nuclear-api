<?php
/*
 * Terminal
 *
 * Copyright 2011 Ryan <altaokami@gmail.com>
 * Nuclear Framework
 * Revised 2011
 *
 * ==========================================
 * Terminal base class - managed output
 */

abstract class Terminal {


	/**
	 * Writer method - to be implemented
	 * @return Terminal
	 */
	abstract public function Write ($content);

}