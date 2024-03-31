<?php

class Logger extends Mysql_DB{
	//prepared Statements
	private $p_insertException;
	private $p_insertWarning;
	private $p_insertSystem;
	private $p_updateTracker;
	
	//binded params
	protected $lineNumber;
	protected $exec_time;
	protected $type;
	protected $line;
	protected $shortname;
	public function __construct(...$args){
		parent::__construct(...$args);
		$this->shortname = substr($this->filename, -2);
		// initiate the prepared statements
		$this->p_insertException = $this->prepare("INSERT IGNORE INTO `parse_error` (type, lineNumber, fileName, e_line) VALUES (?, ?, ?, ?)");
		$this->p_insertWarning = $this->prepare("INSERT IGNORE INTO `parse_warnings` (type, lineNumber, fileName, e_line) VALUES (?, ?, ?, ?)");
		$this->p_insertSystem = $this->prepare("INSERT IGNORE INTO `sys_fail` (type, filename, exec_date) VALUES (?, ?, ?)");
		$this->p_updateTracker= $this->prepare("UPDATE `tracker` SET lineNumber = lineNumber + 1, exec_time = exec_time + ? WHERE filename=?");
		//bind variables
		$this->p_insertException->bind_param("siss", $this->type, $this->lineNumber, $this->shortname, $this->line);
		$this->p_insertWarning->bind_param("siss",  $this->type, $this->lineNumber, $this->shortname, $this->line);
		$this->p_insertSystem->bind_param("sss", $this->type, $this->filename, $this->exec_time);
		$this->p_updateTracker->bind_param("ds", $this->exec_time, $this->filename);
	}
	

	// These functions need to be wrapped in a try/catch block for mysqli_sql_exception!
	function insertErrorWarning($code, $type, $lineNumber, $line){
		$this->type = $type;
		$this->lineNumber = $lineNumber;
		$this->line = $line;
		//warning
		if($code == 0){
			$this->p_insertWarning->execute();
			return;
		}
		$this->p_insertException->execute();
	}
	
	// NOTE: program shuts down after sys_err occurs - ok to override filename.
	function insertSysErr($type, $filename){
		$this->filename = $filename;
		$this->exec_time = date('Y-m-d H:i:s');
		$this->type=$type;
		$this->p_insertSystem->execute();
	}
	
	function updateTracker($exec_time){
		$this->exec_time = $exec_time;
		$this->p_updateTracker->execute();
	}
	
	function checkTracker(){
		$res = $this->query("Select startingLine from `tracker` where filename = '$this->filename'");
		if($res->num_rows >= 1){
			$row = $res->fetch_assoc();
			return $row['startingLine'];
		}
		// unable to pick up where last started... sys fail.
		else {
			throw new Mysqli_sql_exception("Unable to query startingLine");
		}
	}
	
	function lastViewedEntry(){
		$res = $this->query("Select lineNumber from `tracker` where filename = '$this->filename'");
		if($res->num_rows >= 1){
			$row = $res->fetch_assoc();
			return $row['lineNumber'];
		}
		//unable to pick up ... sys fail.
		else {
			throw new Mysqli_sql_exception("Unable to query linenumber");
		}
	}
	
}
?>