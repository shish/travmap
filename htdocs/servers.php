<?php
require_once "lib/database.php";
require_once "lib/util.php";

$res = $db->query("
	SELECT name,villages,updated,status,owners,guilds,population
	FROM servers
	WHERE visible=True
	ORDER BY name
");

header("Content-Type: text/plain");
$out = fopen('php://output', 'w');
fputcsv($out, array(
	'name',
	'country',
	'updated',
	'status',
	'villages',
	'owners',
	'guilds',
	'population',
));
while($row = $res->fetchAll()) {
	fputcsv($out, array(
		$row['name'],
		getSubdomain($row['name']),
		substr($row['updated'], 0, 16),
		$row['status'],
		$row['villages'],
		$row['owners'],
		$row['guilds'],
		$row['population'],
	));
}
fclose($out);
