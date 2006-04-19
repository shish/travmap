<?php
/*
 * libcache.php (c) Shish 2006
 *
 * a generic caching API
 */


$cachehit = false;
$cachename = "";

function cache_start($cachedir="./cache") {
	global $cachehit, $cachename;

	// force on so we can read it at the end
	ob_start();

	switch($_GET["format"]) {
		case "PNG": case "png": default: $format="png"; break;
		case "JPEG": case "jpeg": $format = "jpeg"; break;
		case "SVG": case "svg": $format = "svg+xml"; break;
	}

	$nocache = $_GET['nocache'];
	$hash = md5($_SERVER["QUERY_STRING"]);
	$initial = substr($hash, 0, 1);
	$cachename = "$cachedir/$initial/$hash.$format";

	if(file_exists($cachename) && !$nocache) {
		$if_modified_since = preg_replace('/;.*$/', '', $_SERVER["HTTP_IF_MODIFIED_SINCE"]);
		$gmdate_mod = @gmdate('D, d M Y H:i:s', @filemtime($cachename)) . ' GMT';

		if($if_modified_since == $gmdate_mod) {
			header("HTTP/1.0 304 Not Modified");
			header("Content-type: image/$format");
			$cachehit = true;
		}
		else if($file = @file_get_contents($cachename)) {
			header("Content-type: image/$format");
			header("Last-Modified: $gmdate_mod");
			print $file;
			$cachehit = true;
		}
	}
}

function cache_save() {
	global $cachename;
	$fp = @fopen($cachename, 'w');
	@fwrite($fp, ob_get_contents());
	@fclose($fp); 
}

function cache_is_hit() {
	global $cachehit;
	return $cachehit;
}
?>
