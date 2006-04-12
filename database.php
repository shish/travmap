<?php
/*
 * database.php (c) Shish 2006
 *
 * open the database
 */

require_once "options.php";

if($newdb) {
	mysql_connect("localhost", "travmap", "dsfkgavs");
	mysql_select_db("travmap");
	function sql_fetch_row($result) {return mysql_fetch_assoc($result);}
	function sql_escape_string($text) {return mysql_escape_string($text);}
	function sql_query($query) {return mysql_query($query);}
}
else {
	$db = sqlite_open("db/$server.db");
	function sql_fetch_row($result) {return sqlite_fetch_array($result);}
	function sql_escape_string($text) {return sqlite_escape_string($text);}
	function sql_query($query) {global $db; return sqlite_query($db, $query);}
}
?>
