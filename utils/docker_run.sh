#!/bin/sh

cat >/app/config.php <<EOD
<?php
\$sql_dsn = "sqlite:/data/travmap.sqlite";
EOD

cat >/utils/config.sh <<EOD
SQL_DB=/data/travmap.sqlite
CACHE=/cache
STATUS=/app/status.txt
EOD

cd /app
/usr/bin/php -S 0.0.0.0:8000
