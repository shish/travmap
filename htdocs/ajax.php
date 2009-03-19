<?php
require_once "database.php";
if($_GET["mode"] == "servers") {
	$country = sql_escape_string($_GET["country"]);
	$result = sql_query("SELECT * FROM servers WHERE country='$country' AND visible=True ORDER BY num");
	while($row = sql_fetch_row($result)) {
		print $row["name"] . "," . ($row["population"] > 1000) . "\n";
	}
}
?>
