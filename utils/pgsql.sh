#!/bin/sh
cd `dirname $0`
. config.sh
psql -U $SQL_USER -h $SQL_HOST $SQL_DB "$@"
