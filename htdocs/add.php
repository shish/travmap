<pre>
<?php
if(
	preg_match("/^[a-z0-9\.]+$/", $_GET["server"]) &&
	preg_match("/^[A-Za-z ]+$/", $_GET["country"])
) {
	$server = $_GET["server"];
	$country = $_GET["country"];
	$num = (int)time();
	print "adding $country / $server\n";
	chdir("/home/shish/travmap.shishnet.org/utils/");
	system("./add_server $server '$country' $num");
}
?>
</pre>
