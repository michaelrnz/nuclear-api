<?php
/*
 *		UserObject.php
 *
 *		Copyright 2010 Ryan <altaokami@gmail.com>
 *		Nuclear Framework
 *		Revised 2010 Winter
 *
 *		UserObject
 *		==========================================
 *			Container for userland entity
 */

abstract class UserObject extends Entity
{
	protected $prefs;

	function __construct( $id, $name, $domain=null )
	{
		parent::__construct( 'user', $id, $name, $domain );
		$this->prefs = Preferences::getInstance();
	}

	public function setInteger( $label, $value )
	{
		$this->prefs->setInteger( $this->id, $label, $value );
		return $this;
	}

	public function getInteger( $label )
	{
		return $this->prefs->getInteger( $this->id, $label );
	}

	public function setBlob( $label, $value )
	{
		$this->prefs->setBlob( $this->id, $label, $value );
		return $this;
	}

	public function getBlob( $label )
	{
		return $this->prefs->getBlob( $this->id, $label );
	}

	public function setObject( $label, $value )
	{
		$this->prefs->setObject( $this->id, $label, $value );
		return $this;
	}

	public function getObject( $label )
	{
		return $this->prefs->getObject( $this->id, $label );
	}
}