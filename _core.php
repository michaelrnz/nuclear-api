<?php
/**
 * Copyright 2012 Ryan <altaokami@gmail.com>
 * Nuclear Framework
 * Revised 2012
 */


/**
 * Determine if TYPE is in string set
 *
 * @param string $group
 * @param string $type
 * @return bool
 */
function is_type ($group, $type) {
	return !(strpos("|{$group}|","|{$type}|")===false);
}


/**
 * Destructive escape slash
 *
 * @param string $f
 * @return string
 */
function safe_slash ($f) {
	$find = array("/\\\+'/","/([^\\\])\\\([^'\\\])/");
	$rep = array("\'",'\1\\\\\\\\\2');
	return preg_replace( $find, $rep, str_replace("'","\'", str_replace('\\', '\\\\', $f)) );
}


/**
 * Destructive unescape slash
 *
 * @param string $f
 * @return string
 */
function safe_unslash ($f) {
	return str_replace("\'", "'", $f);
}


/**
 * Convert native base64 to url version
 *
 * @param string $input
 * @return string
 */
function base64url_encode ($input) {
	return strtr( base64_encode( $input ), "+/", "-_" );
}


/**
 * Convert from url version base64 encode
 *
 * @param string $input
 * @return string
 */
function base64url_decode ($input) {
	return base64_decode( strtr( $input, '-_', '+/' ) );
}


/**
 * Convert decimal to a given base-size by lib
 *
 * @param string $dec
 * @param string $lib
 * @return string
 */
function to_base ($dec, $lib="0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz") {
	$code = "";
	$base = strlen($lib);

	while ($dec>0) {
	    $m      = (int) bcmod($dec, $base);
	    $code  .= substr($lib, $m, 1);
	    $dec    = bcdiv($dec, $base, 0);
	}

	return strrev($code);
}


/**
 * Convert from an arbitrary base defined by lib to dec
 *
 * @param string $alpha
 * @param string $lib
 * @return string
 */
function from_base ($alpha, $lib="0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz") {
	$dec    = 0;
	$base   = strlen($lib);
	$len    = strlen($alpha);

	for($a=0; $a<$len; $a++)
	{
	    $p      = ($len - ($a+1));
	    $c      = substr($alpha, $a, 1);
	    $dec    = bcadd( $dec, bcmul( strpos($lib,$c), bcpow($base, $p, 0) ) );
	}

	return $dec;
}


/** 
 * Convert decimal to hex using bc
 *
 * @param int $dec
 * @return string
 */
function to_hex ($dec) {
	return to_base( $dec, "0123456789ABCDEF" );
}


/**
 * Convert hex to decimal using bc
 *
 * @param string $hex
 * @return string
 */
function from_hex ($hex) {
	return from_base( strtoupper($hex), "0123456789ABCDEF" );
}

