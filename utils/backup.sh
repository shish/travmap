#!/bin/bash
cd `dirname $0`
. config.sh
pg_dump -U $SQL_USER -h $SQL_HOST -t servers $SQL_DB > servers.sql
