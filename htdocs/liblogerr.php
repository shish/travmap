<?php
function error_handler(int $errno, string $errstr, string $errfile, int $errline): bool {
	$halt_script = true;
	switch($errno) { 
		case E_USER_NOTICE: 
		case E_NOTICE: 
			$halt_script = false;         
			$type = "Notice";
			return true;
			break; 

		case E_USER_WARNING: 
		case E_COMPILE_WARNING: 
		case E_CORE_WARNING: 
		case E_WARNING: 
			$halt_script = false;        
			$type = "Warning"; 
			break;

		case E_USER_ERROR: 
		case E_COMPILE_ERROR: 
		case E_CORE_ERROR: 
		case E_ERROR: 
			$type = "Fatal Error"; 
			break; 

		case E_PARSE: 
			$type = "Parse Error"; 
			break; 

		default: 
			$type = "Unknown Error";
			break;
	}

	error_log("Error $errno ($type) at $errfile:$errline: $errstr");
	/*
	$fp = fopen("../logs/error.log", "a");
	$errfile = basename($errfile);
	fputs($fp, "Error $errno ($type) at $errfile:$errline: $errstr\n");
	fclose($fp);
	*/

	if($halt_script) {
		ob_end_clean();
		echo <<<EOD
<html>
	<head><title>Error Report</title></head>
	<body>
		<b>Error report</b>
		<br><b>Location:</b> $errfile:$errline
		<br><b>Code:</b> $errno/$type:$errstr
	</body>
</html>
EOD;

		exit;
	}

	return true;
}
set_error_handler("error_handler");
