<?php
/*
 * Nuclear Environment
 *
 * Copyright 2011 Ryan <altaokami@gmail.com>
 * Nuclear Framework
 *
 * ==========================================
 * Here we load the base class for path
 * searching in the environment.
 * 
 */

// ENV_REFRESH - threshold for refreshing the path cache
if( !defined("NU_ENV_REFRESH") ) {
	define("NU_ENV_REFRESH", 3600);
}

// ENV_FORCE_REFRESH - threshold for forcing refresh
if( !defined("NU_ENV_FORCE_REFRESH") ) {
	define("NU_ENV_FORCE_REFRESH", 60);
}

// define access time in micro
define('NU_ENV_MICROTIME', microtime(true));

// ENV_PATH - path to nuclear source
define("NU_ENV_PATH", dirname(__FILE__));

// require the core functions
require_once('_core.php');

// require the path environment (auto-loading)
require_once('classes/environment/EnvironmentIndex.php');

// get singleton and add the current Nuclear path
EnvironmentIndex::getInstance()
	->setRefresh(NU_ENV_REFRESH, NU_ENV_FORCE_REFRESH)
	->addPath(NU_ENV_PATH);
