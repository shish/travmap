#!/bin/sh

#
# update_mysql.sh (c) Shish 2006
#
# a script to update a mysql database
#

DBNAME=`echo $1 | sed 's/\./_/g'`

. config.sh # mysql contact info

if [ -f create_$DBNAME ] ; then
	echo "
		CREATE TABLE $DBNAME(
			lochash int primary key, x int, y int, race int,
			town_id  int, town_name  char(20),
			owner_id int, owner_name char(16),
			guild_id int, guild_name char(8),
			population int
		);
		CREATE INDEX town_name ON $DBNAME(town_name);
		CREATE INDEX owner_name ON $DBNAME(owner_name);
		CREATE INDEX guild_name ON $DBNAME(guild_name);
		CREATE INDEX owner_id ON $DBNAME(owner_id);
		CREATE INDEX guild_id ON $DBNAME(guild_id);
		CREATE INDEX x ON $DBNAME(x);
		CREATE INDEX y ON $DBNAME(y);
		CREATE INDEX race ON $DBNAME(race);
		CREATE INDEX population ON $DBNAME(population);
	" | mysql -u$MYSQL_USER -p$MYSQL_PASS -h $MYSQL_HOST $MYSQL_DB
fi

if [ -s sql/$1.sql ] ; then
	perl -ne "s/INSERT INTO \`x_world\` VALUES \(//; s/\);//; print;" < sql/$1.sql > sql/$DBNAME.txt
	# yes it's hardcoded. mysql is set local access only :P
	mysqlimport \
		-u$MYSQL_USER -p$MYSQL_PASS -h $MYSQL_HOST $MYSQL_DB \
		--delete --local \
		--fields-terminated-by="," --fields-optionally-enclosed-by="'" \
		sql/$DBNAME.txt
	rm -f sql/$DBNAME.txt
else
	echo "$1's SQL file is empty!"
	exit
fi

