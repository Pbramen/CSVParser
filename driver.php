<?php

	$files = scandir("/home/qta422/parts");
	$files = array_diff($files, array('..', '.'));
	$n = count($files);
	try {
		for ($i = 3; $i < $n; $i+=1){
			exec('/usr/bin/php /home/qta422/src/parseCSV.php '.$files[$i].' >>/home/qta422/output.txt 2>&1 &');
			}
	} catch (mysqli_sql_exception $e){
			echo $e;
			error_log("\r\n$e\r\n", 3, $error_log);
			exit(-1);
	}




	try{
		foreach($files as $file){
			exec('/usr/bin/php /home/qta422/src/parseCSV.php '.$file.' >>/home/qta422/output.txt 2>&1 &');
		}
	} catch (Exception $e){
		echo $e;
	}
?>