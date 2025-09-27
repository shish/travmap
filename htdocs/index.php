<?php
require_once "lib/localise.php";
require_once "lib/version.php";

$words = get_words();
$version = get_version();
$build_time = getenv("BUILD_TIME");
$build_hash = substr(getenv("BUILD_HASH") ?: "", 0, 7);

// misc {{{
/* sort first by country, then by server */
function wwwcmp($a, $b) {
	$as = explode(".", $a);
	$bs = explode(".", $b);
	$ae = count($as)-1;
	$be = count($bs)-1;
	if(strncmp($as[$ae], $bs[$be], 2) != 0) {
		// first sort by country
		return strncmp($as[$ae], $bs[$be], 2);
	}
	else if(preg_match('/[0-9]/', $as[0]) && preg_match('/[0-9]/', $bs[0])) {
		// then by server number
		$ai = Array();
		$bi = Array();
		preg_match('/([0-9]+)/', $as[0], $ai);
		preg_match('/([0-9]+)/', $bs[0], $bi);
		return ($ai[0] == $bi[0]) ? 0 : (($ai[0] > $bi[0]) ? 1 : -1);
	}
	else {
		// then by string(part #0) if server number fails
		return strcmp($as[0], $bs[0]);
	}
}

/*
 * for the long link
 */
// $baseurl = preg_replace("#[^/]+$#", "", $_SERVER['SCRIPT_URI']);
$baseurl = "https://travmap.shishnet.org/";


/*
 * for time until next update
 */
$servertime = date('g:iA');

$status = null;
if(file_exists("status.txt")) {
	$status = file_get_contents("status.txt");
	if(strlen($status) > 0) {
		$status = ": $status";
	}
}
// }}}

/* server list {{{
 *
 * load the server list -- cached if possible, else
 * look for the database's tables
 */
if(file_exists("../cache/countries.txt") && file_exists("../cache/servers.txt")) {
	$countryopts = file_get_contents("../cache/countries.txt");
	$serveropts = file_get_contents("../cache/servers.txt");
}
else {
	require_once "lib/database.php";

	$country_list = Array();
//	$country_list[] = "<option>All</option>";

	$server_list = Array();

	$lastcountry = "";
	$res = $db->query("SELECT name,country,villages FROM servers WHERE visible=True ORDER BY country, name");
	foreach($res->fetchAll() as $row) {
		$name = $row['name'];
		$country = $row['country'];
		$disabled = $row['villages'] < 1000 ? " disabled" : "";
		
		if($country != $lastcountry) {
			$country_list[] = "<option>$country</option>";
			if($server_list) $server_list[] = "</optgroup>";
			$server_list[] = "<optgroup label='$country'>";
			#$server_list[] = "<option style='background: black; color: white;' disabled>$country</option>";
			$lastcountry = $country;
		}
		$server_list[] = "<option value='$name'$disabled>$name</option>";
	}
	if($server_list) $server_list[] = "</optgroup>";

	$countryopts = implode("\n", $country_list);
	$serveropts = implode("\n", $server_list);

	file_put_contents("../cache/countries.txt", $countryopts);
	file_put_contents("../cache/servers.txt", $serveropts);
}
// }}}

/* language files {{{
 *
 * Scan each of the lanugage files
 */
$langs = "";
if(file_exists("../cache/langs.txt")) {
	$fp = fopen("../cache/langs.txt", "r");
	while($tmp = fgets($fp)) {$langs .= $tmp;}
	fclose($fp);
}
else {
	$n = 0;
	$flags = "";
	foreach(glob("./lang/*.txt") as $flang) {
		$code = preg_replace("#./lang/(.*).txt#", '$1', $flang);
		$fp = fopen($flang, "r");
		$lang = str_replace("lang=", "", trim(fgets($fp)));
		fclose($fp);
		if($lang == "") continue;
		if($n == 0) $langs = "";
		else if(($n % 5) == 0) $langs .= "<br>\n";
		else $langs .= " | ";
		$langs .= "<a href='?lang=$code'>$lang</a>\n";
		$flags .= "<a href='?lang=$code'><img src='https://static.shishnet.org/flags/$code.png' alt='$lang'></a>\n";
		$n++;
	}

	$fp = fopen("../cache/langs.txt", "w");
	fputs($fp, $langs);
	fclose($fp);

	$fp = fopen("../cache/flags.txt", "w");
	fputs($fp, $flags);
	fclose($fp);
}
// }}}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html>
	<head>
		<title>TravMap <?=$version;?></title>
		<link rel="stylesheet" href="style.css" type="text/css">
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
		<script>baseurl = "<?=$baseurl;?>";</script>
		<script type="text/javascript" src="script.js"></script>
		<meta name="description" content="A mapping tool for the online game Travian">
		<meta name="keywords" content="travian,game,map,rts">
	</head>
	<body>
		<table border="1" align="center" class="page">
			<tr>
