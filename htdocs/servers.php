<?php
require_once "localise.php";
require_once "version.php";
require_once "database.php";


$m = date("m"); // Month value
$d = date("d"); //today's date
$y = date("Y"); // Year value
$today = date('Y-m-d');
$yesterday = date('Y-m-d', mktime(0,0,0,$m,($d-1),$y));

$rows = array();
$links = array();
$res = sql_query("
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
while($row = sql_fetch_row($res)) {
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
