#!/bin/sh

#
# update.	(c) Shish 2006
#
#
#

BASEDIR=/home/www/shish.is-a-geek.net/htdocs/projects/travmap

cd $BASEDIR

function foreach() {
	$1 s1.travian.com map
	$1 s2.travian.com map
	$1 s3.travian.com map
	$1 s4.travian.com map
	$1 s5.travian.com map

	$1 s1.travian.fr map
	$1 s2.travian.fr map
	$1 s3.travian.fr map
	$1 s4.travian.fr map
	$1 s5.travian.fr map
	$1 s6.travian.fr map
	$1 s7.travian.fr map
	$1 s8.travian.fr map

	$1 s1.travian.it map
	$1 s2.travian.it map
	$1 s3.travian.it map
	$1 s4.travian.it map
	$1 s5.travian.it map

	$1 www.travian.org karte
	$1 www.travian.at  karte

	$1 s1.travian.net map
	$1 s2.travian.net map

	$1 s1.travian.nl map
	$1 s2.travian.nl map

	$1 welt1.travian.de karte
	$1 welt2.travian.de karte
	$1 welt3.travian.de karte
}


# create default folders
if [ ! -d sql ] ; then mkdir sql ; fi
if [ ! -d cache ] ; then
	mkdir cache
	cd cache
	mkdir 0 1 2 3 4 5 6 7 8 9 a b c d e f
	cd ..
	chmod -R 777 cache
fi

foreach update_text.sh
foreach update_mysql.sh
# foreach update_sqlite.sh

for n in cache/* ; do rm -f $n/* ; done

