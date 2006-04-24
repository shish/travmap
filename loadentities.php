<?php
/*
 * loadentities.php (c) Shish 2006
 *
 * Load the villages. If an owner is found who hasn't been found before,
 * assign them a colour and add them to the key
 */

require_once "database.php";
require_once "query.php";


$entities = Array();


/*
 * query db for villages, group by owning entity
 */
$result = sql_query($query);
$dbh = null;
if(!$using_data_cache && $datacache) {
	require_once "options.php";
	$dbh = sqlite_open($datacache);
	if(@sqlite_query($dbh, "BEGIN TRANSACTION")) {
		sqlite_query($dbh, 
			"CREATE TABLE $table(
				x, y, population, race,
				owner_name, owner_id,
				guild_name, guild_id,
				town_name, town_id
			)");
	}
	else {
		sqlite_close($dbh);
		unlink($datacache);
		$datacache = false;
	}
}

while($row = sql_fetch_row($result)) {
	if(!$using_data_cache && $datacache) {
		sqlite_query($dbh, "INSERT INTO 
			$table(
				x, y, population, race, 
				owner_name, owner_id,
				guild_name, guild_id,
				town_name, town_id
			)
			VALUES(
				'{$row[x]}', '{$row[y]}', '{$row[population]}', '{$row[race]}',
				'{$row[owner_name]}', '{$row[owner_id]}',
				'{$row[guild_name]}', '{$row[guild_id]}',
				'{$row[town_name]}', '{$row[town_id]}'
			)
		");
	}

	$user_name = $row["owner_name"];
	$guild_name = $row["guild_name"];
	$town_name = $row["town_name"];
	$race_id = $row["race"];

	switch($groupby) {
		default:
		case "player":   $entity_name = $user_name;  break;
		case "alliance": $entity_name = $guild_name; break;
		case "race":     $entity_name = $race_id;    break;
		case "town":     $entity_name = $town_name;  break;
	}

	if(is_null($entities[$entity_name])) {
		$entities[$entity_name]['link'] = $algrp ? "allianz.php?aid=".$row['guild_id'] : "spieler.php?uid=".$key['owner_id'];
		$entities[$entity_name]['guild'] = $row["guild_name"];
		$entities[$entity_name]['race_id'] = $row["race"];
		$entities[$entity_name]['count'] = 0;
	}
	else {
		$entities[$entity_name]['count']++;
	}

	$entities[$entity_name]['villages'][$entities[$entity_name]['count']]['name'] = $row['town_name'];
	$entities[$entity_name]['villages'][$entities[$entity_name]['count']]['population'] = $row['population'];
	$entities[$entity_name]['villages'][$entities[$entity_name]['count']]['x'] = $row['x'];
	$entities[$entity_name]['villages'][$entities[$entity_name]['count']]['y'] = $row['y'];
}
if($datacache && !$using_data_cache) {
	sqlite_query($dbh, "END TRANSACTION");
}
?>
