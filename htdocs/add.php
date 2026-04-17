<?php
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$server = $_GET["server"] ?? "";

if (!preg_match("/^[a-z][a-z0-9\.\-]+$/", $server)) {
	http_response_code(400);
	die("Invalid server name");
}

print "adding $server\n";
chdir("/utils/");
system("./manage.py add " . escapeshellarg($server) . " 2>&1");
