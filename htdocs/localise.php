<?php
/*
 * localise.php (c) Shish 2006
 *
 * load localisations from name=value file
 */

$lang = "en";

if(isset($_GET["lang"]) && (strlen($_GET["lang"]) == 2) && (file_exists("../lang/{$_GET['lang']}.txt"))) {
	$lang = $_GET["lang"];
}

$words = Array();
$fp = fopen("../lang/$lang.txt", "r");
while($line = fgets($fp)) {
	$row = explode("=", $line, 2);
	if(isset($row[1])) $words[$row[0]] = trim($row[1]);
}
fclose($fp);
?>
