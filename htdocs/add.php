<pre>
<?php
if(!preg_match("/^[a-z0-9\.\-]+$/", $_GET["server"])) {
	die("Invalid server name");
}

/*
if(!dns_get_record($_GET["server"])) {
	die("Can't find server");
}
*/

$server = $_GET["server"];
$num = (int)time();
print "adding $server\n";
chdir("/utils/");
system("./manage.py add " . escapeshellarg($server) . " $num 2>&1");
?>
</pre>

