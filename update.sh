#!/bin/sh

#
# update.sh (c) Shish 2006
#
# calls the database specific update scripts
#

cd `dirname $0`

function foreach() {
#	defenc=windows-1252
	defenc=iso-8859-1
	sh $1 s1.travian.com map utf8
	sh $1 s2.travian.com map $defenc
	sh $1 s3.travian.com map $defenc
	sh $1 s4.travian.com map $defenc
	sh $1 s5.travian.com map $defenc
	sh $1 s6.travian.com map $defenc
	sh $1 s7.travian.com map utf8

	sh $1 s1.travian.fr map $defenc
	sh $1 s2.travian.fr map $defenc
	sh $1 s3.travian.fr map $defenc
	sh $1 s4.travian.fr map $defenc
	sh $1 s5.travian.fr map $defenc
	sh $1 s6.travian.fr map $defenc
	sh $1 87.106.1.213 map $defenc
	sh $1 s7.travian.fr map $defenc
	sh $1 s8.travian.fr map $defenc
	sh $1 s9.travian.fr map $defenc
	sh $1 s10.travian.fr map $defenc
	sh $1 85.214.42.15 map $defenc
	sh $1 s11.travian.fr map $defenc
	sh $1 87.106.19.213 map $defenc
	sh $1 s12.travian.fr map $defenc
	sh $1 s13.travian.fr map $defenc
	sh $1 s14.travian.fr map $defenc
	sh $1 s15.travian.fr map $defenc

	sh $1 s1.travian.it map utf8
	sh $1 s2.travian.it map $defenc
	sh $1 s3.travian.it map $defenc
	sh $1 s4.travian.it map $defenc
	sh $1 s5.travian.it map $defenc
	sh $1 s6.travian.it map $defenc
	sh $1 s7.travian.it map $defenc
	sh $1 s8.travian.it map $defenc
	sh $1 s9.travian.it map $defenc
	sh $1 s10.travian.it map $defenc
	sh $1 87.106.12.134 map $defenc # = s10
	sh $1 s11.travian.it map $defenc

	sh $1 www.travian.org karte utf8
	sh $1 www.travian.at  karte utf8

	sh $1 s1.travian.net map $defenc
	sh $1 s2.travian.net map $defenc
	sh $1 s3.travian.net map utf8
	sh $1 s4.travian.net map utf8
	sh $1 speed.travian.net map utf8

	sh $1 s1.travian.nl map $defenc
	sh $1 s2.travian.nl map $defenc
	sh $1 s3.travian.nl map $defenc
	sh $1 speed.travian.nl map utf8
	sh $1 s4.travian.nl map $defenc

	sh $1 welt1.travian.de karte utf8
	sh $1 welt2.travian.de karte $defenc
	sh $1 welt3.travian.de karte $defenc
	sh $1 welt4.travian.de karte $defenc
	sh $1 welt5.travian.de karte utf8
	sh $1 speed.travian.de karte utf8

	sh $1 s1.travian3.pl map utf8
	sh $1 s2.travian3.pl karte utf8
	
	sh $1 s1.travian.com.pl map utf8

	sh $1 s1.travian.com.pt map utf8
	sh $1 s2.travian.com.pt map utf8
	
	sh $1 s1.travian.ru map utf8
	
	sh $1 s1.travian.com.tr map utf8
	
	sh $1 s1.travian.ru map utf8
	
	sh $1 s1.travian.ro map utf8
	
	sh $1 s1.travian.dk map utf8
}

echo -n > update.log

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

