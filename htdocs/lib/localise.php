<?php
/*
 * localise.php (c) Shish 2006
 *
 * load localisations from name=value file
 */

function valid_lang(string $lang): bool {
    if(strlen($lang) != 2 && strlen($lang) != 5) return false;
    if(!preg_match('/^[a-z]{2}(-[A-Z]{2})?$/', $lang)) return false;
    return file_exists("./lang/$lang.txt");
}

function get_lang() {
	$lang = null;

	// allow ?lang=xx to override browser settings, but only if the file exists
	if(isset($_GET["lang"])) {
		switch($_GET["lang"]) {
			case 'cn': $glang = 'zh'; break;
			case 'cz': $glang = 'cs'; break;
			case 'dk': $glang = 'da'; break;
			case 'se': $glang = 'sv'; break;
			default:   $glang = $_GET["lang"]; break;
		}
		if(valid_lang($glang)) {
			$lang = $glang;
		}
	}

	// if not, try the browser settings
	if(is_null($lang) && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
		$al = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		$options = explode(',', str_replace(';', ',', $al));
		foreach($options as $option) {
			if(valid_lang($option)) {
				$lang = $option;
				break;
			}
		}
	}

	// default to english
	if(is_null($lang)) {
		$lang = "en";
	}

	return $lang;
}

function get_words() {
	$lang = get_lang();
	$words = Array();
	$fp = fopen("./lang/$lang.txt", "r");
	while($line = fgets($fp)) {
		$row = explode("=", $line, 2);
		if(isset($row[1])) $words[$row[0]] = trim($row[1]);
	}
	fclose($fp);
	return $words;
}
