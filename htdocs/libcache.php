<?php
/*
 * libcache.php (c) Shish 2006
 *
 * a generic caching API
 */


$cachehit = false;
$cachename = "";

function cache_start($cachedir="../cache") {
	global $cachehit, $cachename;

	// force on so we can read it at the end
	ob_start();

	switch($_GET["format"]) {
		case "PNG": case "png": default: $format="png"; break;
		case "JPEG": case "jpeg": $format = "jpeg"; break;
		case "SVG": case "svg": $format = "svg+xml"; break;
	}

	$nocache = isset($_GET['nocache']);
	$hash = md5($_SERVER["QUERY_STRING"]);
	$ab = substr($hash, 0, 2);
	$cd = substr($hash, 2, 2);
	$cachename = "$cachedir/$ab/$cd/$hash.$format";

	if(file_exists($cachename) && !$nocache) {
		$gmdate_mod = gmdate('D, d M Y H:i:s', filemtime($cachename)) . ' GMT';

		if(isset($_SERVER["HTTP_IF_MODIFIED_SINCE"])) {
			$if_modified_since = preg_replace('/;.*$/', '', $_SERVER["HTTP_IF_MODIFIED_SINCE"]);

			if($if_modified_since == $gmdate_mod) {
				header("HTTP/1.0 304 Not Modified");
				header("Content-type: image/$format");
				$cachehit = true;
			}
		}
		else {
			header("Content-type: image/$format");
			header("Last-Modified: $gmdate_mod");
			print file_get_contents($cachename);
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
