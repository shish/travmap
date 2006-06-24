<?php
/*
 * database.php (c) Shish 2006
 *
 * open the database
 */

require_once "options.php";

$using_data_cache = false; 


if($datacache && file_exists($datacache)) {
	$using_data_cache = true;
	$db = sqlite_open($datacache);
	
	// yes. this is true. sqlite has no "if".
	function sqlite_if($a, $b, $c) {return $a ? $b : $c;}
	sqlite_create_function($db, 'if', 'sqlite_if', 3);

	function sql_fetch_row($result) {return sqlite_fetch_array($result);}
	function sql_escape_string($text) {return sqlite_escape_string($text);}
	function sql_query($query) {global $db; return sqlite_query($db, $query);}
}
else {
	require_once "config.php";
	mysql_pconnect($mysql_host, $mysql_user, $mysql_pass);
	mysql_select_db($mysql_db);
	function sql_fetch_row($result) {return mysql_fetch_assoc($result);}
	function sql_escape_string($text) {return mysql_escape_string($text);}
	function sql_query($query) {
		$result = mysql_query($query) or die(
			"<h3>MySQL Error:</h3>".
			"<b>Failed Query:</b> $query".
			"<p><b>Error:</b> ".mysql_error()
		);
		return $result;
	}
}
?>
