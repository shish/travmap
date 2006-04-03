<?php
/*
 * index.php (c) Shish 2006
 *
 * a nice interface to select options to be passed to map.php
 */

include "localise.php";
include "version.php";

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
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html>
	<head>
		<title>TravMap <?=$version;?></title>
		<link rel="stylesheet" href="style.css" type="text/css">
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
		<script type="text/javascript" src="script.js"></script>
	</head>

	<body>
		<h1>TravMap <?=$version;?></h1>
		<p>
		<center>
			<div id="langopts">
				<small>
					<a href="?lang=en">English</a> |
					<a href="?lang=fr">Français</a> |
					<a href="?lang=de">Deutsh</a> |
					<a href="?lang=es">Español</a> |
					<a href="?lang=nl">Nederlands</a> |
					<a href="?lang=pl">Polski</a> |
					<a href="?lang=it">Italiano</a>
					<br><small><?=$words['credit'];?></small>
				</small>
				<br>&nbsp;
			</div>
			<form action="map.php" method="GET">
				<input type="hidden" name="lang" value="<?=$lang;?>">
				<div id="basic">
					<table>
						<tr>
							<td><?=$words['server'];?></td>
							<td>
								<select name="server">
									<?=$serveropts;?>
								</select>
							</td>
						</tr>
						<tr>
							<td><?=$words['alliance'];?></td>
							<td><input type="text" name="alliance"></td>
						</tr>
						<tr>
							<td><?=$words['player'];?></td>
							<td><input type="text" name="player"></td>
						</tr>
						<tr>
							<td><?=$words['lines'];?></td>
							<td><input type="checkbox" name="lines"></td>
						</tr>
						<tr>
							<td><?=$words['group by'];?></td>
							<td>
								<select name="groupby">
									<option value="player"><?=$words['player'];?></option>
									<option value="alliance"><?=$words['alliance'];?></option>
									<option value="race"><?=$words['race'];?></option>
								</select>
							</td>
						</tr>
						<tr>
							<td><?=$words['colour by'];?></td>
							<td>
								<select name="colby">
									<option value=""><?=$words['default'];?></option>
									<option value="player"><?=$words['player'];?></option>
									<option value="alliance"><?=$words['alliance'];?></option>
									<option value="race"><?=$words['race'];?></option>
								</select>
							</td>
						</tr>
					</table>
				</div>
				<div id="advanced" style="display: none;">
					<table>
						<tr>
							<td><?=$words['zoom'];?></td>
							<td><input type="text" name="zoom"></td>
						</tr>
						<tr>
							<td><?=$words['min population'];?></td>
							<td><input type="text" name="minpop"></td>
						</tr>
						<tr>
							<td><?=$words['max population'];?></td>
							<td><input type="text" name="maxpop"></td>
						</tr>
						<tr>
							<td><?=$words['case sensitive'];?></td>
							<td><input type="checkbox" name="casen" checked></td>
						</tr>
					</table>
				</div>
				<div id="output" style="display: none;">
					<table>
						<tr>
							<td><?=$words['caption'];?></td>
							<td><input type="text" name="caption"></td>
						</tr>
						<tr>
							<td><?=$words['dot size'];?></td>
							<td><input type="text" name="dotsize"></td>
						</tr>
						<tr>
							<td><?=$words['shrinkwrap'];?></td>
							<td><input type="checkbox" name="azoom"></td>
						</tr>
						<tr>
							<td><?=$words['order by'];?></td>
							<td>
								<select name="order">
									<option value=""><?=$words['default'];?></option>
									<option value="dist"><?=$words['distance from zoom target'];?></option>
									<option value="population"><?=$words['population'];?></option>
									<option value="race"><?=$words['race'];?></option>
									<option value="x">x</option>
									<option value="y">y</option>
								</select>
							</td>
						</tr>
						<tr>
							<td><?=$words['layout'];?></td>
							<td>
								<select name="layout">
									<option value=""><?=$words['default'];?></option>
									<option value="spread"><?=$words['spread'];?></option>
								</select>
							</td>
						</tr>
						<tr>
							<td><?=$words['output format'];?></td>
							<td>
								<select name="format">
									<option value="png">PNG</option>
									<!-- <option value="jpeg">JPEG</option> -->
									<option value="svg">SVG</option>
								</select>
							</td>
						</tr>
					</table>
				</div>
				<div id="clickies">
					<table>
						<tr>
							<td colspan="2"><input type="submit" value="<?=$words['show map'];?>"></td>
						</tr>
						<tr>
							<td><a href="#" onclick="toggle('advanced'); return false;"><?=$words['advanced options'];?></a></td>
							<td><a href="#" onclick="toggle('output'); return false;"><?=$words['output options'];?></a></td>
						</tr>
						<tr>
							<td><a href="#" onclick="toggle('langopts'); return false;"><?=$words['language options'];?></a></td>
							<td><a href="#" onclick="toggle('instructions'); return false;"><?=$words['instructions'];?></a></td>
							<!-- <td><a href="help.php?lang=<?=$lang;?>"><?=$words['instructions'];?></a></td> -->
						</tr>
					</table>
				</div>
			</form>

			<div id="instructions">
				<p><ul class="left">
					<li><?=$words['help1'];?>
					<li><?=$words['help2'];?>
					<li><?=$words['help3'];?>
					<li><?=$words['help4'];?>
				</ul>
			</div>
		</center>
	</body>
</html>
