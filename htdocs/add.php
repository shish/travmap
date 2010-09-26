<pre>
<?php
if(
	preg_match("/^[a-z0-9\.]+$/", $_GET["server"]) &&
	preg_match("/^[A-Za-z ]+$/", $_GET["country"])
) {
	$server = $_POST["server"];
	$country = $_POST["country"];
	$num = time();
	print "adding $country / $server\n";
	chdir("/home/shish/travmap.shishnet.org/utils/");
	system("./add_server $server '$country' $num");
}
?>
</pre>
