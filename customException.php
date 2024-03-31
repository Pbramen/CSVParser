<?php

	class PreparedInvalid extends Exception{
		function __construct($message, $code){
			parent::__construct($message, $code);
		}
	}

?>