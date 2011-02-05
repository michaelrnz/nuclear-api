<?php
/*
 * Scheduler
 *
 * Copyright 2011 Ryan <altaokami@gmail.com>
 * Nuclear Framework
 * Revised 2011
 *
 * ==========================================
 * module for inserting data to queue table
 * to be used via an async method
 * (via NuFiles::ping, async socket GET)
 */

class Scheduler {

	/*
	 * @var _instance
	 * @var _table
	 * @var db
	 */
	protected static $_instance;
	protected static $_table = 'nu_queue';
	protected $db;


	/**
	 * Constructor
	 * @return void
	 */
	private function __construct () {

		$this->db = Database::getInstance();
	}

	
	/**
	 * Get singleton
	 * @return Scheduler
	 */
	public static function getInstance () {
		
		if( self::$_instance instanceof self )
			self::$_instance = new self();
		
		return self::$_instance;s
	}
	

	/**
	 * Set a Scheduler instance as singleton
	 *
	 * @param scheduler
	 * @return Scheduler
	 */
	public static function setInstance (&$scheduler) {

		if( $scheduler instanceof Scheduler )
			self::$_instance = $scheduler;
		
		return self::$_instance;
	}
	
	
	/**
	 * Queue an object into the database
	 *
	 * @param label
	 * @param object
	 * @return int
	 */
	public function queue ($label, $object) {

		$label      = safe_slash( $label );
		$data       = safe_slash( serialize( $object ) );
		
		$id = $this->db->id(
			"insert into ". self::$_table ." (label, data) ".
			"values ('{$label}', '{$data}')",
			"Unable to queue object"
		);
		
		return $id;
	}
	

	/**
	 * Unqueue an object by id/label
	 *
	 * @param id
	 * @param label
	 * @return mixed
	 */
	public function unqueue ($id, $label) {
		
		$q      = new NuSelect(self::$_table . " Q");
		$q->where("id={$id}");
		$q->where("label='{$label}'");
		
		$data = $q->single();

		if($data)
			$this->db->void("delete from ". self::$_table ." where id={$id} limit 1");
		
		return unserialize($data['data']);
	}


	/**
	 * Dispatch a schedule to url
	 *
	 * @param id
	 * @param url
	 * @return void
	 */
	public function dispatch ($id, $uri) {

		sNuFiles::ping( "{$uri}?id={$id}" );
	}

}
