<pre>
<?php
if(!preg_match("/^[A-Z][A-Za-z ]+$/", $_GET["country"])) {
	die("Invalid country name");
}

if(!(
	preg_match("/^[a-z0-9\.\-]+$/", $_GET["server"]) &&
	dns_get_record($_GET["server"])
)) {
	die("Invalid server name");
}

$server = $_GET["server"];
$country = $_GET["country"];
$num = (int)time();
$mapfile = strpos($server, "-") ? "json" : "map";
print "adding $country / $server / $mapfile\n";
chdir("/data/sites/travmap.shishnet.org/utils/");
system("./add_server $server '$country' $num $mapfile");
?>
</pre>
