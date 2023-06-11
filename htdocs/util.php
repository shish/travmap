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
	global $casen, $table;

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

	$result = sql_query("SELECT $col FROM $table WHERE $col LIKE '$name' GROUP BY $col");
	$ret = "";
	$n = 0;
	while($row = sql_fetch_row($result)) {
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
function dot($im, $x, $y, $col, $s=5) {
	global $black, $imagefilledellipse, $imageellipse;
	$s *= 2;
	$imagefilledellipse($im, $x, $y, $s, $s, $col);
	$imageellipse($im, $x, $y, $s, $s, $black);
}
