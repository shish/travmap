<pre>
<?php
if(!preg_match("/^[a-z][a-z0-9\.\-]+$/", $_GET["server"])) {
    http_response_code(400);
	die("Invalid server name");
}

$server = $_GET["server"];
print "adding $server\n";
chdir("/utils/");
system("./manage.py add " . escapeshellarg($server) . " 2>&1");
?>
</pre>
