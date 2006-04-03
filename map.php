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
?>
