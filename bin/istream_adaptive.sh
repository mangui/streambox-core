#!/bin/bash

###########################
# Configuration
###########################
NBQUALITIES=4
declare -a QUALITIES
#			VRATE	ARATE	XY
QUALITIES=(	[1]="	200k	48k	320x240"	\
		[2]="	350k	48k	480x320"	\
		[3]="	750k	64k	640x480"	\
		[4]="	900k	64k	720x576"	\
	  )

##########################
# Code, don't modify
##########################

function get_quality
{
	qualityid=$1
	qualityname=$2

	qualities=${QUALITIES[$qualityid]}

	case "$qualityname" in
	"VRATE")
		echo $qualities | awk '{ print $1}'
		;;
	"ARATE")
		echo $qualities | awk '{ print $2}'
		;;
	"BW")
		vrate=`echo $qualities | awk '{ print $1}'`
		vrate=${vrate:0:-1}
		arate=`echo $qualities | awk '{ print $2}'`
		arate=${arate:0:-1}
		echo $((vrate*1024 + vrate*102 + arate*1024 + arate*102))
		;;
	"XY")
		echo $qualities | awk '{ print $3}'
		;;
        *)
		echo "0"
		;;
	esac
}

function get_stream_name
{
	streamid="$1"

	echo "stream_`get_quality $streamid VRATE`_`get_quality $streamid ARATE`_`get_quality $streamid XY`"
}

STREAM="$1"
HTTP_PATH="$5ram/sessions/"
SEGDUR=10		# Length of Segments produced (between 10 and 30)
SEGWIN=$6		# Amount of Segments to produce
FFPATH=$7
SEGMENTERPATH=$8
SESSION=${9}
FFMPEGLOG=${10}
DIR=${11}

CURDIR=`pwd`

if [ $# -eq 0 ]
then
echo "Format is : ./istream_adaptive.sh source video_rate audio_rate audio_channels 480x320 httppath segments_number ffmpeg_path segmenter_path rec_files"
exit 1
fi

# Log
if [ -z "$FFMPEGLOG" ]
then
	FFMPEGLOG="/dev/null"
fi

#############################################################
# start dumping the TS via Streamdev into a pipe for ffmpeg
# and store baseline 3.0 mpegts to outputfile
# sending it to the segmenter via a PIPE
##############################################################

# Check that the session dir exists
if [ ! -e ../ram/sessions/$SESSION ]
then
	exit;
fi

# Go into session and create fifos
cd ../ram/sessions/$SESSION
for fifoid in `seq 1 $NBQUALITIES`
do
	mkfifo ./fifo${fifoid}
done

#create master playlist
echo "#EXTM3U" > stream.m3u8
for streamid in `seq 1 $NBQUALITIES`
do
	echo "#EXT-X-STREAM-INF:PROGRAM-ID=1,BANDWIDTH=`get_quality $streamid BW`" >> stream.m3u8
	echo "`get_stream_name $streamid`".m3u8 >> stream.m3u8
done

COMMON_OPTION="-filter:v yadif -f mpegts -async 2 -threads 0 "
AUDIO_OPTION="-acodec libaacplus -ac 2 -b:a "
VIDEO_OPTION="-vcodec libx264 -flags +loop+mv4 -cmp 256 -partitions +parti4x4+parti8x8+partp4x4+partp8x8+partb8x8 -me_method hex -subq 7 -trellis 1 -refs 5 -coder 0 -me_range 16 -i_qfactor 0.71 -rc_eq 'blurCplx^(1-qComp)' -qcomp 0.6 -qmin 10 -qmax 51 -qdiff 4 -level 30 -sc_threshold 0 "

# Start ffmpeg
echo start > $FFMPEGLOG
FFMPEG_QUALITIES=""
for ffid in `seq 1 $NBQUALITIES`
do

	FFMPEG_QUALITIES="${FFMPEG_QUALITIES} $COMMON_OPTION  $AUDIO_OPTION `get_quality $ffid ARATE` -s `get_quality $ffid XY` $VIDEO_OPTION \
				-keyint_min 25 -r 25 -g 250 -b:v `get_quality $ffid VRATE` -bt `get_quality $ffid VRATE` -maxrate `get_quality $ffid VRATE` \
				-bufsize `get_quality $ffid VRATE` ./fifo${ffid}"

done

if [ ! -z "$DIR" ]
then
	$CURDIR/cat_recording.sh $DIR | $FFPATH -i - -y $FFMPEG_QUALITIES 2>$FFMPEGLOG &
else
        wget "$STREAM" -O - | $FFPATH -i - -y $FFMPEG_QUALITIES 2>$FFMPEGLOG &
fi

sleep 1

# Store ffmpeg pid
FFPID=$!
echo $FFPID > /tmp/aaa
\ps ax --format "%p %c %P" >> /tmp/aaa
if [ ! -z "$FFPID" ]
then
	SPID=`\ps ax --format "%p %c %P" | grep "^$FFPID" | grep ffmpeg | awk {'print $1'}`;
	echo $SPID >> /tmp/aaa
	if [ ! -z "$SPID" ]
	then
		echo $SPID > ./ffmpeg.pid
	fi
fi
echo "TOTO" >> /tmp/aaa

# Start Segmenters and store pids
> ./segmenter.pid
for segid in `seq 1 $NBQUALITIES`
do
	# Now start segmenter1
	$SEGMENTERPATH ./fifo${segid} $SEGDUR `get_stream_name $segid` `get_stream_name $segid`.m3u8 "" $SEGWIN &

	sleep 0.5

	# Store segmenters pid
	SEGPID=$!
	if [ ! -z "$SEGPID" ]
	then
		SPID=`\ps ax --format "%p %c %P" | grep "^$SEGPID" | grep segmenter | awk {'print $1'}`;
		if [ ! -z "$SPID" ]
		then
			echo $SPID >> ./segmenter.pid
		fi
	fi
done
