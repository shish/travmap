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
	) CHARSET=utf8;
" | mysql -u$MYSQL_USER -p$MYSQL_PASS -h $MYSQL_HOST $MYSQL_DB

if [ ! -f "sql/$1.sql" ] ; then
	echo "$1's SQL file does not exist!"
	exit
elif [ `stat -c "%s" sql/$1.sql` -le 128000 ] ; then
	echo "$1's SQL file is short!"
	exit
else
	SQL2CSV="s/INSERT INTO \`x_world\` VALUES \(//; s/\),\(/\n/g; s/\);//; print;"
	if [ `file sql/$1.sql | grep Unicode | wc -l` -eq 1 ] ; then
		perl -ne "$SQL2CSV" < sql/$1.sql > sql/$DBNAME.txt
	else
		perl -ne "$SQL2CSV" < sql/$1.sql | iconv -f iso-8859-1 -t utf8 > sql/$DBNAME.txt
	fi
	mysqlimport \
		-u$MYSQL_USER -p$MYSQL_PASS -h $MYSQL_HOST $MYSQL_DB \
		--delete --local \
		--fields-terminated-by="," --fields-optionally-enclosed-by="'" \
		sql/$DBNAME.txt
	rm -f sql/$DBNAME.txt
fi

