#!/bin/sh
info=`/usr/local/bin/mediainfo $1 2>&1`
if [[ $? == 0 ]]; then
  duration=`echo "$info" | grep -i Duration | head -n1 | cut -d: -f2`
  framerate=`echo "$info" | grep -i "Frame rate" | head -n1 | cut -d: -f2`
  echo "Duration: $duration"
  echo "Framerate: $framerate"
else
  echo "ERROR in mediainfo: $?"
  /usr/local/bin/mediainfo $1
  echo $info
fi
