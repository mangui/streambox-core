#!/bin/bash

DIR=$1

# Get all files
for file in `seq -f "%05g" 1 99999`
do
	if [ -e "$DIR/$file.ts" ]
	then
		cat "$DIR/$file.ts"
	else
		exit
	fi
done
