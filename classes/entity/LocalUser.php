<?php
/*
 *		LocalUser.php
 *
 *		Copyright 2010 Ryan <altaokami@gmail.com>
 *		Nuclear Framework
 *		Revised 2010 Winter
 *
 *		LocalUser
 *		==========================================
 *			Local-domain user entity
 */

class LocalUser extends UserObject implements iSingleton
{
	private static $_instance;
	protected $email;

	function __construct( $id, $name, $email )
	{
		parent::__construct( $id, $name, get_global('DOMAIN') );

		$this->email = $email;

		if( is_null(self::$_instance) )
			self::$_instance = $this;
	}

	function __get( $f )
	{
		if( $f == 'email' ) return $this->email;

		return parent::__get( $f );
	}

	public static function getInstance()
	{
		return self::$_instance;
	}

	public static function setInstance( &$object )
	{
		if( $object instanceof LocalUser ) )
			self::$_instance = $object;

		return self::$_instance;
	}
}