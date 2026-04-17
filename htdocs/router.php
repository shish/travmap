<?php
declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = ltrim($path, '/');

if ($path === '/' || $path === '') {
    require 'index.php';
    return true;
}

if ($file && file_exists($file) && is_file($file)) {
    return false;
}

http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>404 Not Found</title>
</head>
<body>
    <h1>404 Not Found</h1>
    <p>The requested file <code>' . htmlspecialchars($path) . '</code> was not found.</p>
</body>
</html>';
return true;
