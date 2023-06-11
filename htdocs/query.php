<?php
/*
 * query.php (c) Shish 2006
 *
 * generate and run an SQL query and leave the
 * result lying around for render.php to pick up
 */

require_once "options.php";
require_once "database.php"; # required for getMatches


 // query start {{{
/*
 * Build the query
 * o)  single table is considerably (3-4 times) faster than joins :-/
 */

if($groupby == "group") {
	$guild_group = "CASE WHEN guild_name='' THEN owner_name ELSE guild_name END AS guild_group";
}
else {
	$guild_group = "1";
}

$query = "
	SELECT x, y, x-y AS diag, population, race, 
		owner_name, owner_id,
		guild_name, guild_id,
		town_name, town_id,
		$guild_group
	FROM $table
	WHERE 1=1 
";
// }}}

// lists -> SQL {{{

/*
 * input:
 *   id:123, moo, flarg, "a,b,c", "id:42"
 *
 * output:
 *   pre_name IN ("moo", "flarg", "a,b,c") OR pre_id IN (123, 42)
 */
function list2query(string $str, string $pre): ?string {
	$names = Array();
	$ids = Array();

	$list = quotesplit(",", $str);
	$list = array_map("sql_escape_string", $list);
		
	foreach($list as $al) {
		if(preg_match("/^id:\d+$/", $al)) $ids[] = substr($al, 3);
		else {
			$a = getMatches("{$pre}_name", $al);
			if($a) $names[] = $a;
			else $names[] = "'$al'";
		}
	}
	
	$q = "";

	if(count($names) > 0) 
		$q .= "{$pre}_name IN(".join(", ", $names).")";
	if(count($names) > 0 && count($ids) > 0)
		$q .= " OR ";
	if(count($ids) > 0) 
		$q .= "{$pre}_id IN(".join(", ", $ids).")";

	return strlen($q) > 0 ? $q : null;
}

$alliance_query = list2query($alliance, "guild");
$player_query = list2query($player, "owner");
$town_query = list2query($town, "town");


if($alliance_query || $player_query || $town_query) $query .= "AND (";
$query .= $alliance_query;
if($alliance_query && $player_query) $query .= " OR ";
$query .= $player_query;
if(($alliance_query || $player_query) && $town_query) $query .= " OR ";
$query .= $town_query;
if($alliance_query || $player_query || $town_query) $query .= ") ";

// }}}

// population {{{
if($minpop) {
	$query .= "AND population >= $minpop ";
}
if($maxpop) {
	$query .= "AND population <= $maxpop ";
}
//  }}}

// location {{{

if(empty($zz)) $zz = 1;
$query .= "
	AND x > (-256/$zz) + ($zx)
	AND x < ( 256/$zz) + ($zx)
	AND y < ( 256/$zz) + ($zy)
	AND y > (-256/$zz) + ($zy)
";

if($maxdist) {
	$query .= "
		AND pow(x-($zx), 2) + pow(y-($zy), 2) <= pow($maxdist , 2)
		AND x > ($zx - $maxdist)
		AND x < ($zx + $maxdist)
		AND y > ($zy - $maxdist)
		AND y < ($zy + $maxdist)
	";
}
if($mindist) {
	$query .= "AND pow(x-($zx), 2) + pow(y-($zy), 2) >= pow($mindist, 2) ";
}
// }}}

// order {{{
switch($order) {
	case "population": $query .= "ORDER BY population DESC "; break;
	case "race": $query .= "ORDER BY race "; break;
	case "dist": $query .= "ORDER BY ((x-($zx))*(x-($zx))+(y-($zy))*(y-($zy))) "; break;
	case "x": $query .= "ORDER BY x "; break;
	case "y": $query .= "ORDER BY -y "; break;

	default:
		if($colby == "alliance") $query .= "ORDER BY guild_id,diag ";
		else if($colby == "race") $query .= "ORDER BY race,diag ";
		else if($lines) $query .= "ORDER BY diag ";
		break;
}
// }}}

// limit {{{
$query .= "LIMIT 5000 ";
// }}}


if($_GET["debug"] == "on") {
	print "<p><b>Query:</b> $query";
}
