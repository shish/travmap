<?php
declare(strict_types=1);

require_once "lib/localise.php";

$version = "0.14.0";
$build_time = getenv("BUILD_TIME");
$build_hash = substr(getenv("BUILD_HASH") ?: "", 0, 7);
$words = get_words();

// misc {{{
/*
 * for time until next update
 */
$servertime = date('g:iA');

$status = null;
if(file_exists("status.txt")) {
	$status = file_get_contents("status.txt");
	if(strlen($status) > 0) {
		$status = ": " . h($status);
	}
}
// }}}

/* server list {{{
 */
require_once "lib/database.php";
require_once "lib/util.php";

$country_list = [];
$server_list = [];
$server_data = [];

$servers = [];
$res = $db->query("SELECT name,villages FROM servers ORDER BY name");
foreach($res->fetchAll() as $row) {
	$servers[$row["name"]] = $row["villages"];
}
uksort($servers, "wwwcmp");

$lastcountry = "";
foreach($servers as $name => $villages) {
	$country = getSubdomain($name);
	$disabled = $villages < 1000 ? " disabled" : "";

	if($country != $lastcountry) {
		$country_list[] = "<option>" . h($country) . "</option>";
		if($server_list) $server_list[] = "</optgroup>";
		$server_list[] = "<optgroup label='" . ha($country) . "'>";
		$lastcountry = $country;
	}
	$server_list[] = "<option value='" . ha($name) . "'$disabled>" . h($name) . "</option>";

	// Build server data structure for JavaScript
	if (!isset($server_data[$country])) {
		$server_data[$country] = [];
	}
	$server_data[$country][] = [
		'name' => $name,
		'enabled' => $villages >= 1000
	];
}
if($server_list) $server_list[] = "</optgroup>";

$countryopts = implode("\n", $country_list);
$serveropts = implode("\n", $server_list);
// }}}

/* language files {{{
 *
 * Scan each of the lanugage files
 */
$n = 0;
$langs = "";
foreach(glob("./lang/*.txt") as $flang) {
	$code = preg_replace("#./lang/(.*).txt#", '$1', $flang);
	$fp = fopen($flang, "r");
	if ($fp === false) continue;

	$lang = str_replace("lang=", "", trim(fgets($fp) ?: ""));
	fclose($fp);
	if($lang == "") continue;
	if($n == 0) $langs = "";
	else if(($n % 5) == 0) $langs .= "<br>\n";
	else $langs .= " | ";
	$langs .= "<a href='?lang=" . ha($code) . "'>" . h($lang) . "</a>\n";
	$n++;
}

// }}}
?>
<!DOCTYPE html>
<html lang="<?= ha(get_lang()); ?>">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>TravMap <?= h($version); ?></title>
		<link rel="stylesheet" href="style.css" type="text/css">
		<meta name="description" content="A mapping tool for the online game Travian">
		<meta name="keywords" content="travian,game,map,rts">
		<script>
			serverData = <?= json_encode($server_data, JSON_HEX_TAG | JSON_HEX_AMP); ?>;
		</script>
		<script src="script.js"></script>
	</head>
	<body>
		<table border="1" class="page">
			<tr>
