<?php
/*
 * map.php (c) Shish 2006
 *
 * call each of the parts of the map generation process
 */

require_once "lib/liblogerr.php";
require_once "lib/libaima.php";
require_once "lib/util.php";
require_once "lib/database.php";
require_once "lib/localise.php";      # words to use

$words = get_words();


/*
 * Parse input parameters
 */
// GET options {{{
$server   = getString("server", "s2.travian.com");
$alliance = getString("alliance", null);
$player   = getString("player", null);
$town     = getString("town", null);
$order    = getString("order", "default");
$zoom     = getString("zoom", null);
$layout   = getString("layout", "default");
$caption  = getString("caption", $words["key"]);
$groupby  = getString("groupby", "player");
$colby    = getString("colby", $groupby);

$lines = getBool("lines");
$casen = getBool("casen");
$azoom = getBool("azoom");
$nocache = getBool("nocache");

$dotsize = getFloat("dotsize", 1);

$maxdist = getInt("maxdist", null);
$mindist = getInt("mindist", null);
$maxpop = getInt("maxpop", null);
$minpop = getInt("minpop", null);

$table = preg_replace("/[^a-zA-Z0-9]/", "_", $server);
$s_server = sql_escape_string($server);
$server_info = sql_fetch_row(sql_query("SELECT * FROM servers WHERE name='$s_server'"));
if(!$server_info) {
	echo("No registered server $s_server");
	exit;
}
// }}}

// figure out where we are {{{
if($layout == "spread") {
	$cx = 768/2;
	$cy = 256;
}
else {
	$cx = 256;
	$cy = 256;
}
// }}}

/* figure out where to focus {{{
 *
 * z(a, x, y, p) = zoom (array, x, y, player)
 */

function town2xy($name) {
	global $table, $casen, $zx;

	$xy = Array();
	
	$name = sql_escape_string($name);
	if(preg_match("/^id:\d+$/", $name)) {
		$id = (int)substr($name, 3);
		$za2 = sql_fetch_row(sql_query("SELECT x,y FROM $table WHERE town_id=$id LIMIT 1"));
	}
	else {
		$cmp = $casen ? "=" : "LIKE";
		$za2 = sql_fetch_row(sql_query("SELECT x,y FROM $table WHERE town_name $cmp '$name' LIMIT 1"));
	}
	if($zx < -256) $zx = -256;
	$xy[0] = $za2['x'] ? $za2['x'] : 0;
	$xy[1] = $za2['y'] ? $za2['y'] : 0;

	return $xy;
}

$size = max(max($server_info['height'], $server_info['width']), 1);
$zx = 0; $zy = 0; $zz = 500.0 / $size;

if($zoom) {
	$za = array_map("trim", explode(",", $zoom));
	
	switch(count($za)) {
		case 3: 
			// x, y, z
			if(is_numeric($za[0])) {
				$zx = (int)$za[0]; 
				$zy = (int)$za[1];
				$zz = (float)$za[2]; 
			}
			break;
		case 2:
			// x, y
			if(is_numeric($za[0])) {
				$zx = (int)$za[0];
				$zy = (int)$za[1];
				$zz = 1; 
			}
			// name, z
			else {
				$xy = town2xy($za[0]);
				$zx = $xy[0];
				$zy = $xy[1];
				$zz = $za[1] ? (float)$za[1] : 1;
			}
			break;
		case 1:
			// z
			if(is_numeric($za[0])) {
				$zx = 0;
				$zy = 0; 
				$zz = (float)$za[0];
			}
			// name
			else {
				$xy = town2xy($za[0]);
				$zx = $xy[0];
				$zy = $xy[1];
				$zz = 1;
			}
			break;
	}

	if($zx < -512) $zx = -512;
	if($zx >  512) $zx =  512;
	if($zy < -512) $zy = -512;
	if($zy >  512) $zy =  512;
	if($zz <  0.1) $zz =  0.1;
}
// }}}


/*
 * Build query
 */
 // query start {{{
/*
 * Build the query
 * o)  single table is considerably (3-4 times) faster than joins :-/
 */

