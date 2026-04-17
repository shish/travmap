<?php
declare(strict_types=1);

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
		return ucfirst($parts[count($parts) - 3]);
	}
	if (count($parts) >= 2) {
		// Return first domain part if only 2 parts (e.g., "travian" from "ts5.travian.com")
		return ucfirst($parts[count($parts) - 2]);
	}
	// Fallback to the server name itself
	return ucfirst($serverName);
}


function quotesplit(string $splitter = ",", string $string = ""): array {
	$result = [];
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
 *
 * SECURITY: Uses parameterized queries to prevent SQL injection
 */
function getMatches(string $col, string $name): string {
	global $casen, $table, $db;

	if($casen) {
		return $db->quote($name);
	}

	// Normalize the name for case-insensitive matching
	$name = urlencode($name);
	$name = str_replace("%C2", "", $name);
	$name = str_replace("%E2%84", "", $name);

	$allowed_chars = ["[", "]", "{", "}", "(", ")", "<", ">", "\\", "/",
	                  ".", ",", "?", "!", "$", "^", "&", "*", "-", "_",
	                  "+", "=", ":", ";", "@", "~", "#", "%", " "];

	foreach($allowed_chars as $ok) {
		$name = str_replace(urlencode($ok), $ok, $name);
	}
	$name = preg_replace("/%[0-9A-F][0-9A-F]/", "_", $name);

	// Use parameterized query to prevent SQL injection
	$stmt = $db->prepare("SELECT $col FROM $table WHERE $col LIKE :name GROUP BY $col");
	$stmt->execute(['name' => $name]);

	$matches = [];
	foreach($stmt->fetchAll() as $row) {
		$matches[] = $db->quote($row[$col]);
	}

	return count($matches) > 0 ? implode(", ", $matches) : $db->quote($name);
}


/*
 * some maths
 */
function in(float $v, float $min, float $max): bool {
	return ($v >= $min && $v <= $max);
}

function bound(float $v, float $min, float $max): float {
	return ( in($v, $min, $max) ? $v : ($v < $min ? $min : $max) );
}


function wwwcmp(string $a, string $b): int {
	$as = explode(".", $a);
	$bs = explode(".", $b);
	$ae = count($as)-1;
	$be = count($bs)-1;
	for ($i = min($ae, $be); $i >= 0; $i--) {
		# if segment matches "x\d+", skip it
		if (preg_match('/^x\d+$/', $as[$i]) && preg_match('/^x\d+$/', $bs[$i])) {
			continue;
		}
		$cmp = strcmp($as[$i], $bs[$i]);
		if ($cmp !== 0) {
			return $cmp;
		}
	}
	return 0;
}
