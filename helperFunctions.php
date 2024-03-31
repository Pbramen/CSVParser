<?php
	

	function checkSNString(&$sn){
		$err= 0;
		if(($s = substr($sn, 0, 3)) != "SN-"){
			$err +=1;
			$sn = "SN-".substr($sn,3);
		}
		$res = preg_match("/[^abcdefABCDEFG\d]/", substr($sn, 3), $matches, PREG_OFFSET_CAPTURE);
		if ($res){
			//invalid hexstring
			$err += 2;
		}
		return $err;
	}
	
	
	function checkAlpha($string, &$res){
		$n = strlen($string);
		$res = '';
		$r = false;
		for ($i = 0; $i < $n; $i+=1){
			if((ctype_alpha($string[$i]) || $string[$i] == ' ')){
				$res .= $string[$i];
			}else{
				$r = true;
			}
		}
		return $r;
	}

?>