<?php
/*
 * query.php (c) Shish 2006
 *
 * generate and run an SQL query and leave the
 * result lying around for render.php to pick up
 */

require_once "options.php";
require_once "database.php"; # required for getMatches


/*
 * Build the query
 * o)  single table is considerably (3-4 times) faster than joins :-/
 */
$query = "
	SELECT x, y, x-y AS diag, population, race, 
		owner_name, owner_id,
		guild_name, guild_id, 
		town_name, town_id
	FROM $table
	WHERE 1=1 
";

if(!is_null($alliance) || !is_null($player) || !is_null($town)) {
	$query .= "AND (";
}

if(!is_null($alliance)) {
	$alliances = quotesplit(",", sql_escape_string($alliance));
	$query .= "guild_name IN(";
	$n = 0;
	foreach($alliances as $alliance) {
		$alliance = trim($alliance);
		if(strncmp($alliance, "id:", 3) == 0) $items = id2name("guild_id", "guild_name", substr($alliance, 3), $table);
		else $items = getMatches("guild_name", $alliance);
		if(strlen($items) > 0) {
			if($n++) $query .= ", ";
			$query .= $items;
		}
	}
	if(preg_match('/\($/', $query)) $query .= "null";
	$query .= ")";
}
if(!is_null($player)) {
	if(!is_null($alliance)) $query .= " OR ";
	$players = quotesplit(",", sql_escape_string($player));
	$query .= "owner_name IN(";
	$n = 0;
	foreach($players as $player) {
		$player = trim($player);
		if(strncmp($player, "id:", 3) == 0) $items = id2name("owner_id", "owner_name", substr($player, 3), $table);
		else $items = getMatches("owner_name", $player);
		if(strlen($items) > 0) {
			if($n++) $query .= ", ";
			$query .= $items;
		}
	}
	if(preg_match('/\($/', $query)) $query .= "null";
	$query .= ")";
}
if(!is_null($town)) {
	if(!is_null($alliance) || !is_null($player)) $query .= " OR ";
	$towns = quotesplit(",", sql_escape_string($town));
	$query .= "town_name IN(";
	$n = 0;
	foreach($towns as $town) {
		$town = trim($town);
		if(strncmp($town, "id:", 3) == 0) $items = id2name("town_id", "town_name", substr($town, 3), $table);
		else $items = getMatches("town_name", $town);
		if(strlen($items) > 0) {
			if($n++) $query .= ", ";
			$query .= $items;
		}
	}
	if(preg_match('/\($/', $query)) $query .= "null";
	$query .= ")";
}
if(!is_null($alliance) || !is_null($player) || !is_null($town)) {
	$query .= ") ";
}

if($minpop) {
	$query .= "AND population >= '$minpop' ";
}
if($maxpop) {
	$query .= "AND population <= '$maxpop' ";
}

if($zx != 0 || $zy != 0 || $zz != 1) {
	$query .= "
		AND x > (-256/$zz) + ($zx)
		AND x < ( 256/$zz) + ($zx)
		AND y < ( 256/$zz) + ($zy)
		AND y > (-256/$zz) + ($zy)
	";
}

switch($order) {
	case "population": $query .= "ORDER BY population DESC "; break;
	case "race": $query .= "ORDER BY race "; break;
	case "dist": $query .= "ORDER BY ((x-($zx))*(x-($zx))+(y-($zy))*(y-($zy))) "; break;
	case "x": $query .= "ORDER BY x "; break;
	case "y": $query .= "ORDER BY y "; break;

	default:
		if($colby == "alliance") $query .= "ORDER BY guild_id,diag ";
		else if($colby == "race") $query .= "ORDER BY race,diag ";
		else if($lines) $query .= "ORDER BY diag ";
		break;
}

$query .= "LIMIT 2500 ";


if($_GET["debug"] == "on") {
	print "<p><b>Query:</b> $query";
}
?>
