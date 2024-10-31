#!/bin/bash

# Find the first available pot
POT=`/bin/ls -1 *.pot|head`

if [ "$POT" == "" ]
then
    echo "There were no .pot files found. Can not continue."
    exit
fi

DOMAIN=`echo $POT|sed "s/\.pot//"`

cp "$DOMAIN.pot" lang/$DOMAIN.pot
OPTIONS="-s -j --no-wrap -d $DOMAIN -p lang -o $DOMAIN.pot --omit-header"
echo xgettext $OPTIONS $DOMAIN*php 
xgettext $OPTIONS $DOMAIN*php 
