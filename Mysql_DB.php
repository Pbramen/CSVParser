<?php
	class Mysql_DB extends mysqli{
		protected $filename;
		
		public function __construct($filename, $user, $pass, $db){
			$this->filename = substr($filename, -2);
			
			parent::__construct("localhost", $user, $pass, $db);
			mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
			if ($this->connect_errno){
				throw new RuntimeException('mysql failed to connect: '.$this->conn->connect_error);
			}
		}
	
		
	}

?>