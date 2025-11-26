<?php
require_once "lib/database.php";
require_once "lib/util.php";

// totals {{{
$res = $db->query("
	SELECT
		SUM(villages) AS villages,
		SUM(guilds) AS guilds,
		SUM(owners) AS owners,
		SUM(population) AS population
	FROM servers WHERE visible=True");
$row = $res->fetch();
$total_villages = $row['villages'];
$total_guilds = $row['guilds'];
$total_owners = $row['owners'];
$total_population = $row['population'];
$totals = "
			<tr><th colspan='7'>Totals</th></tr>
			<tr>
				<td>Server</td>
				<td>Updated</td>
				<td>Status</td>
				<td>Alliances</td>
				<td>Players</td>
				<td>Villages</td>
				<td>Population</td>
			</tr>
			<tr>
				<td>-</td>
				<td>-</td>
				<td>-</td>
				<td>$total_guilds</td>
				<td>$total_owners</td>
				<td>$total_villages</td>
				<td>$total_population</td>
			</tr>
";
// }}}

// rows {{{
$m = date("m"); // Month value
$d = date("d"); //today's date
$y = date("Y"); // Year value
$today = date('Y-m-d');
$yesterday = date('Y-m-d', mktime(0,0,0,$m,($d-1),$y));

$rows = array();
$links = array();
$last_country = "";
$res = $db->query("
	SELECT name,villages,updated,status,owners,guilds,population
	FROM servers
	WHERE visible=True
	ORDER BY name
");
foreach($res->fetchAll() as $row) {
	$name = $row['name'];
	$country = getSubdomain($name);
	$villages = $row['villages'];
	$updated = $row['updated'];
	$status = $row['status'];

	if($status == "ok" || $status == "map.sql downloaded" || $status == "karte.sql downloaded") {
		$status = "<font color='green'>$status</font>";
	}
	else {
		$status = "<font color='red'>$status</font>";
	}

	$updated = substr($updated, 0, 16);
	if(substr($updated, 0, 10) == $today) {
		$updated = "<font color='green'>$updated</font>";
	}
	elseif(substr($updated, 0, 10) == $yesterday) {
		$updated = "<font color='orange'>$updated</font>";
	}
	else {
		$updated = "<font color='red'>$updated</font>";
	}

	$players = $row['owners'];
	$guilds = $row['guilds'];
	$population = $row['population'];
	
	if($country != $last_country) {
		$links[] = "<a href='#$country'>$country</a>";
		$rows[] = "
			<tr><th colspan='7'><a name='$country'>$country</a></th></tr>
			<tr>
				<td>Server</td>
				<td>Updated</td>
				<td>Status</td>
				<td>Alliances</td>
				<td>Players</td>
				<td>Villages</td>
				<td>Population</td>
			</tr>
		";
		$last_country = $country;
	}
	$rows[] = "
		<tr>
			<td><a href='http://$name/'>$name</a></td>
			<td>$updated</td>
			<td>$status</td>
			<td>$guilds</td>
			<td>$players</td>
			<td>$villages</td>
			<td>$population</td>
		</tr>
	";
}
// }}}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html>
	<head>
		<title>TravMap Server List</title>
		<style>
BODY {
	background: #EEE;
	font-family: "Arial", sans-serif;
	font-size: 14px;
}
TH {
	background: #DDD;
}
TD {
	vertical-align: top;
	text-align: center;
	padding: 0px 10px 0px 10px;
}
		</style>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
	</head>
	<body>
		<?php print implode(", ", $links); ?>
		<p>
		<table border="1" align="center">
			<?php print $totals; ?>
			<?php print implode("\n", $rows); ?>
		</table>
	</body>
</html>
