<?php
	include ("./Mysql_DB.php");
	include ("dbCon.php");
	include ("./Logger.php");
	$error_log = '/home/qta422/log/mysql_error.txt';

	//exec("split -n l/25 '/home/qta422/qta422.csv' /home/qta422/parts/qta422");
	
	$files = scandir("/home/qta422/parts");
	$files = array_diff($files, array('..', '.'));
	$wc = 0;

	try {
		$logger = new Logger('', ...$logger_key);
		$logger->query("INSERT INTO `tracker` ( filename, linenumber, startingLine) VALUES ( 'aa',1, 1)");
		for ($i = 3; $i < 17; $i+=1){
			$wc += exec('cat /home/qta422/parts/'.$files[$i-1]. ' | wc -l') + 1;
			echo $files[$i].' starts at '.$wc;		
			$file = substr($files[$i], -2);
			echo "\r\n";
			$logger->query("INSERT INTO `tracker` (filename, linenumber, startingLine) VALUES ('$file', $wc, $wc)");
			}
	} catch (mysqli_sql_exception $e){
			echo $e;
			error_log("\r\n$e\r\n", 3, $error_log);
			exit(-1);
	}
?>