#!/bin/sh

cat >/app/config.php <<EOD
<?php
\$sql_host = "$SQL_HOST";
\$sql_user = "$SQL_USER";
\$sql_pass = "$SQL_PASS";
\$sql_db = "$SQL_DB";
EOD

cat >/utils/config.sh <<EOD
SQL_HOST=$SQL_HOST
SQL_USER=$SQL_USER
SQL_PASS=$SQL_PASS
SQL_DB=$SQL_DB
CACHE=/cache
STATUS=/app/status.txt
export PGPASSWORD=\$SQL_PASS
EOD

cd /app
/usr/bin/php -S 0.0.0.0:8000
