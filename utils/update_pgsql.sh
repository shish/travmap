#!/bin/sh

#
# update_mysql.sh (c) Shish 2006
#
# a script to update a mysql database
#

DBNAME=`echo $1 | sed 's/\./_/g'`

. config.sh # mysql contact info
data=../sql

echo -n "Updating $1's database" > $STATUS

if [ ! -f "$data/$1.sql" ] ; then
	echo "$1's SQL file does not exist!"
	exit
elif [ `stat -c "%s" $data/$1.sql` -le 64000 ] ; then
	echo "$1's SQL file is short!"
	exit
else
	./update_status $1 "Updating table"
	echo "
SET client_encoding = 'UTF8';
DROP TABLE $DBNAME;
CREATE TABLE $DBNAME(
	lochash INTEGER NOT NULL, x SMALLINT NOT NULL, y SMALLINT NOT NULL, race SMALLINT NOT NULL,
	town_id  INTEGER NOT NULL, town_name  VARCHAR(128) NOT NULL, -- 20
	owner_id INTEGER NOT NULL, owner_name VARCHAR(128) NOT NULL, -- 16
	guild_id INTEGER NOT NULL, guild_name VARCHAR(64) NOT NULL,  -- 8
	population SMALLINT NOT NULL
);

COPY $DBNAME (lochash, x, y, race, town_id, town_name, owner_id, owner_name, guild_id, guild_name, population) FROM stdin;" > $data/$DBNAME.txt
	if [ `file $data/$1.sql | grep Unicode | wc -l` -eq 1 ] ; then
		perl sql2pg.pl < $data/$1.sql >> $data/$DBNAME.txt
	else
		perl sql2pg.pl < $data/$1.sql | iconv -f iso-8859-1 -t utf8 >> $data/$DBNAME.txt
	fi
	echo "\.
CREATE INDEX ${DBNAME}_town_id ON $DBNAME(town_id);
CREATE INDEX ${DBNAME}_town_name ON $DBNAME(town_name);
CREATE INDEX ${DBNAME}_town_name_lower ON $DBNAME(lower(town_name));
CREATE INDEX ${DBNAME}_owner_id ON $DBNAME(owner_id);
CREATE INDEX ${DBNAME}_owner_name ON $DBNAME(owner_name);
CREATE INDEX ${DBNAME}_owner_name_lower ON $DBNAME(lower(owner_name));
CREATE INDEX ${DBNAME}_guild_id ON $DBNAME(guild_id);
CREATE INDEX ${DBNAME}_guild_name ON $DBNAME(guild_name);
CREATE INDEX ${DBNAME}_guild_name_lower ON $DBNAME(lower(guild_name));
CREATE INDEX ${DBNAME}_x ON $DBNAME(x);
CREATE INDEX ${DBNAME}_y ON $DBNAME(y);
CREATE INDEX ${DBNAME}_diag ON $DBNAME((x-y));
CREATE INDEX ${DBNAME}_race ON $DBNAME(race);
CREATE INDEX ${DBNAME}_population ON $DBNAME(population);

UPDATE servers SET villages=(SELECT COUNT(*) FROM ${DBNAME}) WHERE name='$1';
UPDATE servers SET owners=(SELECT COUNT(DISTINCT owner_id) FROM ${DBNAME}) WHERE name='$1';
UPDATE servers SET guilds=(SELECT COUNT(DISTINCT guild_id) FROM ${DBNAME}) WHERE name='$1';
UPDATE servers SET population=(SELECT SUM(population) FROM ${DBNAME}) WHERE name='$1';
UPDATE servers SET width =(SELECT MAX(x) - MIN(x) FROM ${DBNAME}) WHERE name='$1';
UPDATE servers SET height=(SELECT MAX(x) - MIN(x) FROM ${DBNAME}) WHERE name='$1';
	" >> $data/$DBNAME.txt
	psql -q -U $MYSQL_USER $MYSQL_DB < $data/$DBNAME.txt
	rm -f $data/$DBNAME.txt
	./update_status $1 "`date '+%Y-%m-%d %H:%M:%S'`"
fi

echo -n > $STATUS

