<?php
/*
 * database.php (c) Shish 2006
 *
 * open the database
 */

require_once "config.php";

$db = pg_pconnect("host=$sql_host user=$sql_user password=$sql_pass dbname=$sql_db");
if(!$db) throw new Exception("Failed to connect to database");
pg_query($db, "SET client_encoding = 'UTF8';");
function sql_fetch_row($result) {return pg_fetch_assoc($result);}
function sql_escape_string(string $text): string {global $db; return pg_escape_string($db, $text);}
function sql_query(string $query) {
	global $db;
	$result = @pg_query($db, $query) or die(
		"<h3>PgSQL Error:</h3>".
		"<b>Failed Query:</b> $query".
		"<p><b>Error:</b> ".pg_last_error($db)
	);
	return $result;
}
/*
mysql_pconnect($sql_host, $sql_user, $sql_pass);
mysql_select_db($sql_db);
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
