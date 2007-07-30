<?php
/*
 * options.php (c) Shish 2006
 *
 * take the options from HTTP GET, load them into PHP
 */

require_once "util.php";
require_once "localise.php"; # words[key]
require_once "database.php";

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
$server_info = sql_fetch_row(sql_query("SELECT * FROM servers WHERE name='".sql_escape_string($server)."'"));
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
	global $table;

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

$size = max($server_info['height'], $server_info['width']);
$zx = 0; $zy = 0; $zz = 500.0 / $size;

if($zoom) {
	$za = array_map("trim", split(",", $zoom));
	
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

?>
