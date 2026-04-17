<?php
declare(strict_types=1);

/*
 * database.php (c) Shish 2006
 *
 * open the database
 */

$db = new \PDO("sqlite:../data/travmap.sqlite");
if(!$db) throw new Exception("Failed to connect to database");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
