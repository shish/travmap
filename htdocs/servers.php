<?php
require_once "lib/database.php";

$res = $db->query("
	SELECT name,country,villages,updated,status,owners,guilds,population
	FROM servers
	WHERE visible=True
	ORDER BY country, name
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
		$row['country'],
		substr($row['updated'], 0, 16),
		$row['status'],
		$row['villages'],
		$row['owners'],
		$row['guilds'],
		$row['population'],
	));
}
fclose($out);
