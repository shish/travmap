<pre>
<?php
if(!preg_match("/^[a-z0-9\.\-]+$/", $_GET["server"])) {
	die("Invalid server name");
}

$server = $_GET["server"];
print "adding $server\n";
chdir("/utils/");
system("./manage.py add " . escapeshellarg($server) . " 2>&1");
?>
</pre>

