<?php
/*
 * NDLQuery Nuclear Data Layer
 *
 * Copyright 2011 Ryan <altaokami@gmail.com>
 * Nuclear Framework
 * Revised 2011
 *
 * ==========================================
 * 
 */

class NDLQuery {

	const SELECT = 1;
	const INSERT = 2;
	const UPDATE = 3;
	const DELETE = 4;


	protected $operation;


	protected $context;


	protected $keys;


	protected $values;


	protected $conditions;


	protected $group;


	protected $order;


	protected $limit;


	protected $page;


	public function __construct () {
		$this->Reset();
	}


	public function __get ($k) {

		switch ($k) {
			case 'operation':
			case 'context':
			case 'keys':
			case 'values':
			case 'conditions':
			case 'group':
			case 'order':
			case 'limit':
			case 'page':
				return $this->{$k};
		}

		return null;
	}


	public function Reset () {

		$this->operation	= 0;
		$this->context		= array();
		$this->keys			= array();
		$this->values		= array();
		$this->conditions	= array();
		$this->group		= array();
		$this->order		= array();
	}


	public function Select ($context) {

		$this->operation = self::SELECT;
		$this->context[] = $context;
		return $this;
	}


	public function Insert ($context) {

		$this->operation = self::INSERT;
		$this->context = array($context);
		return $this;
	}


	public function Update ($context) {

		$this->operation = self::UPDATE;
		$this->context[] = $context;
		return $this;
	}


	public function Delete ($context) {

		$this->operation = self::DELETE;
		$this->context = array($context);
		return $this;
	}


	public function Get () {

		$count = func_num_args();

		for ($a=0; $a<$count; $a++) {
			$this->keys[] = (string) func_get_arg($a);
		}

		return $this;
	}


	public function Set () {

		$count = func_num_args();

		for ($a=0; $a<$count; $a++) {
			$this->values[] = (string) func_get_arg($a);
		}

		return $this;
	}


	public function Condition () {

		$count = func_num_args();

		for ($a=0; $a<$count; $a++) {
			$this->conditions[] = (string) func_get_arg($a);
		}

		return $this;
	}


	public function AndCondition () {

		$count = func_num_args();

		$conditions = array();
		for ($a=0; $a<$count; $a++) {
			$cond = func_get_arg($a);

			if (is_array($cond)) {
				$conditions[] = str_replace('?', $cond[1], $cond[0]);
			} else {
				$conditions[] = (string) $cond;
			}
		}

		return count($conditions)>0 ? "(".implode(" && ", $conditions).")" : $conditions[0];
	}


	public function OrCondition () {

		$count = func_num_args();

		$conditions = array();
		for ($a=0; $a<$count; $a++) {
			$cond = func_get_arg($a);

			if (is_array($cond)) {
				$conditions[] = str_replace('?', $cond[1], $cond[0]);
			} else {
				$conditions[] = (string) $cond;
			}
		}

		return count($conditions)>0 ? "(".implode(" || ", $conditions).")" : $conditions[0];
	}


	public function Group () {

		foreach (func_get_args() as $g) {
			$this->group[] = $g;
		}

		return $this;
	}


	public function Order () {

		foreach (func_get_args() as $g) {
			$this->order[] = $g;
		}

		return $this;
	}


	public function Limit ($limit) {

		$limit = intval($limit);
		$limit = $limit<1 ? 1 : $limit;

		$this->limit = $limit;
		return $this;
	}


	public function Page ($page) {

		$page = intval($page);
		$page = $page<1 ? 1 : $page;

		$this->page = $page;
		return $this;
	}


	public function Paging ($limit, $page) {

		$page = intval($page)<1 ? 1 : intval($page);

		return array($limit, $limit * ($page-1), $page);
	}

}
