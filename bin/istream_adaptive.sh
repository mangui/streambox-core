#!/bin/bash

STREAM=$1

VRATE1=110k
ARATE1=48k
BW1=180000
XY1=320x240

VRATE2=200k
ARATE2=48k
BW2=280000
XY2=320x240

VRATE3=300k
ARATE3=48k
BW3=400000
XY3=320x240

VRATE4=400k
ARATE4=48k
BW4=510000
XY4=480x320

VRATE5=600k
ARATE5=48k
BW5=740000
XY5=640x480

HTTP_PATH="$5ram/"

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
if [ ! -e ../ram/$SESSION ]
then
	exit;
fi

cd ../ram/$SESSION
mkfifo ./fifo1 ./fifo2 ./fifo3 ./fifo4 ./fifo5

if [ ! -z "$DIR" ]
then
	FFMPEGPREFIX="$CURDIR/cat_recording.sh $DIR"
else
#	FFMPEGPREFIX="wget -d --dot-style=mega -o "$WGETLOG" "$STREAM" -O -"
	FFMPEGPREFIX="cat /dev/null"
fi

STREAM1=stream_"$VRATE1"_"$ARATE1"_$XY1
STREAM2=stream_"$VRATE2"_"$ARATE2"_$XY2
STREAM3=stream_"$VRATE3"_"$ARATE3"_$XY3
STREAM4=stream_"$VRATE4"_"$ARATE4"_$XY4
STREAM5=stream_"$VRATE5"_"$ARATE5"_$XY5

#create master playlist
cat > stream.m3u8 << EOF
#EXTM3U
#EXT-X-STREAM-INF:PROGRAM-ID=1,BANDWIDTH=$BW1
$STREAM1.m3u8
#EXT-X-STREAM-INF:PROGRAM-ID=1,BANDWIDTH=$BW2
$STREAM2.m3u8
#EXT-X-STREAM-INF:PROGRAM-ID=1,BANDWIDTH=$BW3
$STREAM3.m3u8
#EXT-X-STREAM-INF:PROGRAM-ID=1,BANDWIDTH=$BW4
$STREAM4.m3u8
#EXT-X-STREAM-INF:PROGRAM-ID=1,BANDWIDTH=$BW5
$STREAM5.m3u8
EOF



COMMON_OPTION="-filter:v yadif -f mpegts -async 2 -threads 0 "
AUDIO_OPTION="-acodec libaacplus -ac 2 -b:a "
VIDEO_OPTION="-vcodec libx264 -flags +loop+mv4 -cmp 256 -partitions +parti4x4+parti8x8+partp4x4+partp8x8+partb8x8 -me_method hex -subq 7 -trellis 1 -refs 5 -coder 0 -me_range 16 -i_qfactor 0.71 -rc_eq 'blurCplx^(1-qComp)' -qcomp 0.6 -qmin 10 -qmax 51 -qdiff 4 -level 30 -sc_threshold 0 "
# Start ffmpeg
echo start > $FFMPEGLOG
(trap "rm -f ./ffmpeg.pid; rm -f ./fifo*" EXIT HUP INT TERM ABRT; \
 $FFMPEGPREFIX | $FFPATH -i "$STREAM" -y \
 $COMMON_OPTION  $AUDIO_OPTION $ARATE1 -s $XY1 $VIDEO_OPTION -keyint_min 25 -r 25 -g 250 -b:v $VRATE1 -bt $VRATE1 -maxrate $VRATE1 -bufsize $VRATE1 ./fifo1 \
 $COMMON_OPTION  $AUDIO_OPTION $ARATE2 -s $XY2 $VIDEO_OPTION -keyint_min 25 -r 25 -g 250 -b:v $VRATE2 -bt $VRATE2 -maxrate $VRATE2 -bufsize $VRATE2 ./fifo2 \
 $COMMON_OPTION  $AUDIO_OPTION $ARATE3 -s $XY3 $VIDEO_OPTION -keyint_min 25 -r 25 -g 250 -b:v $VRATE3 -bt $VRATE3 -maxrate $VRATE3 -bufsize $VRATE3 ./fifo3 \
 $COMMON_OPTION  $AUDIO_OPTION $ARATE4 -s $XY4 $VIDEO_OPTION -keyint_min 25 -r 25 -g 250 -b:v $VRATE4 -bt $VRATE4 -maxrate $VRATE4 -bufsize $VRATE4 ./fifo4 \
 $COMMON_OPTION  $AUDIO_OPTION $ARATE5 -s $XY5 $VIDEO_OPTION -keyint_min 25 -r 25 -g 250 -b:v $VRATE5 -bt $VRATE5 -maxrate $VRATE5 -bufsize $VRATE5 ./fifo5 \
 2>$FFMPEGLOG) &

