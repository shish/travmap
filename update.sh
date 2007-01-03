#!/bin/sh

#
# update.sh (c) Shish 2006
#
# calls the database specific update scripts
#

cd `dirname $0`

function foreach() {
	sh $1 s1.travian.com map
	sh $1 s2.travian.com map
	sh $1 s3.travian.com map
	sh $1 s4.travian.com map
	sh $1 s5.travian.com map
	sh $1 s6.travian.com map
	sh $1 s7.travian.com map

	sh $1 s1.travian.fr map
	sh $1 s2.travian.fr map
	sh $1 s3.travian.fr map
	sh $1 s4.travian.fr map
	sh $1 s5.travian.fr map
	sh $1 s6.travian.fr map
	sh $1 87.106.1.213 map
	sh $1 s7.travian.fr map
	sh $1 s8.travian.fr map
	sh $1 s9.travian.fr map
	sh $1 s10.travian.fr map
	sh $1 85.214.42.15 map
	sh $1 s11.travian.fr map
	sh $1 87.106.19.213 map
	sh $1 s12.travian.fr map
	sh $1 s13.travian.fr map
	sh $1 s14.travian.fr map
	sh $1 s15.travian.fr map
	sh $1 s16.travian.fr map

	sh $1 s1.travian.it map
	sh $1 s2.travian.it map
	sh $1 s3.travian.it map
	sh $1 s4.travian.it map
	sh $1 s5.travian.it map
	sh $1 s6.travian.it map
	sh $1 s7.travian.it map
	sh $1 s8.travian.it map
	sh $1 s9.travian.it map
	sh $1 s10.travian.it map
	sh $1 87.106.12.134 map # = s10
	sh $1 s11.travian.it map

	sh $1 www.travian.org karte
	sh $1 www.travian.at  karte

	sh $1 s1.travian.net map
	sh $1 s2.travian.net map
	sh $1 s3.travian.net map
	sh $1 s4.travian.net map
	sh $1 speed.travian.net map

	sh $1 s1.travian.nl map
	sh $1 s2.travian.nl map
	sh $1 s3.travian.nl map
	sh $1 speed.travian.nl map
	sh $1 s4.travian.nl map

	sh $1 welt1.travian.de karte
	sh $1 welt2.travian.de karte
	sh $1 welt3.travian.de karte
	sh $1 welt4.travian.de karte
	sh $1 welt5.travian.de karte
	sh $1 speed.travian.de karte

	sh $1 s1.travian3.pl map
	sh $1 s2.travian3.pl map
	sh $1 s3.travian3.pl map
	
	sh $1 s1.travian.com.pl map

	sh $1 s1.travian.com.pt map
	sh $1 s2.travian.com.pt map
	
	sh $1 s1.travian.ru map
	
	sh $1 s1.travian.com.tr map
	sh $1 s2.travian.com.tr map
	
	sh $1 s1.travian.ru map
	
	sh $1 s1.travian.ro map
	
	sh $1 s1.travian.dk map
	
	sh $1 s1.travian.se map

	sh $1 s1.travian.hk map

	sh $1 s1.travian.cn map
	
	sh $1 s1.travian.cz map
}

# create default folders
if [ ! -d sql ] ; then mkdir sql ; fi
if [ ! -d cache ] ; then
	mkdir cache
	zeroTo255=`seq -s " " -f %g 0 255`
	zeroToFF=`for n in $zeroTo255 ; do printf "%2.2x " $n ; done`
	for m in $zeroToFF ; do 
		mkdir cache/$m
	done
	chmod -R 777 cache
fi

foreach ./update_text.sh
foreach ./update_mysql.sh
# foreach update_sqlite.sh

rm -f cache/*.txt
for n in cache/* ; do rm -f $n/* ; done

