<?php
/*
 * map.php (c) Shish 2006
 *
 * call each of the parts of the map generation process
 */

include "libtimer.php";
include "libcache.php";

timer_start();

cache_start();
if(!cache_is_hit()) {
	include "render.php";
	include "output.php";
	cache_save();
}

timer_save();

$fp = fopen("cachelog.txt", 'a');
fwrite($fp, sprintf("icache:%s dcache:%s // %16s // %s\n", 
	cache_is_hit() ? "y" : "n", 
	$using_data_cache ? "y" : "n",
	$_SERVER["REMOTE_ADDR"],
	$_SERVER["QUERY_STRING"]));
fclose($fp); 
?>