<!-- nav {{{ -->
				<td style="width: 175px;">
<h1>TravMap <span title="<?=$build_time;?>-<?=$build_hash;?>"><?=$version;?></span></h1>

<small><a href="#" onclick="help(); return false;" style="display: block; margin: 8px;"><?=$words['instructions'];?></a></small>
<!--
<hr>
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
-->
<hr>
<form onSubmit="updateMap(); return false;" action="">
	<input type="hidden" name="lang" value="<?=get_lang();?>">
	<div id="basic">
		<br><?=$words['server'];?>
		<br><select onChange="updateServers(this);" id="country_select" name="country"><?=$countryopts;?></select>
		<br><select onChange="saveServer(this);" id="server_select" name="server"><?=$serveropts;?></select>
		<br><?=$words['alliance'];?>
		<br><input type="text" name="alliance">
		<br><?=$words['player'];?>
		<br><input type="text" name="player">
		<br><?=$words['town'];?>
		<br><input type="text" name="town">
		<br><?=$words['lines'];?>
		<br><input type="checkbox" name="lines">
		<br><?=$words['group by'];?>
		<br>
			<select name="groupby">
				<option value="player"><?=$words['player'];?></option>
				<option value="alliance"><?=$words['alliance'];?></option>
				<option value="group"><?=$words['alliance'];?> 2</option>
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
		<br><?=$words['min distance'];?>
		<br><input type="text" name="mindist">
		<br><?=$words['max distance'];?>
		<br><input type="text" name="maxdist">
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
				<option value="y">-y</option>
			</select>
		<br><?=$words['layout'];?>
		<br>
			<select name="layout">
				<option value=""><?=$words['default'];?></option>
				<option value="spread"><?=$words['spread'];?></option>
			</select>
		<br><?=$words['output format'];?>
		<br>
			<select id="format_select" name="format">
				<option value="png">PNG</option>
				<!-- <option value="jpeg">JPEG</option> -->
				<option value="svg">SVG</option>
			</select>
		<br><?=$words['new page'];?>
		<br><input id="newpage_check" type="checkbox" name="newpage">
	</div>
	<div id="clickies">
		<br><input type="submit" value="<?=$words['show map'];?>">
		<br><a href="#" onclick="toggle('advanced'); return false;"><?=$words['advanced options'];?></a>
		<br><a href="#" onclick="toggle('output'); return false;"><?=$words['output options'];?></a>
	</div>
</form>
				</td>
<!-- }}} -->
<!-- instructions {{{ -->
				<td style="width: 768px;">
<div id="inst">
	<p><?=$words['help1'];?>
	<p><?=$words['help2'];?>
	<p><?=$words['help3'];?>
	<p><?=$words['help4'];?>
	<!-- <p><?=@$words['help5'];?> -->
	<p><?=$words['help6'];?>
	<p><?=$words['help7'];?>

	<p><?=$words['servertime'];?> <?=$servertime;?>

	<p><a href="status.php"><?=$words['server status'];?></a><?=$status;?>

	<hr style="width: 400px">

	<p><?=@$words['notice'];?>
	
	<p><?=$words['report bugs'];?>

	<p style="text-align: center;"><a href="mailto:webmaster@shishnet.org">webmaster@shishnet.org</a><!-- // <a href="#" onclick="about(); return false;">About</a> -->
</div>

<div id="about" style="display: none;">
<!-- google_ad_section_start -->
<p>TravMap is a tool for displaying maps of players in the free online
game Travian, a massively multiplayer real time strategy which allows
players to build villages, trade goods, form alliances and wage war~
<!-- google_ad_section_end -->
</div>

<div id="map" style="display: none;"></div>
<br><?=$words['link to image'];?>: 
<input type="text" id="link" style="width: 512px;">

<hr>

<form action="add.php" method="GET">
<table style="margin: auto; width: 30em;">
	<tr><td colspan="2">Add Server</td></tr>
	<tr><td style="width: 7em;">Server</td><td><input type="text" name="server" placeholder="e.g. ts1.x3.europe.travian.com" pattern="[a-z0-9]+\.x[0-9]\.[a-z]+\.travian\.com" required></td></tr>
	<tr>
		<td></td>
		<td>
			e.g.
			<span style="color:green">ts1.x3.europe.travian.com</span>
			<br/>not
			<span style="color:red">s5</span> /
			<span style="color:red">server1</span> /
			<span style="color:red">ptx</span> /
			<span style="color:red">s4.nl</span>
		</td>
	</tr>
	<tr><td>Country</td><td><input type="text" name="country" placeholder="e.g. America" pattern="[A-Z][a-zA-Z ]+" required></td></tr>
	<tr><td colspan="2"><input type="submit" value="Add Server"></td></tr>
</table>
</form>

<hr>

<p><small>
	<?=$langs;?>
	<br>
	<small><?=$words['credit'];?></small>
</small>
				</td>
<!-- }}} -->
			</tr>
		</table>
	</body>
</html>
