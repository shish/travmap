<?php
/*
 * database.php (c) Shish 2006
 *
 * open the database
 */

require_once "config.php";

$db = new \PDO("sqlite:../data/travmap.sqlite");
if(!$db) throw new Exception("Failed to connect to database");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
