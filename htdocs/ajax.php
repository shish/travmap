<?php
require_once "lib/database.php";
if($_GET["mode"] == "servers") {
	$st = $db->prepare("SELECT * FROM servers WHERE country=:country AND visible=True ORDER BY num");
	$st->bindParam(':country', $_GET["country"], PDO::PARAM_STR);
	$st->execute();
	foreach($st->fetchAll() as $row) {
		print $row["name"] . "," . ($row["population"] > 1000) . "\n";
	}
}
