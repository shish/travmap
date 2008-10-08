#!/bin/sh

#
# update_text.sh (c) Shish 2006
#
# a script to update a text database
#

DBNAME=`echo $1 | sed 's/\./_/g'`
. config.sh
data=../sql

echo -n "Downloading http://$1/$2.sql... "
echo -n "Downloading http://$1/$2.sql... " > $STATUS

# if the SQL file is less than 4 hours old, leave it
if [ -f $data/$1.sql.gz ] ; then
	NOW=`date +"%s"`
	THEN=`stat -L -c %Y $data/$1.sql.gz`
	DIFF=`expr $NOW - $THEN`
	if [ $DIFF -lt 43200 ] ; then # 43200 sec = 12 hour
		echo "cached"
		exit
	fi
fi

if wget -q http://$1/$2.sql.gz -O $data/$1.sql.gz ; then
	touch $data/$1.sql.gz
	if [ `stat -c "%s" $data/$1.sql.gz` -le 64000 ] ; then
		./update_status $1 "$2.sql is short"
	else
		./update_status $1 "$2.sql downloaded"
	fi
	echo "ok"
else
	./update_status $1 "$2.sql missing"
	echo "failed"
fi

echo -n > $STATUS
