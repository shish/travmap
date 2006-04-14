#!/bin/sh

#
# update_sqlite.sh (c) Shish 2006
#
# a script to update an sqlite database
#

# if the DB file doesn't exist, create one with the default schema
if [ ! -r db/$1.db ] ; then
	echo "
		CREATE TABLE x_world(
			lochash int, x int, y int, race int,
			town_id int, town_name char(32),
			owner_id int, owner_name char(32),
			guild_id int, guild_name char(32),
			population int
		);
		CREATE INDEX town_name ON x_world(town_name);
		CREATE INDEX owner_name ON x_world(owner_name);
		CREATE INDEX guild_name ON x_world(guild_name);
		CREATE INDEX x ON x_world(x);
		CREATE INDEX y ON x_world(y);
	" | sqlite db/$1.db
fi

if [ -s sql/$1.sql ] ; then
	# insert bulk into x_world
	# o) transaction makes things work entirely in memory
	#    without being written to disk until it's finished
	# o) dropping indexes before and recreating them after
	#    is faster than updating them with every insert
	(
		echo "
			BEGIN TRANSACTION;
			DROP INDEX town_name;
			DROP INDEX guild_name;
			DROP INDEX owner_name;
			DROP INDEX x;
			DROP INDEX y;
			DELETE FROM x_world;
		"

		# sqlite doesn't like backticks; remove them
		perl -ne "s/\`x_world\`/x_world/; print;" < sql/$1.sql

		echo "
			CREATE INDEX town_name ON x_world(town_name);
			CREATE INDEX owner_name ON x_world(owner_name);
			CREATE INDEX guild_name ON x_world(guild_name);
			CREATE INDEX x ON x_world(x);
			CREATE INDEX y ON x_world(y);
			END TRANSACTION;
		"
	) | sqlite db/$1.db
else
	echo "$1's SQL file is empty!"
	exit
fi
