<?php
/*
 * localise.php (c) Shish 2006
 *
 * load localisations from name=value file
 */

$lang = null;

if(isset($_GET["lang"])) {
	switch($_GET["lang"]) {
		case 'cn': $glang = 'zh'; break;
		case 'cz': $glang = 'cs'; break;
		case 'dk': $glang = 'da'; break;
		case 'se': $glang = 'sv'; break;
		default:   $glang = $_GET["lang"]; break;
	}
	if((strlen($glang) == 2 || strlen($glang) == 5) && (file_exists("./lang/$glang.txt"))) {
		$lang = $glang;
	}
}

if(is_null($lang) && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
	$al = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
	$options = explode(',', str_replace(';', ',', $al));
	foreach($options as $option) {
		if(file_exists("./lang/$option.txt")) {
			$lang = $option;
			break;
		}
	}
}

if(is_null($lang)) {
	$lang = "en";
}

$words = Array();
$fp = fopen("./lang/$lang.txt", "r");
while($line = fgets($fp)) {
	$row = explode("=", $line, 2);
	if(isset($row[1])) $words[$row[0]] = trim($row[1]);
}
fclose($fp);
