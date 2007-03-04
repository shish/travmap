#!/bin/sh

#
# update.sh (c) Shish 2006
#
# calls the database specific update scripts
#

cd `dirname $0`
. config.sh

cache=../cache

# create default folders
if [ ! -d ../sql ] ; then mkdir ../sql ; fi
if [ ! -d $cache ] ; then
	mkdir $cache
	zeroTo255=`seq -s " " -f %g 0 255`
	zeroToFF=`for n in $zeroTo255 ; do printf "%2.2x " $n ; done`
	for m in $zeroToFF ; do 
		mkdir $cache/$m
	done
	chmod -R 777 $cache
fi

mysql -u$MYSQL_USER -p$MYSQL_PASS -h$MYSQL_HOST $MYSQL_DB --skip-column-names \
		-e "SELECT name,mapfile FROM servers" | xargs -l1 ./update_text.sh
mysql -u$MYSQL_USER -p$MYSQL_PASS -h$MYSQL_HOST $MYSQL_DB --skip-column-names \
		-e "SELECT name FROM servers" | xargs -l1 ./update_mysql.sh

rm -f $cache/*.txt
for n in $cache/* ; do rm -f $n/* ; done

