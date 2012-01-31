<?php
/*
 * Preferences
 *
 * Copyright 2011 Ryan <altaokami@gmail.com>
 * Nuclear Framework
 * Revised 2011
 *
 * ==========================================
 * Singleton for Nuclear's preference storage
 *
 * TODO: use a database abstraction
 */

class Preferences {

	private static $_pref_table = 'nu_preference';
	private static $_instance = null;
	private $db;
	

	/**
	 * Constructor
	 * @return void
	 */
	private function __construct () {

		$this->db = Database::getInstance();
	}


	/**
	 * Get singleton
	 * @return void
	 */
	public static function getInstance () {

		if( !(self::$_instance instanceof self) )
			self::$_instance = new self();
			
		return self::$_instance;
	}


	/**
	 * Test the existence of a preference
	 * hopfully uses the inno index
	 *
	 * @param id
	 * @param label
	 * @return bool
	 */
	public function exists ($id, $label) {

		$q	=  "select count(id) as exists from ". self::$_pref_table ."
				where id={$id} && label='{$label}'";

		$d	=  $this->db->single($q,"Unabled to assert preference");
		return $d->exists > 0;
	}
	

	/**
	 * Select binary object by id-label
	 *
	 * @param id
	 * @param label
	 * @return mixed
	 */
	public function getBlob ($id, $label) {
		
		$q =	"select blob_store
				from ". self::$_pref_table ."
				where id={$id} && label='{$label}'
				limit 1;";

		if( $d = $this->db->single($q,"Unable to get preference") )
			return unserialize($d['blob_store']);

		return null;
	}


	/**
	 * Insert binary object by id-label
	 *
	 * @param id
	 * @param label
	 * @param value
	 * @return void
	 */
	public function setBlob ($id, $label, $value) {

		$blob_store = safe_slash(serialize($value));
		if( $this->exists($id,$label) )
		{
			$q = 	"update ". self::$_pref_table ."
					set blob_store='{$blob_store}'
					where id={$id} && label='{$label}'
					limit 1";
		}
		else
		{
			$q = 	"insert into ". self::$_pref_table ."
					(id, label, blob_store)
					values ({$id},'{$label}','{$blob_store}')";
			//$q.= " on duplicate key update blob_store=values(blob_store);";
		}
		
		$this->db->void($q, "Unable to set preference");
	}


	/**
	 * JSON storage of preference
	 *
	 * @param id
	 * @param label
	 * @return stdClass
	 */
	public function getObject ($id, $label) {

		$q = 	"select blob_store
				from ". self::$_pref_table ."
				where id={$id} && label='{$label}'
				limit 1";

		if( $d = $this->db->single($q,"Unable to get preference") )
			return json_decode($d['blob_store']);

		return null;
	}


	/**
	 * JSON encode preference in blob
	 *
	 * @param id
	 * @param label
	 * @param value
	 * @return void
	 */
	public function setObject ($id, $label, $value) {

		$blob_store = safe_slash(json_encode($value));
		if( $this->exists($id,$label) )
		{
			$q = 	"update ". self::$_pref_table ."
					set blob_store='{$blob_store}'
					where id={$id} && label='{$label}'
					limit 1";
		}
		else
		{
			$q = 	"insert into ". self::$_pref_table ."
					(id, label, blob_store)
					values ({$id},'{$label}','{$blob_store}')";
			//$q.= " on duplicate key update blob_store=values(blob_store);";
		}
		
		$this->db->void($q, "Unable to set preference");
	}


	/**
	 * Select integer preference
	 *
	 * @param id
	 * @param label
	 * @return int
	 */
	public function getInteger ($id,$label) {
		
		$q = 	"select int_store
				from ". self::$_pref_table ."
				where id={$id} && label='{$label}'
				limit 1";

		if( $d = $this->db->single($q,"Unable to get preference") )
			return $d['int_store'];

		return null;
	}


	/**
	 * Store integer preference
	 *
	 * @param id
	 * @param label
	 * @param int_store
	 * @return void
	 */
	public function setInteger ($id,$label,$int_store) {

		if( $this->exists($id,$label) )
		{
			$q = 	"update ". self::$_pref_table ."
					set int_store='{$int_store}'
					where id={$id} && label='{$label}'
					limit 1";
		}
		else
		{
			$q = 	"insert into ". self::$_pref_table ."
					(id, label, int_store)
					values ({$id},'{$label}','{$int_store}')";
		}

		$this->db->void($q, "Unable to set preference");
	}


	/**
	 * Increment an integer preference
	 *
	 * @param id
	 * @param label
	 * @param inc
	 * @return void
	 */
	public function increment ($id, $label, $inc=1) {

		if( $this->exists($id,$label) )
		{
			$q = 	"update ". self::$_pref_table ."
					set int_store=int_store+{$inc}
					where id={$id} && label={$label}
					limit 1";
					
			$this->db->void($q, "Unable to set preference");
		}
	}


	/**
	 * Decrement an integer preference
	 *
	 * @param id
	 * @param label
	 * @param dec
	 * @return void
	 */
	public function decrement ($id, $label, $dec=1) {

		if( $this->exists($id,$label) )
		{
			$q = 	"update ". self::$_pref_table ."
					set int_store=int_store-{$dec}
					where id={$id} && label={$label}
					limit 1";
					
			$this->db->void($q, "Unable to set preference");
		}
	}


	/**
	 * Delete a preference
	 *
	 * @param id
	 * @param label
	 * @return void
	 */
	public function delete ($id, $label) {

		$q = 	"delete from ". self::$_pref_table ."
				where id={$id} && label='{$label}'
				limit 1;";
		
		$this->db->void($q, "Unable to delete preference");
	}
	
}
