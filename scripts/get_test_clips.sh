#!/bin/sh

elephant="
ed24p_00
ed24p_01
ed24p_02
ed24p_03
ed24p_04
ed24p_05
ed24p_06
ed24p_07
ed24p_08
ed24p_09
ed24p_10
ed24p_11
"
for e in $elephant; do
  if [[ ! -f videos/$e.ts ]]; then
    wget http://www.w6rz.net/$e.zip
    unzip $e.zip
    mv $e.ts videos/
  fi
  if [[ ! -f videos/$e.720p.ts ]]; then
    ffmpeg -i videos/$e.ts -s 1280x720 videos/$e.720p.ts
  fi
done
