#!/bin/sh

#
# update_text.sh (c) Shish 2006
#
# a script to update a text database
#

DBNAME=`echo $1 | sed 's/\./_/g'`

# if the SQL file is less than 4 hours old, leave it
if [ -f sql/$1.sql ] ; then
	NOW=`date +"%s"`
	THEN=`stat -L -c %Y sql/$1.sql`
	DIFF=`expr $NOW - $THEN`
	if [ $DIFF -lt 43200 ] ; then # 43200 sec = 12 hour
		exit
	fi
fi

wget -q http://$1/$2.sql -O sql/$1.sql