sleep 0.5

# Store ffmpeg pid
FFPID=$!
if [ ! -z "$FFPID" ]
then
	SPID=`\ps ax --format "%p %c %P" | grep "$FFPID$" | awk {'print $1'}`;
	if [ ! -z "$SPID" ]
	then
		echo $SPID > ./ffmpeg.pid
	fi
fi

# Now start segmenter1
(trap "rm -f ./segmenter.pid; cat ./fifo1" EXIT HUP INT TERM ABRT; $SEGMENTERPATH ./fifo1 $SEGDUR $STREAM1 $STREAM1.m3u8 "" $SEGWIN) &

sleep 0.5

# Store segmenter1 pid
SEGPID=$!
if [ ! -z "$SEGPID" ]
then
	SPID=`\ps ax --format "%p %c %P" | grep "$SEGPID$" | grep segmenter | awk {'print $1'}`;
	if [ ! -z "$SPID" ]
	then
		echo $SPID > ./segmenter.pid
	fi
fi

# Now start segmenter2
(trap "rm -f ./segmenter2.pid; cat ./fifo2" EXIT HUP INT TERM ABRT; $SEGMENTERPATH ./fifo2 $SEGDUR $STREAM2 $STREAM2.m3u8 "" $SEGWIN) &

sleep 0.5

# Store segmenter2 pid
SEGPID=$!
if [ ! -z "$SEGPID" ]
then
	SPID=`\ps ax --format "%p %c %P" | grep "$SEGPID$" | grep segmenter | awk {'print $1'}`;
	if [ ! -z "$SPID" ]
	then
		echo $SPID > ./segmenter2.pid
	fi
fi

# Now start segmenter3
(trap "rm -f ./segmenter3.pid; cat ./fifo3" EXIT HUP INT TERM ABRT; $SEGMENTERPATH ./fifo3 $SEGDUR $STREAM3 $STREAM3.m3u8 "" $SEGWIN) &

sleep 0.5
# Store segmenter3 pid
SEGPID=$!
if [ ! -z "$SEGPID" ]
then
	SPID=`\ps ax --format "%p %c %P" | grep "$SEGPID$" | grep segmenter | awk {'print $1'}`;
	if [ ! -z "$SPID" ]
	then
		echo $SPID > ./segmenter3.pid
	fi
fi

# Now start segmenter4
(trap "rm -f ./segmenter4.pid; cat ./fifo4" EXIT HUP INT TERM ABRT; $SEGMENTERPATH ./fifo4 $SEGDUR $STREAM4 $STREAM4.m3u8 "" $SEGWIN) &

sleep 0.5
# Store segmenter4 pid
SEGPID=$!
if [ ! -z "$SEGPID" ]
then
	SPID=`\ps ax --format "%p %c %P" | grep "$SEGPID$" | grep segmenter | awk {'print $1'}`;
	if [ ! -z "$SPID" ]
	then
		echo $SPID > ./segmenter4.pid
	fi
fi

# Now start segmenter5
(trap "rm -f ./segmenter5.pid; cat ./fifo5" EXIT HUP INT TERM ABRT; $SEGMENTERPATH ./fifo5 $SEGDUR $STREAM5 $STREAM5.m3u8 "" $SEGWIN) &

sleep 0.5
# Store segmenter5 pid
SEGPID=$!
if [ ! -z "$SEGPID" ]
then
	SPID=`\ps ax --format "%p %c %P" | grep "$SEGPID$" | grep segmenter | awk {'print $1'}`;
	if [ ! -z "$SPID" ]
	then
		echo $SPID > ./segmenter5.pid
	fi
fi
