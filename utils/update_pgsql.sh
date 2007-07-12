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
elif [ `stat -c "%s" $data/$1.sql` -le 128000 ] ; then
	echo "$1's SQL file is short!"
	exit
else
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

DROP TABLE ${DBNAME}_town;
CREATE TABLE ${DBNAME}_town(
	lochash INTEGER NOT NULL, x SMALLINT NOT NULL, y SMALLINT NOT NULL,
	id INTEGER NOT NULL, name VARCHAR(128) NOT NULL, name_lower VARCHAR(128) NOT NULL,
	owner_id INTEGER NOT NULL,
	population SMALLINT NOT NULL
);

DROP TABLE ${DBNAME}_owner;
CREATE TABLE ${DBNAME}_owner(
	id INTEGER NOT NULL, name VARCHAR(128) NOT NULL, name_lower VARCHAR(128) NOT NULL,
	race SMALLINT NOT NULL, guild_id INTEGER NOT NULL
);

DROP TABLE ${DBNAME}_guild;
CREATE TABLE ${DBNAME}_guild(
	id INTEGER NOT NULL, name VARCHAR(64) NOT NULL, name_lower VARCHAR(64) NOT NULL
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
CREATE INDEX ${DBNAME}_owner_id ON $DBNAME(owner_id);
CREATE INDEX ${DBNAME}_owner_name ON $DBNAME(owner_name);
CREATE INDEX ${DBNAME}_guild_id ON $DBNAME(guild_id);
CREATE INDEX ${DBNAME}_guild_name ON $DBNAME(guild_name);
CREATE INDEX ${DBNAME}_x ON $DBNAME(x);
CREATE INDEX ${DBNAME}_y ON $DBNAME(y);
CREATE INDEX ${DBNAME}_x_y ON $DBNAME(x, y);
CREATE INDEX ${DBNAME}_race ON $DBNAME(race);
CREATE INDEX ${DBNAME}_population ON $DBNAME(population);


INSERT INTO ${DBNAME}_town(lochash, x, y, id, name, name_lower, owner_id, population)
	SELECT lochash, x, y, town_id, town_name, LOWER(town_name), owner_id, population
	FROM ${DBNAME};
INSERT INTO ${DBNAME}_owner(id, name, name_lower, race, guild_id)
	SELECT owner_id, MAX(owner_name), LOWER(MAX(owner_name)), MAX(race), MAX(guild_id)
	FROM ${DBNAME} GROUP BY owner_id ORDER BY owner_id;
INSERT INTO ${DBNAME}_guild(id, name, name_lower)
	SELECT guild_id, MAX(guild_name), LOWER(MAX(guild_name))
	FROM ${DBNAME} GROUP BY guild_id ORDER BY guild_id;

CREATE INDEX ${DBNAME}__town_id ON ${DBNAME}_town(id);
CREATE INDEX ${DBNAME}__town_name ON ${DBNAME}_town(name);
CREATE INDEX ${DBNAME}__town_name_lower ON ${DBNAME}_town(name_lower);
CREATE INDEX ${DBNAME}__x ON ${DBNAME}_town(x);
CREATE INDEX ${DBNAME}__y ON ${DBNAME}_town(y);
CREATE INDEX ${DBNAME}__x_y ON ${DBNAME}_town(x, y);
CREATE INDEX ${DBNAME}__population ON ${DBNAME}_town(population);

CREATE INDEX ${DBNAME}__owner_id ON ${DBNAME}_owner(id);
CREATE INDEX ${DBNAME}__owner_name ON ${DBNAME}_owner(name);
CREATE INDEX ${DBNAME}__owner_name_lower ON ${DBNAME}_owner(name_lower);
CREATE INDEX ${DBNAME}__race ON ${DBNAME}_owner(race);

CREATE INDEX ${DBNAME}__guild_id ON ${DBNAME}_guild(id);
CREATE INDEX ${DBNAME}__guild_name ON ${DBNAME}_guild(name);
CREATE INDEX ${DBNAME}__guild_name_lower ON ${DBNAME}_guild(name_lower);


UPDATE servers SET villages=(SELECT COUNT(*) FROM $DBNAME) WHERE name='$1';
	" >> $data/$DBNAME.txt
	psql -q -U $MYSQL_USER $MYSQL_DB < $data/$DBNAME.txt
	rm -f $data/$DBNAME.txt
fi

echo -n > $STATUS
