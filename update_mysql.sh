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
		lochash MEDIUMINT UNSIGNED PRIMARY KEY NOT NULL, x SMALLINT NOT NULL, y SMALLINT NOT NULL, race TINYINT NOT NULL,
		town_id  MEDIUMINT UNSIGNED NOT NULL, town_name  CHAR(20) NOT NULL,
		owner_id MEDIUMINT UNSIGNED NOT NULL, owner_name CHAR(16) NOT NULL,
		guild_id MEDIUMINT UNSIGNED NOT NULL, guild_name CHAR(8) NOT NULL,
		population MEDIUMINT NOT NULL,
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

if [ `stat -c "%s" sql/$1.sql` -ge 256000 ] ; then
	perl -ne "s/INSERT INTO \`x_world\` VALUES \(//; s/\),\(/\n/g; s/\);//; print;" < sql/$1.sql > sql/$DBNAME.txt
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

