<?php
/*
 * Nuclear Environment
 *
 * Copyright 2011 Ryan <altaokami@gmail.com>
 * Nuclear Framework
 * Revised 2010
 *
 * ==========================================
 * Here we load the base class for path
 * searching in the environment.
 * 
 */

// ENV_REFRESH - threshold for refreshing the path cache
if( !defined("ENV_REFRESH") )
	define("ENV_REFRESH", 3600);

// ENV_FORCE_REFRESH - threshold for forcing refresh
if( !defined("ENV_FORCE_REFRESH") )
	define("ENV_FORCE_REFRESH", 60);

// require the path environment (auto-loading)
require_once('classes/pathenvironment.php');

// get singleton and add the current Nuclear path
PathEnvironment::getInstance()->addPath(dirname(__FILE__));