#!/bin/sh

cd `dirname $0`
. config.sh

start=`date +%l:%M%p`

rm -f $CACHE/*.txt
count=0
for n in `ls -t $CACHE` ; do
	count=$[$count+1]
	percent=$[$count*100/256]
	dir=`basename $n`
	echo -n "Cleaning cache $percent% (started $start)" > $STATUS
	for o in $CACHE/$n/* ; do
		rm -f $o/*
	done
done

echo -n > $STATUS
