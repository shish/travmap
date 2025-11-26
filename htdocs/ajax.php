<?php
require_once "lib/database.php";
require_once "lib/util.php";
if($_GET["mode"] == "servers") {
	$country = $_GET["country"];
	$res = $db->query("SELECT * FROM servers");
	foreach($res->fetchAll() as $row) {
		$subdomain = getSubdomain($row["name"]);
		if ($subdomain == $country) {
			print $row["name"] . "," . ($row["population"] > 1000) . "\n";
		}
	}
}
