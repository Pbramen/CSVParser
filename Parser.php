<?php

class Parser extends Mysql_DB{
	//prepared Statements
	private $p_insertSN;
	private $p_insertRelation;
	private $p_selectSN;
	//binded params
	private $sn;
	private $device;
	private $company;

	
	function __construct(...$args){
		parent::__construct(...$args);
		
		// initiate the prepared statements
		$this->p_insertSN = $this->prepare("INSERT IGNORE INTO `sn` (sn) VALUES (?)");
		$this->p_insertRelation= $this->prepare("INSERT IGNORE INTO `relation` (sn_id, device_id, company_id) VALUES (?, ?, ?)");
		$this->p_selectSN = $this->prepare("SELECT sn_id FROM `sn` WHERE sn = ?");
		//bind variables
		$this->p_insertSN->bind_param("s", $this->sn);
		$this->p_insertRelation->bind_param("iii", $this->sn, $this->company, $this->device);
		$this->p_selectSN->bind_param("s", $this->sn);
	}
	

	function insertItem($table, $value){
		$this->query("INSERT INTO `$table` ($table) VALUES ('$value')");
		return $this->insert_id;
	}

	function insertSN($sn){
		$this->sn = $sn;
		$this->p_insertSN->execute();
		return $this->insert_id;
	}
	
	function insertRelation($device_id, $company_id, $sn_id){
		$this->device = $device_id;
		$this->company = $company_id;
		$this->sn = $sn_id;
		$this->p_insertRelation->execute();
	}
	
	function checkUnquieSN($value){
		$this->sn = $value;
		$this->p_selectSN->execute();
		$this->p_selectSN->bind_result($res);
		$this->p_selectSN->store_result();
		$this->p_selectSN->fetch();
		return $this->p_selectSN->num_rows();
	}
	
	// only for devices and company: do not call on sn!
	function getFields($table){
		$arr = array();
		$id = $table.'_id';
		$res = $this->query("Select * from $table");
		if ($res->num_rows > 0){
			while($row = $res->fetch_assoc()){
				$arr[$row[$table]] = $row[$id];
			}
		}
		return $arr;
	}
	
}


?>