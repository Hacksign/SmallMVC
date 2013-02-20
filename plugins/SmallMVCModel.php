<?php
class SmallMVCModel{
	private $db = null;
	private $table = null;
	function __construct($table,$poolName = null){
		if(!isset($table)){
			$e = new Exception("Table name must be set!");
			$e->type = DEBUG;
			throw $e;
		}
		$this->table = $table;
		$this->db = SMvc::instance()->controller->load->database($poolName, $this->table);
	}
	private function setTable(){
		if(empty($this->table)){
			$e = new Exception("table is empty");
			$e->type = DEBUG;
			throw $e;
		}
		$this->db->table($this->table);
		return $this;
	}
	public function insert($columns){
		if(empty($columns)){
			$e = new Exception("columns is empty");
			$e->type = DEBUG;
			throw $e;
		}
		if(!$this->setTable()->db->insert($columns)){
			return false;
		}
		return $this;
	}
	public function delete(){
		if(!$this->setTable()->db->delete()){
			return false;
		}
		return $this;
	}
	public function select($feild = null){
		if(empty($feild)){
			$e = new Exception("feiled is empty");
			$e->type = DEBUG;
			throw $e;
		}
		if(!$this->setTable()->db->select($feild))
			return false;
		return $this;
	}
	public function query($condition = null){
		if(!$this->setTable()->db->query($condition))
			return false;
		return $this;
	}
	public function query_one($condition = null){
		if(!($result = $this->setTable()->db->query_one($condition)))
			return false;
		return $result;
	}
	public function query_all($condition = null){
		if(!($result = $this->setTable()->db->query_all($condition)))
			return false;
		return $result;
	}
	
	public function find(){
		$this->setTable()->db->query();
		while($row = $this->db->next())
			$results[] = $row;
		return empty($results) ? null : $results;
	}
	public function where($feild, $condition){
		if(isset($this->db->query_params['where']))
			unset($this->db->query_params['where']);
		if($this->db->where($feild, $condition))
			return false;
		return $this;
	}
	public function join($table, $on, $type){
		$this->db->join($table, $on, $type);
		return $this;
	}
}
?>
