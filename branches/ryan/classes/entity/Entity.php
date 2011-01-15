<?php
/*
 *		Entity.php
 *
 *		Copyright 2010 Ryan <altaokami@gmail.com>
 *		Nuclear Framework
 *		Revised 2010 Winter
 *
 *		Entity
 *		==========================================
 *			Abstract which represents a userland
 * 			identity.
 */

abstract class Entity
{

	protected $type;
	protected $id;
	protected $name;
	protected $domain;


	function __construct ($type, $id, $name, $domain=null) {
		
		$this->type		= $type;
		$this->id		= $id;
		$this->name		= $name;
		$this->domain	= is_null($domain) ? get_global('DOMAIN') : $domain;
	}


	function __get( $f )
	{
		switch( $f )
		{
			case 'type':	return $this->type;
			case 'id':		return $this->id;
			case 'name':	return $this->name;
			case 'domain':  return $this->domain;
		}

		return null;
	}


	public function tag ($section=false) {
		$sec = $section ? $seciton . ":" : "";
		return "tag:{$this->domain},". date("Y") .":{$sec}{$this->type}:{$this->id}";
	}
	
}