if($groupby == "group") {
	$guild_group = "CASE WHEN guild_name='' THEN owner_name ELSE guild_name END AS guild_group";
}
else {
	$guild_group = "1 AS guild_group";
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
function list2query(?string $str, string $pre): ?string {
	if(is_null($str)) return null;
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


if(getBool("debug")) {
	print "<p><b>Query:</b> $query";
}

/*
 * Run query
 */
$entities = Array();
$races = Array($words["roman"], $words["teuton"], $words["gaul"]);


/*
 * query db for villages, group by owning entity
 */
$result = sql_query($query);


while($row = sql_fetch_row($result)) {
	$user_name = $row["owner_name"];
	$user_id = $row["owner_id"];
	$guild_name = $row["guild_name"];
	$guild_id = $row["guild_id"];
	$guild_group = $row["guild_group"];
	$town_name = $row["town_name"];
	$town_id = $row["town_id"];
	$race_id = $row["race"];
	$race_name = $race_id <= count($races) ? $races[$race_id-1] : "Race $race_id";

	switch($groupby) {
		default:
		case "player":   $entity_id = $user_id;     $entity_name = $user_name;   break;
		case "alliance": $entity_id = $guild_id;    $entity_name = $guild_name;  break;
		case "group":    $entity_id = $guild_group; $entity_name = $guild_group; break;
		case "race":     $entity_id = $race_id;     $entity_name = $race_name;   break;
		case "town":     $entity_id = $town_id;     $entity_name = $town_name;   break;
	}

	if(!isset($entities[$entity_id])) {
		switch($groupby) {
			case "alliance":
				$entities[$entity_id]['link'] = "allianz.php?aid=".$row['guild_id'];
				break;
			case "player":
				$entities[$entity_id]['link'] = "spieler.php?uid=".$row['owner_id'];
				break;
			case "town":
				$entities[$entity_id]['link'] = "karte.php?z=".$row['id'];
				break;
		}
		$entities[$entity_id]['name'] = $entity_name;
		$entities[$entity_id]['guild'] = $row["guild_name"];
		$entities[$entity_id]['race_id'] = $row["race"];
		$entities[$entity_id]['count'] = 0;
	}
	else {
		$entities[$entity_id]['count']++;
	}

	$entities[$entity_id]['villages'][$entities[$entity_id]['count']]['name'] = $row['town_name'];
	$entities[$entity_id]['villages'][$entities[$entity_id]['count']]['owner'] = $row['owner_name'];
	$entities[$entity_id]['villages'][$entities[$entity_id]['count']]['guild'] = $row['guild_name'];
	$entities[$entity_id]['villages'][$entities[$entity_id]['count']]['population'] = $row['population'];
	$entities[$entity_id]['villages'][$entities[$entity_id]['count']]['x'] = $row['x'];
	$entities[$entity_id]['villages'][$entities[$entity_id]['count']]['y'] = $row['y'];
}

/*
 * Initialise image
 */
putenv('GDFONTPATH=' . realpath('.'));
$im = $imagecreatetruecolor(768, 512);
# FIXME: $imagecustom(javascript)
# imageantialias($im, true);
$white = $imagecolorallocate($im, 255, 255, 255);
$wgrey = $imagecolorallocate($im, 240, 240, 240);
$lgrey = $imagecolorallocate($im, 220, 220, 220);
$mgrey = $imagecolorallocate($im, 180, 180, 180);
$grey  = $imagecolorallocate($im, 127, 127, 127);
$dgrey = $imagecolorallocate($im, 63,  63,  63);
$black = $imagecolorallocate($im, 0,   0,   0);
$imagefill($im, 0, 0, $white);

/*
 * Hardcode our own colour table, spiralling through HSV space
 */
$ct = Array();
$s = 1.0; $v = 1.0;  for($h=0.00; $h<0.99; $h+=0.166) {$ct[] = $imagecolorallocate_hsv($im, $h, $s, $v);}
$s = 1.0; $v = 0.75; for($h=0.00; $h<0.99; $h+=0.166) {$ct[] = $imagecolorallocate_hsv($im, $h, $s, $v);}
$s = 0.5; $v = 1.0;  for($h=0.00; $h<0.99; $h+=0.166) {$ct[] = $imagecolorallocate_hsv($im, $h, $s, $v);}
$s = 1.0; $v = 1.0;  for($h=0.08; $h<0.99; $h+=0.166) {$ct[] = $imagecolorallocate_hsv($im, $h, $s, $v);}
$s = 1.0; $v = 0.75; for($h=0.08; $h<0.99; $h+=0.166) {$ct[] = $imagecolorallocate_hsv($im, $h, $s, $v);}
$s = 0.5; $v = 1.0;  for($h=0.08; $h<0.99; $h+=0.166) {$ct[] = $imagecolorallocate_hsv($im, $h, $s, $v);}

/*
 * Render globals
 */
$races = Array($words["roman"], $words["teuton"], $words["gaul"]);
$kcount = 0;
$kcount1 = 0; $kcount2 = 0; $kcount3 = 0; $kcount4 = 0;
$minx = 512; $miny = 512; $maxx = -512; $maxy = -512;
$kx = 530;


/* autozoom {{{
 * figure out where to zoom, if auto
 */
if($azoom) {
	foreach($entities as $entity) {
		foreach($entity['villages'] as $village) {
			$x = $village['x'];
			$y = $village['y'];

			if($x < $minx) {$minx = $x;}
			if($y < $miny) {$miny = $y;}
			if($x > $maxx) {$maxx = $x;}
			if($y > $maxy) {$maxy = $y;}
		}
	}
	$zx = ($minx+$maxx)/2;
	$zy = ($miny+$maxy)/2;
	
	$bigdiff = (($maxx-$minx) > ($maxy-$miny)) ? ($maxx-$minx) : ($maxy-$miny);
	if($bigdiff == 0) $bigdiff = 5;
	$zz = (450/$bigdiff);
}
// }}}

// key locations {{{
/*
 * count keys in each area
 */
$pbl = 0;
$pbr = 0;
$ptl = 0;
$ptr = 0;
if($layout == "spread") {
	foreach($entities as $entity_name => $entity) {
		$x = $cx+($entity['villages'][0]['x']-$zx)*$zz;
		$y = $cy-($entity['villages'][0]['y']-$zy)*$zz;

		if(($x < $cx) && ($y >= $cy)) {$pbl++;}
		else if(($x >= $cx) && ($y >= $cy)) {$pbr++;}
		else if(($x < $cx) && ($y < $cy)) {$ptl++;}
		else if(($x >= $cx) && ($y < $cy)) {$ptr++;}
	}
}


/*
 * figure out where each key entry should go in the key
 */
foreach($entities as $entity_name => $entity) {
	$x = $cx+($entity['villages'][0]['x']-$zx)*$zz;
	$y = $cy-($entity['villages'][0]['y']-$zy)*$zz;

	if($layout == "spread") {
		if(($x < $cx) && ($y >= $cy)) {
			$entity['y'] = 500-(($pbl-1)*15)+($kcount3++*15);
		}
		else if(($x >= $cx) && ($y >= $cy)) {
			$entity['y'] = 480-(($pbr-1)*15)+($kcount4++*15);
		}
		else if(($x < $cx) && ($y < $cy)) {
			$entity['y'] = 40+($kcount1++*15);
		}
		else if(($x >= $cx) && ($y < $cy)) {
			$entity['y'] = 40+($kcount2++*15);
		}

		if($x < $cx) {
			$entity['dx'] = 114;
			$entity['x'] = 10;
		}
		else {
			$entity['dx'] = 654;
			$entity['x'] = 662;
		}

		$entity['dy'] = $entity['y'];
	}
	else {
		if($kcount == 31) {$kx += 130;$kcount = 0;}
		$entity['dx'] = $kx;
		$entity['dy'] = 40+($kcount*15);
		$entity['x'] = $kx+8;
		$entity['y'] = 40+($kcount*15);
		$kcount++;
	}

	$entities[$entity_name] = $entity;
}
// }}}

// grid {{{
function get_gridline_color($pos) {
	global $grey, $mgrey, $lgrey, $wgrey;

		if($pos % 1000 == 0) $col = $grey;
	elseif($pos % 100 == 0) $col = $mgrey;
	elseif($pos % 10 == 0) $col = $lgrey;
	elseif($pos % 1 == 0) $col = $wgrey;
	
	return $col;
}

function draw_grid_lines($image, $mapradius, $drawradius) {
	global $zz, $zx, $zy, $cx, $cy, $imageline;

	$inc = ($zz >= 10) ? 1 : 10;

	for($v=-$mapradius; $v<=$mapradius; $v+=$inc) {
		$col = get_gridline_color($v);
	
		$x = ($v-$zx)*$zz;
		$y = ($v+$zy)*$zz;

		$y1 = bound( (-$mapradius+$zy)*$zz, -$drawradius, $drawradius-1);
		$y2 = bound(  ($mapradius+$zy)*$zz, -$drawradius, $drawradius-1);
		$x1 = bound(-(-$mapradius+$zx)*$zz, -$drawradius, $drawradius-1);
		$x2 = bound( -($mapradius+$zx)*$zz, -$drawradius, $drawradius-1);
		if(in($x, -$drawradius, $drawradius)) $imageline($image, (int)($cx+$x), (int)($cy+$y1), (int)($cx+$x), (int)($cy+$y2), $col);
		if(in($y, -$drawradius, $drawradius)) $imageline($image, (int)($cx+$x1), (int)($cy+$y), (int)($cx+$x2), (int)($cy+$y), $col);
	}
}

function draw_grid_labels($image, $mapradius, $drawradius) {
	global $zz, $zx, $zy, $cx, $cy, $imagestring, $mgrey;
	
	if($zz >= 10) $inc = 10;
	else if($zz <= 0.75) $inc = 100;
	else $inc = 50;

	$x = bound(-$zx*$zz, -$drawradius, $drawradius-25);
	$y = bound( $zy*$zz, -$drawradius, $drawradius-10);

	for($v=-$mapradius; $v<=$mapradius; $v+=$inc) {
		$imagestring($image, 3, (int)($cx+$x+2), (int)($cy-($v-$zy)*$zz+1), $v, $mgrey);
		$imagestring($image, 3, (int)($cx+($v-$zx)*$zz+2), (int)($cy+$y+1), $v, $mgrey);
	}
}

function draw_grid($image, $mapradius, $drawradius) {
	draw_grid_lines($image, $mapradius, $drawradius);
	draw_grid_labels($image, $mapradius, $drawradius);
}

draw_grid($im, 500, 256);

date_default_timezone_set("America/Los_Angeles");
$stamp1 = $words["last update"];
$stamp2 = substr($server_info['updated'], 0, 16);

/*
 * Draw the rectangles
 */
$imagerectangle($im, $cx-256, $cy-256, $cx+255, $cy+255, $black);
$caption_bounds = imagettfbbox(15, 0, "arialuni", $caption);
if($layout == "spread") {
	$imagefilledrectangle($im, 0, 0, 124, 511, $white);
	$imagefilledrectangle($im, 643, 0, 767, 511, $white);
	$imagerectangle($im, 0, 0, 124, 511, $black);
	$imagerectangle($im, 643, 0, 767, 511, $black);
//	$imagestring($im, 3, 704-strlen($caption)*3.45, 10, $caption, $black);
//	$imagestring($im, 3, 64-strlen($caption)*3.45, 10, $caption, $black);
	$imagettftext($im, 15, 0, 706-$caption_bounds[2]/2, 25, $black, "arialuni", $caption);
	$imagettftext($im, 15, 0, 64-$caption_bounds[2]/2, 25, $black, "arialuni", $caption);

	$stamp2_bounds = imagettfbbox(10, 0, "arialuni", $stamp2);
	$imagettftext($im, 10, 0, 706-$stamp2_bounds[2]/2, 508, $grey, "arialuni", $stamp2);
}
else {
	$imagefilledrectangle($im, 515, 0, 767, 511, $white);
	$imagerectangle($im, 515, 0, 767, 511, $black);
//	$imagestring($im, 3, 640-strlen($caption)*3.45, 10, $caption, $black);
	$imagettftext($im, 15, 0, 640-$caption_bounds[2]/2, 25, $black, "arialuni", $caption);

	$stamp_bounds = imagettfbbox(10, 0, "arialuni", "$stamp1 $stamp2");
	$imagettftext($im, 10, 0, 640-$stamp_bounds[2]/2, 508, $grey, "arialuni", "$stamp1 $stamp2");
}
// }}}

// entities {{{
/*
 * Draw each entity, its villages, and its key entry
 */
$cals = Array();
$ca = 0;

function draw_entity_label($image, $entity, $colour) {
	global $server, $white, $imagettftext, $imagecustom;
	
	$imagecustom($image, "<a xlink:href='http://$server/".$entity['link']."'>");
	if(is_a($image, "AimaImage")) {
		aimafilledrectangle($image,
				$entity['x']+7, $entity['y']-7,
				$entity['x']+90, $entity['y']+7, $white);
	}
	dot($image, $entity['dx'], $entity['dy'], $colour);
	$entity_name = $entity["name"];
	$count = $entity['count'];
	$title = ($count ? "$entity_name (".($count+1).")" : $entity_name);
	$imagettftext($image, 10, 0, $entity['x'], $entity['y']+5, $colour, "arialuni", $title);
	$imagecustom($image, "</a>");
}

function draw_village_marker($image, $entity, $village, $colour) {
	global $server, $cx, $cy, $zx, $zy, $zz, $lines, $imageline, $imagecustom, $dotsize;
	
	$vx =  ($village['x']-$zx)*$zz;
	$vy = -($village['y']-$zy)*$zz;
	if($lines) $imageline($image, $entity['dx'], $entity['dy'], (int)($cx+$vx), (int)($cy+$vy), $colour);
	
	$name = $village['name'];
	$owner = $village['owner'];
	$guild = $village['guild'];
	$x = $village['x'];
	$y = $village['y'];
	$pop = $village['population'];
	$cohash = (256-$y)*512 + ($x+257);
	$dfz = "";
	if($zx !=0 || $zy != 0) {
		$dx = $x-$zx;
		$dy = $y-$zy;
		$dist = (int)sqrt($dx*$dx + $dy*$dy);
		$dfz = " ($dist away)";
	}
	$tip = svgentities("$name ($x, $y)$dfz, $pop, ($owner, $guild)");
	$imagecustom($image, "<a xlink:href='http://$server/karte.php?z=$cohash' xlink:title='$tip'>");
	dot($image, $cx+$vx, $cy+$vy, $colour, (log($pop+1)+1)*$dotsize);
	$imagecustom($image, "</a>");
}

function get_entity_colour($entity) {
	global $colby, $cals, $ct, $ca;

	if($colby == "alliance") {
		if(!is_null($cals[$entity["guild"]])) $colour = $cals[$entity["guild"]];
		else $colour = $cals[$entity["guild"]] = $ct[($ca++)%count($ct)];
	}
	else if($colby == "race") {
		if(!is_null($cals[$entity["race_id"]])) $colour = $cals[$entity["race_id"]];
		else $colour = $cals[$entity["race_id"]] = $ct[($ca++)%count($ct)];
	}
	else {
		$colour = $ct[($ca++)%count($ct)];
	}
	return $colour;
}

foreach($entities as $entity_id => $entity) {
	$colour = get_entity_colour($entity);
	foreach($entity['villages'] as $village) {
		draw_village_marker($im, $entity, $village, $colour);
	}

	draw_entity_label($im, $entity, $colour);
}
// }}}

// navigator widget for SVG {{{
if($_GET["format"] == "svg") {
	$base_query = preg_replace("/&amp;zoom=[^&$]+/", "", str_replace("&", "&amp;", $_SERVER["QUERY_STRING"]));
	
	$tzz = $zz == 0 ? 1 : $zz; // stop divide by zeroes
	
	$imagecustom($im, "<a xlink:href='map.php?$base_query&amp;zoom=".($zx-100/$tzz).",$zy,$zz' xlink:title='west'>");
	dot($im, $cx+230-9, $cy+230+0, $white);
	$imagecustom($im, "</a>");
	
	$imagecustom($im, "<a xlink:href='map.php?$base_query&amp;zoom=$zx,".($zy+100/$tzz).",$zz' xlink:title='north'>");
	dot($im, $cx+230+0, $cy+230-9, $white);
	$imagecustom($im, "</a>");
	
	$imagecustom($im, "<a xlink:href='map.php?$base_query&amp;zoom=$zx,".($zy-100/$tzz).",$zz' xlink:title='south'>");
	dot($im, $cx+230+0, $cy+230+9, $white);
	$imagecustom($im, "</a>");
	
	$imagecustom($im, "<a xlink:href='map.php?$base_query&amp;zoom=".($zx+100/$tzz).",$zy,$zz' xlink:title='east'>");
	dot($im, $cx+230+9, $cy+230-0, $white);
	$imagecustom($im, "</a>");

	
	$imagecustom($im, "<a xlink:href='map.php?$base_query&amp;zoom=$zx,$zy,".($zz-2)."' xlink:title='zoom out'>");
//	dot($im, $cx+230+15, $cy+245, $ct[($ca++)%count($ct)]);
	dot($im, $cx+230+15, $cy+245, $white);
	$imageline($im, $cx+230+12, $cy+245, $cx+230+18, $cy+245, $black);
	$imagecustom($im, "</a>");
	
	$imagecustom($im, "<a xlink:href='map.php?$base_query&amp;zoom=$zx,$zy,".($zz+2)."' xlink:title='zoom in'>");
	dot($im, $cx+230-15, $cy+245, $white);
	$imageline($im, $cx+230-18, $cy+245, $cx+230-12, $cy+245, $black);
	$imageline($im, $cx+230-15, $cy+242, $cx+230-15, $cy+248, $black);
	$imagecustom($im, "</a>");
}
// }}}

if(!getBool("debug"))
switch($_GET["format"]) {
	case "PNG": case "png": default:
		header("Content-type: image/png");
		imagepng($im);
		break;
	case "JPEG": case "jpeg":
		header("Content-type: image/jpeg");
		imagejpeg($im);
		break;
	case "SVG": case "svg":
		header("Content-type: image/svg+xml");
		imagesvg($im);
		break;
}
$imagedestroy($im);
