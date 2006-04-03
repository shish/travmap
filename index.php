<?php
require_once "localise.php";
require_once "version.php";

$serveropts = "";
$dbs = glob("db/*.db");
usort($dbs, "wwwcmp");
foreach($dbs as $filename) {
	$db = preg_replace("/^db\/(.+)\.db$/", '\1', $filename);
	if($db == $default) $serveropts .= "<option value='$db' selected>$db</option>\n";
	else $serveropts .= "<option value='$db'>$db</option>\n";
}

/* sort first by country, then by server */
function wwwcmp($a, $b) {
	$as = explode(".", $a);
	$bs = explode(".", $b);
	if(strcmp($as[2], $bs[2]) != 0) return strcmp($as[2], $bs[2]);
	else return strcmp($as[0], $bs[0]);
}

$baseurl = $_SERVER['SCRIPT_URI'];
$baseurl = preg_replace("#[^/]+$#", "", $baseurl);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html>
	<head>
		<title>TravMap <?=$version;?></title>
		<link rel="stylesheet" href="style.css" type="text/css">
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
		<script>baseurl = "<?=$baseurl;?>";</script>
		<script type="text/javascript" src="script.js"></script>
	</head>
	<body>
		<table border="1" align="center">
			<tr>
				<td>
<h1>TravMap <?=$version;?></h1>

<small><a href="#" onclick="help(); return false;" style="display: block; margin: 8px;">Instructions</a></small>
<small><a href="old_index.php" style="display: block; margin: 8px;">Old Index</a></small>

<hr>
<!--
<table style="margin: 16px;" border="1">
	<tr>
		<td colspan="2"><a href="#" onclick="north(); return false;">North</a></td>
		<td><a href="#" onclick="zin(); return false;">In</a></td>
	</tr>
	<tr>
		<td><a href="#" onclick="west(); return false;">West</a></td>
		<td><a href="#" onclick="east(); return false;">East</a></td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan="2"><a href="#" onclick="south(); return false;">South</a></td>
		<td><a href="#" onclick="zout(); return false;">Out</a></td>
	</tr>
</table>

<hr>
-->
<form onSubmit="updateMap(); return false;" action="">
	<input type="hidden" name="lang" value="<?=$lang;?>">
	<div id="basic">
		<br><?=$words['server'];?>
		<br><select name="server"><?=$serveropts;?></select>
		<br><?=$words['alliance'];?>
		<br><input type="text" name="alliance" value="<?=$alliance;?>">
		<br><?=$words['player'];?>
		<br><input type="text" name="player">
		<br><?=$words['lines'];?>
		<br><input type="checkbox" name="lines">
		<br><?=$words['group by'];?>
		<br>
			<select name="groupby">
				<option value="player"><?=$words['player'];?></option>
				<option value="alliance"><?=$words['alliance'];?></option>
				<option value="race"><?=$words['race'];?></option>
			</select>
		<br><?=$words['colour by'];?>
		<br>
			<select name="colby">
				<option value=""><?=$words['default'];?></option>
				<option value="player"><?=$words['player'];?></option>
				<option value="alliance"><?=$words['alliance'];?></option>
				<option value="race"><?=$words['race'];?></option>
			</select>
	</div>
	<div id="advanced" style="display: none;">
		<br><?=$words['zoom'];?>
		<br><input type="text" name="zoom">
		<br><?=$words['min population'];?>
		<br><input type="text" name="minpop">
		<br><?=$words['max population'];?>
		<br><input type="text" name="maxpop">
		<br><?=$words['case sensitive'];?>
		<br><input type="checkbox" name="casen" checked>
	</div>
	<div id="output" style="display: none;">
		<br><?=$words['caption'];?>
		<br><input type="text" name="caption">
		<br><?=$words['dot size'];?>
		<br><input type="text" name="dotsize">
		<br><?=$words['shrinkwrap'];?>
		<br><input type="checkbox" name="azoom">
		<br><?=$words['order by'];?>
		<br>
			<select name="order">
				<option value=""><?=$words['default'];?></option>
				<option value="dist"><?=$words['distance from zoom target'];?></option>
				<option value="population"><?=$words['population'];?></option>
				<option value="race"><?=$words['race'];?></option>
				<option value="x">x</option>
				<option value="y">y</option>
			</select>
		<br><?=$words['layout'];?>
		<br>
			<select name="layout">
				<option value=""><?=$words['default'];?></option>
				<option value="spread"><?=$words['spread'];?></option>
			</select>
		<br><?=$words['output format'];?>
		<br>
			<select name="format">
				<option value="png">PNG</option>
				<!-- <option value="jpeg">JPEG</option> -->
				<option value="svg">SVG</option>
			</select>
	</div>
	<div id="clickies">
		<br><input type="submit" value="<?=$words['show map'];?>">
		<br><a href="#" onclick="toggle('advanced'); return false;"><?=$words['advanced options'];?></a>
		<br><a href="#" onclick="toggle('output'); return false;"><?=$words['output options'];?></a>
	</div>
</form>
	<p>&nbsp;<p>&nbsp;<p><small><small><?=$words['report bugs'];?></small></small>
				</td>
				<td style="width: 768px;">
<div id="inst" style="height: 512px; width: 512px; margin: auto;">
	<p><?=$words['help1'];?>
	<p><?=$words['help2'];?>
	<p><?=$words['help3'];?>
	<p><?=$words['help4'];?>
	
	<p><?=$words['notice'];?>
</div>

<img id="map" src="loading.php" alt="map image" style="display: none;">
<br><?=$words['link to image'];?>: 
<input type="text" id="link" style="width: 512px;">

<hr>

<p><small>
	<a href="?lang=en">English</a> |
	<a href="?lang=fr">Français</a> |
	<a href="?lang=de">Deutsh</a> |
	<a href="?lang=es">Español</a> |
	<a href="?lang=nl">Nederlands</a> |
	<a href="?lang=pl">Polski</a> |
	<a href="?lang=it">Italiano</a>
	<br><small><?=$words['credit'];?></small>
</small>
				</td>
			</tr>
		</table>
	</body>
</html>