<!-- nav {{{ -->
				<td class="sidebar">
<h1>TravMap <span title="<?= ha($build_time); ?>-<?= ha($build_hash); ?>"><?= h($version); ?></span></h1>

<small><a href="#" id="help-link" class="block-link"><?= h($words['instructions']); ?></a></small>

<hr>
<form action="" class="map-form">
	<input type="hidden" name="lang" value="<?= ha(get_lang()); ?>">
	<div id="basic">
		<br><?= h($words['server']); ?>
		<br><select id="country_select" name="country"><?= $countryopts; ?></select>
		<br><select id="server_select" name="server"><?= $serveropts; ?></select>
		<br><?= h($words['alliance']); ?>
		<br><input type="text" name="alliance">
		<br><?= h($words['player']); ?>
		<br><input type="text" name="player">
		<br><?= h($words['town']); ?>
		<br><input type="text" name="town">
		<br><?= h($words['lines']); ?>
		<br><input type="checkbox" name="lines">
		<br><?= h($words['group by']); ?>
		<br>
			<select name="groupby">
				<option value="player"><?= h($words['player']); ?></option>
				<option value="alliance"><?= h($words['alliance']); ?></option>
				<option value="group"><?= h($words['alliance']); ?> 2</option>
				<option value="race"><?= h($words['race']); ?></option>
				<option value="town"><?= h($words['town']); ?></option>
			</select>
		<br><?= h($words['colour by']); ?>
		<br>
			<select name="colby">
				<option value=""><?= h($words['default']); ?></option>
				<option value="player"><?= h($words['player']); ?></option>
				<option value="alliance"><?= h($words['alliance']); ?></option>
				<option value="race"><?= h($words['race']); ?></option>
			</select>
	</div>
	<div id="advanced">
		<br><?= h($words['zoom']); ?>
		<br><input type="text" name="zoom">
		<br><?= h($words['min distance']); ?>
		<br><input type="text" name="mindist">
		<br><?= h($words['max distance']); ?>
		<br><input type="text" name="maxdist">
		<br><?= h($words['min population']); ?>
		<br><input type="text" name="minpop">
		<br><?= h($words['max population']); ?>
		<br><input type="text" name="maxpop">
		<br><?= h($words['case sensitive']); ?>
		<br><input type="checkbox" name="casen" checked>
	</div>
	<div id="output">
		<br><?= h($words['caption']); ?>
		<br><input type="text" name="caption">
		<br><?= h($words['dot size']); ?>
		<br><input type="text" name="dotsize">
		<br><?= h($words['shrinkwrap']); ?>
		<br><input type="checkbox" name="azoom">
		<br><?= h($words['order by']); ?>
		<br>
			<select name="order">
				<option value=""><?= h($words['default']); ?></option>
				<option value="dist"><?= h($words['distance from zoom target']); ?></option>
				<option value="population"><?= h($words['population']); ?></option>
				<option value="race"><?= h($words['race']); ?></option>
				<option value="x">x</option>
				<option value="y">-y</option>
			</select>
		<br><?= h($words['layout']); ?>
		<br>
			<select name="layout">
				<option value=""><?= h($words['default']); ?></option>
				<option value="spread"><?= h($words['spread']); ?></option>
			</select>
		<br><?= h($words['output format']); ?>
		<br>
			<select id="format_select" name="format">
				<option value="png">PNG</option>
				<option value="svg">SVG</option>
			</select>
		<br><?= h($words['new page']); ?>
		<br><input id="newpage_check" type="checkbox" name="newpage">
	</div>
	<div id="clickies">
		<br><input type="submit" value="<?= ha($words['show map']); ?>">
		<br><a href="#" id="advanced-toggle"><?= h($words['advanced options']); ?></a>
		<br><a href="#" id="output-toggle"><?= h($words['output options']); ?></a>
	</div>
</form>
				</td>
<!-- }}} -->
<!-- instructions {{{ -->
				<td class="main-content">
<div id="inst">
	<p><?= $words['help1']; ?></p>
	<p><?= $words['help2']; ?></p>
	<p><?= $words['help3']; ?></p>
	<p><?= $words['help4']; ?></p>
	<p><?= $words['help6']; ?></p>
	<p><?= $words['help7']; ?></p>

	<p><?= h($words['servertime']); ?> <?= h($servertime); ?></p>

	<p><a href="status.php"><?= h($words['server status']); ?></a><?= $status; ?></p>

	<hr class="notice-separator">

	<?php if(isset($words['notice']) && $words['notice']): ?>
	<p><?= $words['notice']; ?></p>
	<?php endif; ?>

	<p><?= $words['report bugs']; ?></p>

	<p class="contact-info"><a href="mailto:webmaster@shishnet.org">webmaster@shishnet.org</a></p>
</div>

<div id="about">
<p>TravMap is a tool for displaying maps of players in the free online
game Travian, a massively multiplayer real time strategy which allows
players to build villages, trade goods, form alliances and wage war~</p>
</div>

<div id="map"></div>
<br><?= h($words['link to image']); ?>:
<input type="text" id="link">

<hr>

<form action="add.php" method="GET" class="add-server-form">
<table class="add-server-table">
	<tr><th colspan="2">Add Server</th></tr>
	<tr><td class="label-col">Server</td><td><input type="text" name="server" placeholder="e.g. ts1.x3.europe.travian.com" pattern="[a-z0-9]+\.x[0-9]\.[a-z]+\.travian\.com" required></td></tr>
	<tr>
		<td></td>
		<td class="server-example">
			e.g.
			<span class="example-good">ts1.x3.europe.travian.com</span>
			<br>not
			<span class="example-bad">s5</span> /
			<span class="example-bad">server1</span> /
			<span class="example-bad">ptx</span> /
			<span class="example-bad">s4.nl</span>
		</td>
	</tr>
	<tr><td colspan="2"><input type="submit" value="Add Server"></td></tr>
</table>
</form>

<hr>

<p class="language-links">
	<?= $langs; ?>
	<br>
	<?php if(isset($words['credit'])): ?>
	<small><?= $words['credit']; ?></small>
	<?php endif; ?>
</p>
				</td>
<!-- }}} -->
			</tr>
		</table>
	</body>
</html>
