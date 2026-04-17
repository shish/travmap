<?php
declare(strict_types=1);

require_once "lib/database.php";
require_once "lib/util.php";
require_once "lib/localise.php";

// totals {{{
$res = $db->query("
	SELECT
		SUM(villages) AS villages,
		SUM(guilds) AS guilds,
		SUM(owners) AS owners,
		SUM(population) AS population
	FROM servers");
$row = $res->fetch();
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
				<td>" . h((int)($row['guilds'] ?? 0)) . "</td>
				<td>" . h((int)($row['owners'] ?? 0)) . "</td>
				<td>" . h((int)($row['villages'] ?? 0)) . "</td>
				<td>" . h((int)($row['population'] ?? 0)) . "</td>
			</tr>
";
// }}}

// rows {{{
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

$rows = [];
$links = [];
$last_country = "";
$dbrows = [];
$res = $db->query("
	SELECT name, villages, updated, status, owners, guilds, population
	FROM servers
	ORDER BY name
");
foreach($res->fetchAll() as $row) {
	$dbrows[$row["name"]] = $row;
}
uksort($dbrows, "wwwcmp");
foreach($dbrows as $row) {
	$name = $row['name'];
	$country = getSubdomain($name);
	$villages = (int)$row['villages'];
	$updated = (string)$row['updated'];
	$status = (string)$row['status'];

	if ($status === "ok" || $status === "map.sql downloaded" || $status === "karte.sql downloaded") {
		$status = "<span class='status-good'>" . h($status) . "</span>";
	} else {
		$status = "<span class='status-bad'>" . h($status) . "</span>";
	}

	$updated = substr($updated, 0, 16);
	if (substr($updated, 0, 10) === $today) {
		$updated = "<span class='updated-today'>" . h($updated) . "</span>";
	} elseif (substr($updated, 0, 10) === $yesterday) {
		$updated = "<span class='updated-yesterday'>" . h($updated) . "</span>";
	} else {
		$updated = "<span class='updated-old'>" . h($updated) . "</span>";
	}

	$players = (int)$row['owners'];
	$guilds = (int)$row['guilds'];
	$population = (int)$row['population'];

	if ($country !== $last_country) {
		$links[] = "<a href='#" . ha($country) . "'>" . h($country) . "</a>";
		$rows[] = "<tr><td colspan='7'>&nbsp;</td></tr>";
		$rows[] = "
			<tr><th colspan='7'><a id='" . ha($country) . "'>" . h($country) . "</a></th></tr>
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
			<td><a href='http://" . ha($name) . "/'>" . h($name) . "</a></td>
			<td>$updated</td>
			<td>$status</td>
			<td>" . h($guilds) . "</td>
			<td>" . h($players) . "</td>
			<td>" . h($villages) . "</td>
			<td>" . h($population) . "</td>
		</tr>
	";
}
// }}}

?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>TravMap Server List</title>
		<link rel="stylesheet" href="style.css" type="text/css">
	</head>
	<body>
		<?php echo implode(", ", $links); ?>
		<p></p>
		<table border="1" id="server-list">
			<?php echo $totals; ?>
			<?php echo implode("\n", $rows); ?>
		</table>
	</body>
</html>
