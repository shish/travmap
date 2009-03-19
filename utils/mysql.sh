#!/bin/sh
cd `dirname $0`
. config.sh
mysql -u$SQL_USER -p$SQL_PASS -h $SQL_HOST $SQL_DB "$@"
