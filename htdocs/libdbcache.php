<?php
/*
 * libcache.php (c) Shish 2006
 *
 * a generic caching API
 */

require_once "database.php";

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

	date_default_timezone_set("America/Los_Angeles");
	$nocache = isset($_GET['nocache']);
	$hash = md5($_SERVER["QUERY_STRING"]); #  . date("y/m/d"));
	$cachename = $hash;

	$result = sql_query("SELECT * FROM cache WHERE hash='$hash'");
	$cache_data = sql_fetch_row($result);

	if($cache_data && !$nocache) {
		sql_query("UPDATE cache SET hits=hits+1 WHERE hash='$hash'");
		$gmdate_mod = gmdate('D, d M Y H:i:s', $cache_data['timestamp_unix']) . ' GMT';

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
			print pg_unescape_bytea($cache_data['data']);
			$cachehit = true;
		}
	}
}

function cache_save() {
	global $cachename;
	$data = ob_get_contents();
	$escaped = pg_escape_bytea($data);
	$ts = time();

	$result = sql_query("SELECT * FROM cache WHERE hash='$cachename'");
	$cache_data = sql_fetch_row($result);

	if($cache_data) {
		sql_query("UPDATE cache SET hits=hits+1 WHERE hash='$cachename'");
	}
	else {
		sql_query("
			INSERT INTO cache(hash, timestamp_unix, timestamp_db, data)
			VALUES('$cachename', $ts, now(), E'$escaped')
		") or die(sql_error());
	}
}

function cache_is_hit() {
	global $cachehit;
	return $cachehit;
}
?>
