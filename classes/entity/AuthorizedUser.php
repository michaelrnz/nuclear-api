<?php
/*
 *		AuthorizedUser.php
 *
 *		Copyright 2011 Ryan <altaokami@gmail.com>
 *		Nuclear Framework
 *		Revised 2010
 *
 *		==========================================
 *			Authorized user entity
 */

class AuthorizedUser extends UserObject implements iSingleton
{
	private static $_instance;
	protected $_properties;
	protected $auth_type;
	protected $auth_data;

	function __construct( $id, $name, $domain )
	{
		parent::__construct( $id, $name, $domain );

		if( is_null(self::$_instance) )
			self::$_instance = $this;
	}

	function __get( $f )
	{
		if( $f == 'auth_type' )
			return $this->auth_type;

		$v  = parent::__get( $f );

		if( is_null( $v ) && is_array( $this->auth_data ) && isset( $this->auth_data[$f] ) )
			return $this->auth_data[$f];

		return $v;
	}

	public static function getInstance()
	{
		return self::$_instance;
	}

	public static function setInstance( &$object )
	{
		if( is_a( $object, "AuthorizedUser" ) )
			self::$_instance = $object;

		return self::$_instance;
	}

	public function setAuthorization( $type, $data )
	{
		$this->auth_type	= $type;
		$this->auth_data	= $data;
	}

	public function isLocal()
	{
		return isType( 'nuclear|cookie|basic', $this->auth_type );
	}
}