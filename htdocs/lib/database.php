<?php
/*
 * database.php (c) Shish 2006
 *
 * open the database
 */

require_once "config.php";

$db = new \PDO($sql_dsn);
if(!$db) throw new Exception("Failed to connect to database");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
