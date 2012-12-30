#!/bin/bash

cd `dirname $0`
. config.sh

rm -f $CACHE/*.txt

echo -n > $STATUS
