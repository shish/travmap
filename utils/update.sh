#!/bin/sh

#
# update.sh (c) Shish 2006
#
# calls the database specific update scripts
#

cd `dirname $0`
. config.sh

# create default folders
if [ ! -d ../sql ] ; then mkdir ../sql ; fi
if [ ! -d $CACHE ] ; then
	mkdir $CACHE
	zeroTo255=`seq -s " " -f %g 0 255`
	zeroToFF=`for n in $zeroTo255 ; do printf "%2.2x " $n ; done`
	for m in $zeroToFF ; do 
		mkdir $CACHE/$m
		echo -n "$m: "
		for n in $zeroToFF ; do
			echo -n .
			mkdir $CACHE/$m/$n
		done
		echo
	done
	chmod -R 777 $CACHE
fi

mysql -u$MYSQL_USER -p$MYSQL_PASS -h$MYSQL_HOST $MYSQL_DB --skip-column-names \
		-e "SELECT name,mapfile FROM servers" | xargs -l1 ./update_text.sh
mysql -u$MYSQL_USER -p$MYSQL_PASS -h$MYSQL_HOST $MYSQL_DB --skip-column-names \
		-e "SELECT name FROM servers" | xargs -l1 ./update_mysql.sh

sh ./clear_cache.sh

echo "Update complete at `date +%l:%M%p`" > $STATUS
