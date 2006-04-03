<?php
/*
 * localise.php (c) Shish 2006
 *
 * load localisations from name=value file
 */

$lang = $_GET["lang"];

if((strlen($lang) != 2) || (!file_exists("lang/$lang.txt"))) {
	$lang = "en";
}

$words = Array();
$fp = fopen("lang/$lang.txt", "r");
while($line = fgets($fp)) {
	$row = explode("=", $line, 2);
	$words[$row[0]] = trim($row[1]);
}
fclose($fp);
?>
