<?php
/*
 * util.php (c) Shish 2006
 *
 * functions used in other places
 */


/*
 * because php randomly decided to treat "&foo=" as "" rather than null :-/
 */
function getString(string $name, ?string $default): ?string {
	return (isset($_GET[$name]) && strlen($_GET[$name]) > 0) ? $_GET[$name] : $default;
}
function getFloat(string $name, ?float $default): ?float {
	return (isset($_GET[$name]) && strlen($_GET[$name]) > 0) ? (float)$_GET[$name] : $default;
}
function getInt(string $name, ?int $default): ?int {
	return (isset($_GET[$name])) ? (int)$_GET[$name] : $default;
}
function getBool(string $name): bool {
	return (isset($_GET[$name]) && ($_GET[$name] == "on"));
}


/*
 * Extract subdomain from server name
 * e.g. "x1.america.travian.com" -> "america"
 * e.g. "ts5.travian.com" -> "travian"
 */
function getSubdomain(string $serverName): string {
	$parts = explode(".", $serverName);
	if (count($parts) >= 3) {
		// Return second-to-last domain part (e.g., "america" from "x1.america.travian.com")
		return ucfirst($parts[count($parts) - 2]);
	}
	if (count($parts) >= 2) {
		// Return first domain part if only 2 parts (e.g., "travian" from "ts5.travian.com")
		return ucfirst($parts[count($parts) - 2]);
	}
	// Fallback to the server name itself
	return ucfirst($serverName);
}


function quotesplit(string $splitter=",", string $string=""): array {
	$result = Array();
	$parts = explode($splitter, $string);
	$instring = 0;

	foreach($parts as $part) {
		$part = trim($part);

		# starting and ending a string
		if(($instring == 0) && preg_match('/^"/', $part) &&
				preg_match('/"$/', $part)) {
			$result[] = trim($part, '"');
		}
		
		# starting a string
		else if(($instring == 0) && preg_match('/^"/', $part)) {
			$instring = 1;
			$result[] = ltrim($part, '"');
		}

		# ending a string
		else if(($instring == 1) && preg_match('/"$/', $part)) {
			$instring = 0;
			$result[count($result)-1] .= ($splitter . rtrim($part, '"'));
		}

		# in a string
		else if($instring == 1) {
			$result[count($result)-1] .= ($splitter . $part);
		}

		# blank
		else if($part == "") {
			# leave it; anyone who *really* wants to search for blank can use ""
		}
		
		# not in a string
		else {
			$result[] = $part;
		}
	}

	return $result;
}




/*
 * get a list of all things in a column (eg user, alliance) which match a
 * query, and return them in a form suitable for putting in an IN(...)
 * statement
 */
function getMatches($col, $name) {
	global $casen, $table, $db;

	if($casen) return "'$name'";

	$name = urlencode($name);
	$name = str_replace("%C2", "", $name);
	$name = str_replace("%E2%84", "", $name);
	foreach(Array("[", "]", "{", "}", "(", ")", "<", ">", "\\", "/",
	              ".", ",", "?", "!", "$", "^", "&", "*", "-", "_",
 	              "+", "=", ":", ";", "@", "~", "#", "%", " ") as $ok) {
		$name = str_replace(urlencode($ok), $ok, $name);
	}
	$name = preg_replace("/%[0-9A-F][0-9A-F]/", "_", $name);

	/* no matching characters = no point matching */
	/* if(strpos($name, "%") === false && strpos($name, "_") === false) return "'$name'"; */

	$result = $db->query("SELECT $col FROM $table WHERE $col LIKE '$name' GROUP BY $col");
	$ret = "";
	$n = 0;
	foreach($result->fetchAll() as $row) {
		if($n++) $ret .= ", ";
		$ret .= "'{$row[$col]}'";
	}
	return $ret;
}


/*
 * some maths
 */
function in($v, $min, $max) {return ($v >= $min && $v <= $max);}
function bound($v, $min, $max) {return ( in($v, $min, $max) ? $v : ($v < $min ? $min : $max) );}


/*
 * Draw a village / key entry's marker
 */
function dot($im, float $x, float $y, $col, float $s=5) {
	global $black, $imagefilledellipse, $imageellipse;
	$s *= 2;
	$imagefilledellipse($im, (int)$x, (int)$y, (int)$s, (int)$s, $col);
	$imageellipse($im, (int)$x, (int)$y, (int)$s, (int)$s, $black);
}
