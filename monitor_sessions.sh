#!/bin/bash

http_log=/home/BoB/logs/access.log
ram_path=/home/BoB/www/streambox/ram/
istreamdev_log=/home/BoB/logs/streambox.log

timeout_seconds=1800

for session in `\ls $ram_path/sessions/ | grep session`;
do
	# Only check live ones
	if [ "`cat $ram_path/sessions/$session/streaminfo | grep "type="`" != "type=tv" ]
	then
		continue
	fi

	# Check last time session was accessed
	last_get="`cat $http_log | grep "GET /ram/sessions/$session/stream.m3u8" | tail -n 1`"
	last_date="`echo "$last_get" | awk -F\[ '{ print $2 }' | awk -F\] '{ print $1 }'`"
	last_date="`echo $last_date | sed -e 's/\//\ /g' | sed -e 's/\:/\ /'`"

	last_date_num="`LANG= date -d "$last_date" +%s`"
	current_date_num="`LANG= date +%s`"

	if [ $((current_date_num - last_date_num)) -gt $timeout_seconds ]
	then
		echo "`date +"[%Y/%m/%d %H:%M:%S]"` Killing inactive session $session" >> $istreamdev_log
		find $ram_path -type l -name "$session" -exec rm {} \;
		rm $ram_path/sessions/$session/*
		rmdir $ram_path/sessions/$session
	fi
done
