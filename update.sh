#!/bin/sh

#
# update.sh (c) Shish 2006
#
# a script to update all the databases
#
# in my setup, cron runs it daily at 11:30pm GMT (00:30 server time)
#

cd /home/www/shish.is-a-geek.net/htdocs/projects/travmap

# create default folders
if [ ! -d cache ] ; then
	mkdir cache
	cd cache
	mkdir 0 1 2 3 4 5 6 7 8 9 a b c d e f
	cd ..
	chmod -R 777 cache
fi
if [ ! -d sql ] ; then mkdir sql ; fi
if [ ! -d db ] ; then mkdir db ; fi

CREATE="
CREATE TABLE x_world(
	lochash int, x int, y int, race int,
	town_id int, town_name char(32),
	owner_id int, owner_name char(32),
	guild_id int, guild_name char(32),
	population int
);
CREATE INDEX town_name ON x_world(town_name);
CREATE INDEX x ON x_world(x);
CREATE INDEX y ON x_world(y);
"

function update {
	# if the DB file doesn't exist, create one with the default schema
	if [ ! -r db/$1.db ] ; then
		echo $CREATE | sqlite db/$1.db
	fi

	# if the SQL file is more than 4 hours old, update it
	NOW=`date +"%s"`
	THEN=`stat -L -c %Y sql/$1.sql`
	DIFF=`dc -e "$NOW $THEN - p"`
	TIME=`dc -e "60 60 * 4 * p"`
	if [ $DIFF -gt $TIME ] ; then
		wget -q http://$1/$2.sql -O sql/$1.sql
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
				DROP INDEX x;
				DROP INDEX y;
				DELETE FROM x_world;
			"

			# sqlite doesn't like backticks; remove them
			pv -i 0.2 -N "$1" sql/$1.sql | perl -ne "s/\`x_world\`/x_world/; print;"

			echo "
				CREATE INDEX town_name ON x_world(town_name);
				CREATE INDEX x ON x_world(x);
				CREATE INDEX y ON x_world(y);
				END TRANSACTION;
			"
		) | sqlite db/$1.db
	else
		echo "$1's SQL file is empty!"
		return
	fi
}

update s1.travian.com map
update s2.travian.com map
update s3.travian.com map
update s4.travian.com map
update s5.travian.com map

update s1.travian.fr map
update s2.travian.fr map
update s3.travian.fr map
update s4.travian.fr map
update s5.travian.fr map
update s6.travian.fr map
update s7.travian.fr map
update s8.travian.fr map

update s1.travian.it map
update s2.travian.it map
update s3.travian.it map
update s4.travian.it map
update s5.travian.it map

update www.travian.org karte
update www.travian.at  karte

update s1.travian.net map
update s2.travian.net map

update s1.travian.nl map
update s2.travian.nl map

update welt1.travian.de karte
update welt2.travian.de karte
update welt3.travian.de karte

for n in cache/* ; do rm -f $n/* ; done

