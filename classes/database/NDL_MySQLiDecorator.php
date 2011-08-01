<?php
/*
 * MySQL NDLQuery decorator
 *
 * Copyright 2011 Ryan <altaokami@gmail.com>
 * Nuclear Framework
 * Revised 2011
 *
 * ==========================================
 * 
 */

class NDL_MySQLiDecorator {


	public function Format ($query) {

		$data				= new stdClass();
		$data->operation	= $query->operation;
		$data->keys			= implode(", ", $query->keys);
		$data->values		= implode(", ", $query->values);
		$data->context		= implode(" ", $query->context);
		$data->conditions	= implode(" && ", $query->conditions);
		$data->group		= (count($query->group)>0 ? "GROUP BY " . implode(", ", $query->group) : null);
		$data->order		= (count($query->order)>0 ? "ORDER BY " . implode(", ", $query->order) : null);
		$data->limit		= null;

		if ($query->limit) {
			list($limit, $offset, $page) = $query->Paging($query->limit, $query->page);
			$data->limit = "LIMIT {$limit},{$offset}";
		}

		return $data;
	}


	public function Select ($data) {

		$where = empty($data->conditions) ? "" : "WHERE {$data->conditions}";

		$sql = "
			SELECT
				{$data->keys}
			FROM
				{$data->context}
			{$where}
			{$data->group}
			{$data->order}
			{$data->limit}";

		return $sql;
	}


	public function Insert ($data) {

		$sql = "
			INSERT INTO
				{$data->context}
				({$data->keys})
			VALUES
				({$data->values})";

		return $sql;
	}


	public function Update ($data) {
	}


	public function Delete ($data) {
	}


	public function SQL (NDLQuery $query) {

		$data = $this->Format($query);

		switch ($query->operation) {

			case NDLQuery::SELECT:
				return $this->Select($data);

			case NDLQuery::INSERT:
				return $this->Insert($data);

			case NDLQuery::UPDATE:
				return $this->Update($data);

			case NDLQuery::DELETE:
				return $this->Delete($data);

			default:
				return "";
		}
	}

}
