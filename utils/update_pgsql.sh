#!/bin/bash

#
# update_pgsql.sh (c) Shish 2006
#
# a script to update a pgsql database
#

DBNAME=`echo $1 | sed 's/\./_/g'`

. config.sh # pgsql contact info
data=../sql

echo -n "Updating $1's database" > $STATUS

if [ ! -f "$data/$1.sql.gz" ] ; then
	echo "$1's SQL file does not exist!"
	exit
elif [ `stat -c "%s" $data/$1.sql.gz` -le 32000 ] ; then
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
	if [ `zcat $data/$1.sql.gz | head -n 500 | file - | grep Unicode | wc -l` -eq 1 ] ; then
		zcat $data/$1.sql.gz | perl sql2pg.pl >> $data/$DBNAME.txt
	else
		zcat $data/$1.sql.gz | perl sql2pg.pl | iconv -f iso-8859-1 -t utf8 >> $data/$DBNAME.txt
	fi
	echo "\.
CREATE INDEX ${DBNAME}_town_id ON $DBNAME(town_id);
CREATE INDEX ${DBNAME}_town_name ON $DBNAME(town_name);
-- CREATE INDEX ${DBNAME}_town_name_lower ON $DBNAME(lower(town_name));
CREATE INDEX ${DBNAME}_owner_id ON $DBNAME(owner_id);
CREATE INDEX ${DBNAME}_owner_name ON $DBNAME(owner_name);
-- CREATE INDEX ${DBNAME}_owner_name_lower ON $DBNAME(lower(owner_name));
CREATE INDEX ${DBNAME}_guild_id ON $DBNAME(guild_id);
CREATE INDEX ${DBNAME}_guild_name ON $DBNAME(guild_name);
-- CREATE INDEX ${DBNAME}_guild_name_lower ON $DBNAME(lower(guild_name));
CREATE INDEX ${DBNAME}_x ON $DBNAME(x);
CREATE INDEX ${DBNAME}_y ON $DBNAME(y);
-- CREATE INDEX ${DBNAME}_diag ON $DBNAME((x-y));
-- CREATE INDEX ${DBNAME}_race ON $DBNAME(race);
CREATE INDEX ${DBNAME}_population ON $DBNAME(population);

UPDATE servers SET
	villages=(SELECT COUNT(*) FROM ${DBNAME}),
	owners=(SELECT COUNT(DISTINCT owner_id) FROM ${DBNAME}),
	guilds=(SELECT COUNT(DISTINCT guild_id) FROM ${DBNAME}),
	population=(SELECT SUM(population) FROM ${DBNAME}),
	width =(SELECT MAX(x) - MIN(x) FROM ${DBNAME}),
	height=(SELECT MAX(x) - MIN(x) FROM ${DBNAME}),
	updated=now()
WHERE name='$1';
	" >> $data/$DBNAME.txt
	psql -q -U $SQL_USER $SQL_DB < $data/$DBNAME.txt
	rm -f $data/$DBNAME.txt
	./update_status $1 "ok"
fi

echo -n > $STATUS

