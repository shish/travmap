<?php
/*
 * database.php (c) Shish 2006
 *
 * open the database
 */

require_once "config.php";

# pg_pconnect("host=$mysql_host user=$mysql_user password=$mysql_pass dbname=$mysql_db");
pg_pconnect("user=$mysql_user password=$mysql_pass dbname=$mysql_db");
pg_query("SET client_encoding = 'UTF8';");
function sql_fetch_row($result) {return pg_fetch_assoc($result);}
function sql_escape_string($text) {return pg_escape_string($text);}
function sql_query($query) {
	$result = pg_query($query) or die(
		"<h3>PgSQL Error:</h3>".
		"<b>Failed Query:</b> $query".
		"<p><b>Error:</b> ".pg_last_error()
	);
	return $result;
}
/*
mysql_pconnect($mysql_host, $mysql_user, $mysql_pass);
mysql_select_db($mysql_db);
mysql_query("SET NAMES utf8");
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
*/
?>