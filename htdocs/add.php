<pre>
<?php
if(!preg_match("/^[A-Z][A-Za-z ]+$/", $_GET["country"])) {
	die("Invalid country name");
}

if(!preg_match("/^[a-z0-9\.\-]+$/", $_GET["server"])) {
	die("Invalid server name");
}

/*
if(!dns_get_record($_GET["server"])) {
	die("Can't find server");
}
*/

$server = $_GET["server"];
$country = $_GET["country"];
$num = (int)time();
$mapfile = strpos($server, "-") ? "json" : "map";
print "adding $country / $server / $mapfile\n";
chdir("/utils/");
system("./add_server $server '$country' $num $mapfile");
?>
</pre>
