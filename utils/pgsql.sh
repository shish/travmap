#!/bin/sh
cd `dirname $0`
. config.sh
psql -U $MYSQL_USER -h $MYSQL_HOST $MYSQL_DB "$@"
