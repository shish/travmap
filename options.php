<?php
/*
 * options.php (c) Shish 2006
 *
 * take the options from HTTP GET, load them into PHP
 */

require_once "util.php";
require_once "localise.php"; # words[key]
# require_once "database.php"; # leave until after we've done $_GET["server"]...

/*
 * GET options
 */
$server   = getString("server", "s2.travian.com");
$alliance = getString("alliance", null);
$player   = getString("player", null);
$order    = getString("order", "default");
$zoom     = getString("zoom", null);
$layout   = getString("layout", "default");
$caption  = getString("caption", $words["key"]);
$groupby  = getString("groupby", "player");
$colby    = getString("colby", $groupby);

# deprecated in 0.4.7; leave during 0.5, remove by version 0.6
if(getBool("algrp")) $groupby = "alliance";
if(getBool("ragrp")) $groupby = "race";

if(getBool("alcol")) $colby = "alliance";
if(getBool("racol")) $colby = "race";

$alcol = getBool("alcol") || ($colby == "alliance");
$racol = getBool("racol") || ($colby == "race");

$lines = getBool("lines");
$casen = getBool("casen");
$azoom = getBool("azoom");
$nocache = getBool("nocache");

$dotsize = getFloat("dotsize", 1);

$maxpop = getInt("maxpop", null);
$minpop = getInt("minpop", null);

$table = str_replace(".", "_", $server);

$datahash = md5("$server $alliance $player $zoom $caption $casen $maxpop $minpop");
$datahash_initial = substr($datahash, 0, 2); 
$datacache = $nocache ? false : "cache/$datahash_initial/$datahash.db";

require_once "database.php";


/*
 * figure out where we are
 */
if($layout == "spread") {
	$cx = 768/2;
	$cy = 256;
}
else {
	$cx = 256;
	$cy = 256;
}


/*
 * Figure out where to focus
 *
 * z(a, x, y, p) = zoom (array, x, y, player)
 */
if($zoom) {
	$za = split(",", $zoom);
	
	if(is_numeric($za[0])) {
		switch(count($za)) {
			case 3: $zx = (int)trim($za[0]); $zy = (int)trim($za[1]); $zz = (float)trim($za[2]); break;
			case 2: $zx = (int)trim($za[0]); $zy = (int)trim($za[1]); $zz = 1; break;
			case 1: $zx = 0; $zy = 0; $zz = (float)trim($za[0]); break;
		}
	}
	else {
		$zp = trim(sql_escape_string($za[0]));
		$cmp = $casen ? "=" : "LIKE";
		$za2 = sql_fetch_row(sql_query("SELECT x,y,town_name FROM $table WHERE town_name $cmp '$zp' LIMIT 1"));
		$zx = $za2['x'];
		$zy = $za2['y'];
		
		$zz = $za[1] ? (float)trim($za[1]) : 1;
	}

	if($zz < 0.1) $zz = 0.1;
}
else {
	$zx = 0; $zy = 0; $zz = 1;
}


?>
