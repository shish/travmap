<?php
/*
 * loadentities.php (c) Shish 2006
 *
 * Load the villages. If an owner is found who hasn't been found before,
 * assign them a colour and add them to the key
 */

require_once "database.php";
require_once "query.php";
require_once "localise.php";


$entities = Array();
$races = Array($words["roman"], $words["teuton"], $words["gaul"]);


/*
 * query db for villages, group by owning entity
 */
$result = sql_query($query);


// load data {{{
while($row = sql_fetch_row($result)) {
	$user_name = $row["owner_name"];
	$user_id = $row["owner_id"];
	$guild_name = $row["guild_name"];
	$guild_id = $row["guild_id"];
	$guild_group = $row["guild_group"];
	$town_name = $row["town_name"];
	$town_id = $row["town_id"];
	$race_name = $races[$row["race"]-1];
	$race_id = $row["race"];

	switch($groupby) {
		default:
		case "player":   $entity_id = $user_id;     $entity_name = $user_name;   break;
		case "alliance": $entity_id = $guild_id;    $entity_name = $guild_name;  break;
		case "group":    $entity_id = $guild_group; $entity_name = $guild_group; break;
		case "race":     $entity_id = $race_id;     $entity_name = $race_name;   break;
		case "town":     $entity_id = $town_id;     $entity_name = $town_name;   break;
	}

	if(!isset($entities[$entity_id])) {
		switch($groupby) {
			case "alliance":
				$entities[$entity_id]['link'] = "allianz.php?aid=".$row['guild_id'];
				break;
			case "player":
				$entities[$entity_id]['link'] = "spieler.php?uid=".$row['owner_id'];
				break;
			case "town":
				$entities[$entity_id]['link'] = "karte.php?d=". (($row['x']+257) + (256-$row['y'])*512);
				break;
		}
		$entities[$entity_id]['name'] = $entity_name;
		$entities[$entity_id]['guild'] = $row["guild_name"];
		$entities[$entity_id]['race_id'] = $row["race"];
		$entities[$entity_id]['count'] = 0;
	}
	else {
		$entities[$entity_id]['count']++;
	}

	$entities[$entity_id]['villages'][$entities[$entity_id]['count']]['name'] = $row['town_name'];
	$entities[$entity_id]['villages'][$entities[$entity_id]['count']]['owner'] = $row['owner_name'];
	$entities[$entity_id]['villages'][$entities[$entity_id]['count']]['guild'] = $row['guild_name'];
	$entities[$entity_id]['villages'][$entities[$entity_id]['count']]['population'] = $row['population'];
	$entities[$entity_id]['villages'][$entities[$entity_id]['count']]['x'] = $row['x'];
	$entities[$entity_id]['villages'][$entities[$entity_id]['count']]['y'] = $row['y'];
}
// }}}
?>
