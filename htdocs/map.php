<?php
/*
 * map.php (c) Shish 2006
 *
 * call each of the parts of the map generation process
 */

require_once "libtimer.php";
#require_once "libdbcache.php";
//require_once "libcache.php";
require_once "liblogerr.php";

#cache_start();
#if(!cache_is_hit()) {
//	timer_start();
	require_once "render.php";
	require_once "output.php";
#	cache_save();
//	timer_save();
#}
?>
