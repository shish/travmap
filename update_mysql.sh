#!/bin/sh

#
# update_mysql.sh (c) Shish 2006
#
# a script to update a mysql database
#

DBNAME=`echo $1 | sed 's/\./_/g'`

. config.sh # mysql contact info

echo "
	CREATE TABLE IF NOT EXISTS $DBNAME(
		lochash MEDIUMINT UNSIGNED PRIMARY KEY, x SMALLINT, y SMALLINT, race TINYINT,
		town_id  MEDIUMINT UNSIGNED, town_name  CHAR(20),
		owner_id MEDIUMINT UNSIGNED, owner_name CHAR(16),
		guild_id MEDIUMINT UNSIGNED, guild_name CHAR(8),
		population MEDIUMINT,
		INDEX(town_name),
		INDEX(owner_name),
		INDEX(guild_name),
		INDEX(owner_id),
		INDEX(guild_id),
		INDEX(x),
		INDEX(y),
		INDEX(race),
		INDEX(population)
	);
" | mysql -u$MYSQL_USER -p$MYSQL_PASS -h $MYSQL_HOST $MYSQL_DB

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

