#!/bin/sh
cd `dirname $0`
. config.sh
exec mysql -u$MYSQL_USER -p$MYSQL_PASS -h $MYSQL_HOST $MYSQL_DB $*
