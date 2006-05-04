<?php
require_once "localise.php";
require_once "version.php";

/* sort first by country, then by server */
function wwwcmp($a, $b) {
	$as = explode(".", $a);
	$bs = explode(".", $b);
	if(strncmp($as[2], $bs[2], 2) != 0) return strncmp($as[2], $bs[2], 2);
	else return strcmp($as[0], $bs[0]);
}


/*
 * load the server list -- cached if possible, else
 * look for the database's tables
 */
if(file_exists("cache/servers.txt")) {
	$fp = fopen("cache/servers.txt", "r");
	while($tmp = fgets($fp)) {$serveropts .= $tmp;}
	fclose($fp);
}
else {
	/*
	 * Connect manually -- database.php uses data caching,
	 * which can mess with things in some odd situations...
	 */
	require_once "config.php";
	mysql_pconnect($mysql_host, $mysql_user, $mysql_pass);
	mysql_select_db($mysql_db);

	$options = Array();
	$res = mysql_query("SHOW TABLES");
	while($row = mysql_fetch_row($res)) {
		$server = str_replace("_", ".", $row[0]);
		$row2 = mysql_fetch_row(mysql_query("SELECT count(*) AS count FROM {$row[0]}"));
		$disabled = $row2[0] < 1000 ? " disabled" : "";
		$options[] = "<option value='$server'$disabled>$server</option>\n";
	}

	$serveropts = "";
	usort($options, "wwwcmp");
	foreach($options as $option) {$serveropts .= $option;}

	$fp = fopen("cache/servers.txt", "w");
	fputs($fp, $serveropts);
	fclose($fp);
}


/*
 * for the long link
 */
$baseurl = preg_replace("#[^/]+$#", "", $_SERVER['SCRIPT_URI']);


/*
 * for time until next update
 */
$servertime = date('g:iA');


/*
 * Scan each of the lanugage files
 */
$langs = "";
if(file_exists("cache/langs.txt")) {
	$fp = fopen("cache/langs.txt", "r");
	while($tmp = fgets($fp)) {$langs .= $tmp;}
	fclose($fp);
}
else {
	$n = 0;
	foreach(glob("lang/??.txt") as $flang) {
		$code = preg_replace("#lang/(..).txt#", '$1', $flang);
		$fp = fopen($flang, "r");
		$lang = str_replace("lang=", "", trim(fgets($fp)));
		fclose($fp);
		if($n == 0) $langs = "";
		else if(($n % 5) == 0) $langs .= "<br>\n";
		else $langs .= " | ";
		$langs .= "<a href='?lang=$code'>$lang</a>\n";
		$n++;
	}

	$fp = fopen("cache/servers.txt", "w");
	fputs($fp, $langs);
	fclose($fp);
}
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
				<option value="town"><?=$words['town'];?></option>
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
		<br><?=$words['new page'];?>
		<br><input type="checkbox" name="newpage">
	</div>
	<div id="clickies">
		<br><input type="submit" value="<?=$words['show map'];?>">
		<br><a href="#" onclick="toggle('advanced'); return false;"><?=$words['advanced options'];?></a>
		<br><a href="#" onclick="toggle('output'); return false;"><?=$words['output options'];?></a>
	</div>
</form>
				</td>
				<td style="width: 768px;">
<div id="inst">
	<p><?=$words['help1'];?>
	<p><?=$words['help2'];?>
	<p><?=$words['help3'];?>
	<p><?=$words['help4'];?>
	<p><?=$words['help5'];?>
	
	<p><?=$words['servertime'];?> <?=$servertime;?>

	<hr style="width: 400px">

	<p><?=$words['notice'];?>
	
	<p><?=$words['report bugs'];?>

	<p style="text-align: center;"><a href="mailto:webmaster@shish.is-a-geek.net">webmaster@shish.is-a-geek.net</a>
</div>

<img id="map" src="loading.png" alt="map image" style="display: none;">
<br><?=$words['link to image'];?>: 
<input type="text" id="link" style="width: 512px;">

<hr>

<p><small>
	<?=$langs;?>
	<br>
	<small><?=$words['credit'];?></small>
</small>
				</td>
			</tr>
		</table>
	</body>
</html>
