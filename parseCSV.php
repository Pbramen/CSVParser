<?php
	
	include('./dbCon.php');
	include('./helperFunctions.php');
	include('./Mysql_DB.php');
	include('./Logger.php');
	include('./Parser.php');

	$error_log = '/home/qta422/log/mysql_error.txt';
	$filename = $argv[1];
	$lineNumber = 0;
	date_default_timezone_set("America/Chicago");

	
	try{
		$logger = new Logger($filename, ...$logger_key);
		$parser = new Parser($filename, ...$parser_key);	
	} catch (RuntimeException $e){
		echo $e;
		error_log("\r\n$e\r\n", 3, $error_log);
		exit(-1);
	}
	
	try{
		// php caches valid devices/companies as program
		$deviceMap = $parser->getFields("device");
		$companyMap = $parser->getFields("company");
		
		$lineNumber = $logger->checkTracker() -1;
		$lastEntry = $logger->lastViewedEntry();
	} catch(Mysqli_sql_exception $e){
		$logger->insertSysErr("Tracking failed: ".$e->getMessage(), $filename);
		exit();
	}
	$path = '/home/qta422/parts/'.$filename;
	if(!file_exists($path)){
		$logger->insertSysErr("File does not exist",  $path);
		exit();
	}
	if(!($fp = fopen($path, "r"))){
		$logger->insertSysErr("FP failed to open.",  $filename);
		exit();
	}
	try{
		while (($line = fgets($fp)) != false ){
			$lineNumber +=1;
			
			if($lineNumber < $lastEntry){
				continue;
			}
			
			$time_start = microtime(true);
			$line = trim($line);
			$n = strlen($line);
			
			$params = array(
				0 =>'',
				1 => '',
				2 => ''
			);
			$index = 0;
			$extra = false;
			$empty = true;
			
			//trim starting and ending ',' and records if extra ',' is found
			for ($i = 0 ; $i<$n; $i+=1){
				$cur = $line[$i];
				if(($i == 0 || $i == $n-1) && $cur == ',' ){
					$extra == true;
				}
				else if($cur == ','){
					$index +=1;
					if ($index >= 3){
						echo "Extra parameters found!";
						exit();
					}
				}
				else{
					$empty = false;
					$params[$index] .= $cur;
				}
			}
			if ($empty){
				$logger->insertErrorWarning(1, "Blank line", $lineNumber, $line);
			} 
			else{
				if ($index == 1){
					if (strlen($params[1]) <= 32){
						//device and company present, no sn
						$logger->insertErrorWarning(1, "sn missing", $lineNumber, $line);
					}
					else{
						//company and sn present, no device
						$logger->insertErrorWarning(1, "device missing", $lineNumber, $line);
					}
				}
				else if ($index == 2){
					if($params[0] == ''){
						$logger->insertErrorWarning(1, "device missing",$lineNumber, $line);
					}
					else if($params[1] == ''){
						$logger->insertErrorWarning(1, "company missing",$lineNumber, $line);
					}
					else if($params[2] == ''){
						$logger->insertErrorWarning(1, "sn missing",$lineNumber, $line);
					}
					else{
						// all fields are present here
						
						$sn = $params[2];
						// check for valid sn
						$res = checkSNString($sn);
						if ($res >= 2){
							$logger->insertErrorWarning(1, "sn format invalid", $lineNumber, $sn);
							continue;
						}
						else if($res == 1){
							$logger->insertErrorWarning(0, "Reformated sn", $lineNumber, $line);
						}
						
						if($parser->checkUnquieSN($sn) != 0){
							$logger->insertErrorWarning(1, "Duplicate SN", $lineNumber, $line);
						}
						
						$sn_id = $parser->insertSN($sn);
						// grab device_id and company_id
						if( isset($deviceMap[$params[0]]) ){
							$device_id = $deviceMap[$params[0]];
						} 
						else {
							$extraChar = checkAlpha($params[0], $device);
							if($extraChar){
								$logger->insertErrorWarning(0, "Extra ' in device", $lineNumber, $line);
							}
							$device_id = $deviceMap[$device];
							
							if (!isset($deviceMap[$device])){
								//insert new device and cache!
								$device_id = $parser->insertItem("device", $device);
								$deviceMap[$device] = $device_id;
							}
						}
						
						if(isset($companyMap[$params[1]]) ){
							$company_id = $companyMap[$params[1]];
						}
						else{
							$extraChar = checkAlpha($params[1], $company);
							if($extraChar){
								$logger->insertErrorWarning(0, "Extra ' in company", $lineNumber, $line);
							}
							if(!isset($companyMap[$company])){
								$company_id = $parser->insertItem("company", $company);
								$companyMap[$company] = $company_id;
							}
						}
						
						// insert new relation!
						$parser->insertRelation($device_id, $company_id, $sn_id);
	
					}
				}
			}
			$exec_time = (microtime(true) - $time_start) / 60;
			$logger->updateTracker($exec_time);
		}
	} 
	catch (MySQLi_Sql_Exception $mse){
		// mysql failure has occured -> log to file
		error_log("\r\n$mse\r\n", 3, $error_log);
		exit(-2);
	}
	catch (Exception $e){
		error_log("\r\n$mse\r\n", 3, $error_log);
		exit(-1);
	}
?>