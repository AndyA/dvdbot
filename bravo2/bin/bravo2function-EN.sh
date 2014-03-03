#!/bin/sh

# knihovna funkci pro ovladani zarizeni Bravo II
# library of function for Bravo II
# version 0.1.1 David Fabel (c) 2007


. /srv/bravo2/bin/scriptFunction-EN

# functions and symbolic notation
# 05	        R		
# 80		^¨=+
# 81		^¨=
# 82		^¨¨_
# 83		^¨-
# 84		+=´¨^	^´¨=+
# 85		_´¨¨^	^´¨¨_
# 86		-=
# 87		-¨_
# 88		_´¨-	-´¨_
# 89		-_
# 8a		=¨_
# 8b		_´¨=	=´¨-
# 8c		=_
# 8d		°~~
# 8e		~=~
# 8f		~-~
# 90		~~°
# 91		~=~+
# 92		^		v
# 93		=e
# 94		=c
# 95		w´¨		´¨w
# 96		¨w
# 97		¨¨w
# 98		=-
# 99		?´¨¨?
# 9a		^~~
# 9b		°~~
# 9c		x
# 
# R		reset
# ^		Naber (pokud se vyskytuje cteme ho vzdy jako prvni a je tim urcen smer pohybu robota)
# v		Upust (jedna se o stejny prikaz jako naber!)
# ¨		Prejed s CD vpravo
# ¨¨	Prejed 2x s CD vpravo
# ´¨	Prejed s CD vlevo
# ´¨¨	Prejed 2x s CD vlevo
# ~		Prejed bez CD
# °		Zaparkuj
# 
# =		Tiskarna
# -		CD/DVD
# _		Pozice (left, front, right)
# 
# w		Cartridge
# ?		Osahej
# x		Vypni
# e		Vysun
# c		Zasun
# 
# +		neprozkoumana funkce

lpDdevice="/dev/usb/lp0"
cdDevice="/dev/sr1"
pictureMaxX="2800"
pictureMaxY="2800"
bravoDir="/srv/bravo2/bin"
sourceMedia="/srv/bravo2/bravoSourceMedia"

#check:
function check()
 {
  if ! which $1 > /dev/null; then 
    logMsg 0 "ERROR! command not found $1"
    exit
  fi
 }

function waitOnDevice()
 {
  logMsg 5 "started waitOnDevice"
  device=""
  while [ -z "device" ]; do
    device=$(ls $lpDdevice 2>/dev/null)
    logMsg 4 "Wait for device '$lpDdevice'"
    sleep .5
  done
  logMsg 5 "ended waitOnDevice"
 }

function setStatusBurn()
 {
  logMsg 5 "started setStatusBurn"
    status=""
    if [ -e "/tmp/bravoStatus" ]; then
      status="$(cat < /tmp/bravoStatus |
               grep -v "^burn:")\n"
    fi
    if [ -n "$1" ] && [ "$1" != "?" ]; then
      burnStatus="$1"
    else
      burnStatus="?"
    fi
    logMsg 3 "Status burn drive set: '$burnStatus'"
    echo -e "${status}burn:$burnStatus # help of status burn drive: I (closed), O (opened), D (with media), B (burned), E (eror), ? (unknown)" > /tmp/bravoStatus
  logMsg 5 "ended setStatusBurn"
 }

function getDataBurn()
 {
# examples of status burn drive

# closed and empty
#cdrom-test /dev/sr0
# Drive status: This Should not happen!
# Empty slot.     Unknown disc type 0x1!  Changed.
# Not a DVD: No medium found
# RMI is: 0xb7

# open
#cdrom-test /dev/sr0
# Drive status: Tray Open.
# CD-ROM tray open.
# Unknown disc type 0x1!  Changed.
# Not a DVD: No medium found
# MI is: 0xb7

# closed with CD
#cdrom-test /dev/sr0
# Drive status: Ready.
# Disc present.   Data disc type 1.       Changed.
# Not a DVD: Input/output error
# RMI is: 0xb7

# closed with DVD
#cdrom-test /dev/sr0
# Drive status: Ready.
# Disc present.   Data disc type 1.       Changed.
# DVD not encrypted
# RMI is: 00

# closed with new empty CD
#cdrom-test /dev/sr0
# Drive status: Ready.
# Disc present.   Unknown disc type 0x0!  Changed.
# Not a DVD: Input/output error
# RMI is: 0xb7

  logMsg 5 "started getDataBurn"
  if [ -f /tmp/bravoStatus ]; then
    status=$(cat < /tmp/bravoStatus |
             grep "burn:" |
             sed "s|[^:]*:\(.\).*|\1|")
  fi
  logMsg 3 "Burn drive have status: '$status'"

  if [ "$status" != "B" ]; then
    data=$($bravoDir/cdrom-test $cdDevice 2>/dev/null)
    status=$(echo -e "$data" |
             head -n2 |
             tail -n1 |
             sed "s|\..*|.|")
    logMsg 4 "Data from device '$cdDevice': '$data' and status: '$status'"
    case  "$status" in
      "Empty slot.")
        setStatusBurn I;;

      "CD-ROM tray open.")
        setStatusBurn O;;
        
      "Disc present.")
        setStatusBurn D;;      
    esac
   fi
  logMsg 5 "ended getDataBurn"
 }

function setStatus()
 {
  logMsg 5 "started setStatus s parametrem '$1'"
  robotStatus=$(echo -ne "$1" |
                grep -a "^00000040" |
                sed -e "s/.*|[0-9][0-9][0-9]...//" \
                    -e "s|\(...\).*|\1|" \
                    -e "s|\(.\)|\1~|g")
  
  robotStatusExport=$(echo -e "$robotStatus" |
                      tr "~" "\n" |
                      grep -v "^$" |
                      grep -n "" |
                      sed -e "s|1:\(.\)|bravo:\1 # help of status device Bravo: I (no action), B (in action), C (door open)|" \
                          -e "s|2:\(.\)|printer:\1 # help status printer: I (closed), O (ejected), D (with media),  X (opened empty?)|" \
                          -e "s|3:\(.\)|robot:\1 # help of status robot: X (empty), O (with media)|")
  logMsg 3 "Geting status: '$robotStatus'"
  if [ -e "/tmp/bravoStatus" ]; then
    burnStatus="\n$(grep "^burn:" /tmp/bravoStatus)"
  fi
  echo -e "$robotStatusExport$burnStatus" > /tmp/bravoStatus

  robotError=$(echo -ne "$1" |
               grep -a "^00000040" |
               sed -e "s|00000040  .. .. .. .. .. ||" \
                   -e "s|\(..\).*|\1|")
  logMsg 3 "Geting error: '$robotError'"
  echo -e "$robotError
# help of error: 00 OK
#                04 ?
#                24 No disk on source" > /tmp/bravoError
  logMsg 5 "ended setStatus"
 }

function getStatus()
 {
  logMsg 5 "started getStatus($1)"
  if [ -z "$1" ]; then
    logMsg 0 "Argument of device not set!"
    exit 1
  fi
  if [ "$1" = "printer" ]; then
    getData
  fi
  if [ "$1" = "burn" ]; then
    getDataBurn
  fi
  status=$(cat < /tmp/bravoStatus |
           grep "$1:" |
           sed "s|[^:]*:\(.\).*|\1|"|
           tr -d "[:cntrl:]")
  logMsg 3 "Geting status of device '$1' is: '$status'"
  if [ -z "$status" ]; then
    if [ "$1" = "burn" ]; then
      logMsg 2 "Have not status of device '$1'"
      status="?"
    else
      logMsg 0 "Have not status of device '$1'"
      exit 1
    fi
  fi
  echo -n "$status"
  logMsg 5 "ended getStatus"
 }

function getError()
 {
  logMsg 5 "started getError"
  error=$(cat < /tmp/bravoError |
          head -n1 |
          tr -d "\n")
  logMsg 3 "Getting status of device: '$error'"
  if [ -z "$error" ]; then
    logMsg 0 "Have not status of device"
    exit 1
  fi
  echo -n "$error"
  logMsg 5 "ended getError"
 }

function getBurnError()
 {
  logMsg 5 "started getBurnError"
  error=$(cat < /tmp/burnError |
          head -n1 |
          tr -d "\n")
  logMsg 3 "Beting status of burn drive: '$error'"
  if [ -z "$error" ]; then
    logMsg 0 "Have not status of burn device"
    exit 1
  fi
  echo -n "$error"
  logMsg 5 "ended getBurnError"
 }

function getData()
 {
  logMsg 5 "started getData"
  data=""
  while [ -z "$data" ]; do 
    waitOnDevice
    logMsg 4 "Ctu z '$lpDdevice'"
    data=$(head -c89 $lpDdevice 2>/dev/null |
           hexdump -C)
  done
  setStatus "$data"
  logMsg 4 "Data from device '$lpDdevice': '$(echo -e "$data"|sed "s/|.*//")'"
  logMsg 5 "ended getData"
 }

function setData()
 {
  logMsg 5 "started setData s parametrem '$(echo -e "$1"|hexdump -C|sed "s/|.*//")'"
  waitOnDevice
#  echo -e "$1 $lpDdevice"
#  while ! echo -ne "$1"  2>/dev/null > $lpDdevice; do
  while ! echo -ne "$1" | /srv/bravo2/bin/setdata $lpDdevice; do
    waitOnDevice
    logMsg 2 "Sending data to '$lpDdevice'"
  done
#echo -ne "$1"|./setdata $lpDdevice
  param=$(echo -n "$1"|
          sed "s/\\\\/\\\\\\\\/g")
  logMsg 2 "Sended to '$lpDdevice': 'echo -ne \"$param\"| /srv/bravo2/bin/setdata $lpDdevice'"
  sleep 1
  logMsg 5 "ended setData"
 }

function waitOnStart()
 {
  logMsg 5 "started waitOnStart"
  status=""
  while [ "$status" != "B" ]; do
    getData
    status=$(getStatus bravo)
    logMsg 3 "Ziskany status zarizeni '$status'"
    sleep .5
  done
  logMsg 2 "Start run of command"
  logMsg 5 "ended waitOnStart"
 }

function waitOnEndOK()
 {
  logMsg 5 "started waitOnEndOK"
  status=""
  error=""
  while [ "$status" != "I" ] || [ "$error" != "00" ]; do
    getData
    status=$(getStatus bravo)
    error=$(getError)
    logMsg 3 "Geting status '$status' and return code '$error'"
    sleep .5
  done
  logMsg 2 "Ended run of commnad"
  logMsg 5 "ended waitOnEndOK"
 }

function getXofPicture()
 {
  logMsg 5 "started getXofPicture"
  x=$(identify "$bravoWorkDir/$1" |
      sed "s|.*PNM \([0-9]\+\)x.*|\1|")
  logMsg 3 "X of pictures '$bravoWorkDir/$1' is: '$x'"
  echo $x
  logMsg 5 "ended getXofPicture"
 }

function getYofPicture()
 {
  logMsg 5 "started getYofPicture"
  y=$(identify "$bravoWorkDir/$1" |
      sed "s|.*x\([0-9]\+\).*|\1|")
  logMsg 3 "Y of pictures '$bravoWorkDir/$1' is: '$y'"
  echo $y
  logMsg 5 "ended getYofPicture"
 }


function resizePicture()
 {
  logMsg 5 "started resizePicture"
  x=$(getXofPicture "$1")
  y=$(getYofPicture "$1")
  logMsg 2 "Resolution of pictures '$bravoWorkDir/$1': x='$x' y='$y'"
  resize=""
  if [ "$x" != "$2" ]; then
    x="$2"
    resize="true"
  fi
  if [ "$y" != "$3" ]; then
    y="$3"
    resize="true"
  fi
  if [ -n "$resize" ]; then
    logMsg 2 "Run: convert '$bravoWorkDir/$1' -resize ${x}x$y -depth 8 '$bravoTmpDir/$$.png' && pngtopnm '$bravoTmpDir/$$.png' > '$bravoWorkDir/$1' && rm -f '$bravoTmpDir/$$.png'"
    convert "$bravoWorkDir/$1" -resize ${x}x$y -depth 8 "$bravoTmpDir/$$.png" && pngtopnm "$bravoTmpDir/$$.png" > "$bravoWorkDir/$1" && rm -f "$bravoTmpDir/$$.png"
  fi
  x=$(getXofPicture "$1")
  y=$(getYofPicture "$1")
  if [ "$x" != "$2" ] || [ "$y" != "$3" ]; then
    logMsg 1 "Picture '$bravoWorkDir/$1' is on wron resolution: '${x}x$y'"
  fi
  logMsg 5 "ended resizePicture"
 }

function identifyPicture()
 {
  logMsg 5 "started identifyPicture"
  case  "$(file -b "$bravoWorkDir/$1")" in
    "Netpbm PPM \"rawbits\" image data")
      resizePicture "$1" "$2" "$3";;
    "Netpbm PGM \"rawbits\" image data")
      resizePicture "$1" "$2" "$3";;
    *)
      logMsg 0 "Picture '$bravoWorkDir/$1' is in wrong format: '$(file "$bravoWorkDir/$1")'";;
  esac
  logMsg 5 "ended identifyPicture"
 }

function prepareQuality
 {
  logMsg 5 "started prepareQuality"
  if [ -n "$2" ]; then 
    quality="q$2"
  else
    quality=$(echo -e "$1" |
              sed "s|.*q\([0-9]\+\).*|\1|")
    if [ -n "$quality" ]; then 
      quality="-q$quality"
    else 
      quality="-q0"
    fi
  fi
  echo "$quality"
  logMsg 5 "ended prepareQuality"
 }

function prepareMode
 {
  logMsg 5 "started prepareMode"
  if [ -n "$1" ]; then mode="-m$1"; else mode=""; fi
  echo "$mode"
  logMsg 5 "ended prepareMode"
 }

function preparePictureSizeX
 {
  logMsg 5 "started preparePictureSizeX"
  if [ "$1" = "-q0" ]; then
    pictureSizeX=$(($pictureMaxX / 2))
  else
    pictureSizeX=$pictureMaxX
  fi   
  echo "$pictureSizeX"
  logMsg 5 "ended preparePictureSizeX"
 }

function preparePictureSizeY
 {
  logMsg 5 "started preparePictureSizeY"
  if [ "$1" = "-q0" ]; then
    pictureSizeY=$(($pictureMaxY / 2))
  else
    pictureSizeY=$pictureMaxY
  fi   
  echo "$pictureSizeY"
  logMsg 5 "ended preparePictureSizeY"
 }

function bravoPrint()
 {
  logMsg 5 "started print"
  if [ -e "$bravoWorkDir/$1" ]; then
    quality=$(prepareQuality "$1" "$2")
    mode=$(prepareMode "$3")
    pictureSizeX=$(preparePictureSizeX "$quality")
    pictureSizeY=$(preparePictureSizeY "$quality")
    logMsg 2 "Resolution of '$bravoWorkDir/$1' for quality: '$quality' set on: '$pictureSizeX' x '$pictureSizeY'"

    identifyPicture "$1" $pictureSizeX $pictureSizeY
    x=$(getXofPicture "$1")
    y=$(getXofPicture "$1")
    vMargin=$(( (($pictureSizeX-$x) / 2) +5 ))
    hMargin=$(( (($pictureSizeY-$y) / 2) -1 ))
    fileSize=$(wc -c  "$bravoWorkDir/$1" |
               sed "s| .*||")

    logMsg 2 "Start print '$bravoWorkDir/$1' file size '$fileSize' in quality: '$quality' and mod: '$mode' '$bravoDir/bravo2 -i '$bravoWorkDir/$1' -f$bravoDir -o$lpDdevice -I200 -V$vMargin -H$hMargin $quality $mode'"
    logMsg 2 "$bravoDir/bravo2 -i \"$bravoWorkDir/$1\" -f$bravoDir -o$lpDdevice -I200 -V$vMargin -H$hMargin $quality $mode"
    logMsg 2 $($bravoDir/bravo2 -i "$bravoWorkDir/$1" -f$bravoDir -o$lpDdevice -I200 -V$vMargin -H$hMargin $quality $mode)
  else
    logMsg 1 "Found file for printing: '$bravoWorkDir/$1'"
    exit
  fi
  sleep .5
  logMsg 5 "ended print"
 }

 function bravoBurn()
 {
  logMsg 5 "started burn"
  logMsg 3 "File for burn: '$bravoWorkDir/$1'"
  if [ -e "$bravoWorkDir/$1" ]; then
   (
    logMsg 3 "Start burning: '$bravoWorkDir/$1'"
    setStatusBurn "B"

    if ! cdrecord -tao -d dev=$cdDevice "$bravoWorkDir/$1" >&/tmp/$$_burn.log; then
      logMsg 1 "Burning have problem: '$(cat < /tmp/$$_burn.log)'"
      setStatusBurn "E"
      echo -e "02
# help of errors: 00 OK
#                 01 No data for burn
#                 02 Burn error
#                 03 Verify error" > /tmp/burnError
    else
      if [ "$2" = "verify" ]; then
        logMsg 3 "Start test: '$bravoWorkDir/$1'"
        if ! [ -d "/mnt/cdrom1" ]; then
          mkdir -p /mnt/cdrom1
        fi
        ( cd /mnt
          logMsg 2 "Mount burned CD '$1' ($2)"
          umount $cdDevice
          mount -t iso9660 $cdDevice /mnt/cdrom1 -oro,iocharset=cp852
          sleep 1
          cd /mnt/cdrom1
          ls -R >> /tmp/$$_cdrom.ls &&\
            cd .. &&\
            umount /mnt/cdrom1
          sleep 2

          mount "$bravoWorkDir/$1" /mnt/cdrom1 -oro,loop,iocharset=cp852
          cd /mnt/cdrom1
          ls -R >> /tmp/$$_iso.ls &&\
            cd .. &&\
            umount /mnt/cdrom1
        )
          if ! diff /tmp/$$_cdrom.ls /tmp/$$_iso.ls; then
            logMsg 1 "ERROR! CD on identical with source data: '$(diff /tmp/$$_cdrom.ls /tmp/$$_iso.ls)' "
            errorISO=$(echo "$1"|
                       sed "s|\.iso\.\([^_]*\)_.*|.iso.\\1_1x_q1|")
            mv "$bravoWorkDir/$1" "$bravoWorkDir/$errorISO"
            ln "$bravoDir/ERROR.pnm" "$bravoWorkDir/$errorPNM"
            setStatusBurn "E"
           echo -e "03
# help of errors: 00 OK
#                 01 No data for burn
#                 02 Burn error
#                 03 Verify error" > /tmp/burnError
          fi
          umount /mnt/cdrom1 2>/dev/null

      fi
    sleep .5
    logMsg 2 "Set status of burn drive"
    setStatusBurn "?"
    getDataBurn

    fi
   ) &
  else
    logMsg 1 "Data for burn not found"
    setStatusBurn "E"
    echo -e "01
# help of errors: 00 OK
#                 01 No data for burn
#                 02 Burn error
#                 03 Verify error" > /tmp/burnError
  fi
  logMsg 5 "ended burn"
 }

function bravo()
 {
  logMsg 5 "started bravo: '$@'"
  cmd="$@" 
  while [ -n "$1" ]; do
    wait=true
    logMsg 2 "Sending command: '$1'"
    echo -en "\007"

    case $1 in
          {5} | {05} | {R})
         setData "\033\004\005\0\0\0\0\044";;

	  {80} | {^¨=+})
         setData "\033\004\0200\0\0\0\0\0237";;

	  {81} | {^¨=})
         setData "\033\004\0201\0\0\0\0\0240";;

	  {82} | {^¨¨_})
         setData "\033\004\0202\0\0\0\0\0241";;

	  {83} | {-´¨^} | {-¨^}  | {^´¨-})
         setData "\033\004\0203\0\0\0\0\0242";;

	  {84} | {+=´¨^} | {+=¨^} | {^´¨=+})
         setData "\033\004\0204\0\0\0\0\0243";;

	  {85} | {_´¨¨^} | {_¨¨^} | {^´¨¨_})
         setData "\033\004\0205\0\0\0\0\0244";;

	  {86} | {-=})
         setData "\033\004\0206\0\0\0\0\0245";;

	  {87} | {-¨_})
         setData "\033\004\0207\0\0\0\0\0246";;

	  {88} | {_´¨-} | {_¨-} | {-´¨_})
         setData "\033\004\0210\0\0\0\0\0247";;

	  {89} | {-_})
         setData "\033\004\0211\0\0\0\0\0250";;

	  {8a} | {=¨_})
         setData "\033\004\0212\0\0\0\0\0251";;

	  {8b} | {_´¨=} | {_¨=} | {=´¨-})
         setData "\033\004\0213\0\0\0\0\0252";;

	  {8c} | {=_})
         setData "\033\004\0214\0\0\0\0\0253";;

	  {8d} | {°~~})
         setData "\033\004\0215\0\0\0\0\0254";;

	  {8e} | {~=~})
         setData "\033\004\0216\0\0\0\0\0255";;

	  {8f} | {~-~})
         setData "\033\004\0217\0\0\0\0\0256";;

	  {90} | {~~°})
         setData "\033\004\0220\0\0\0\0\0257";;

	  {91} | {~=~+})
         setData "\033\004\0221\0\0\0\0\0260";;

	  {92} | {^} | {v} )
         setData "\033\004\0222\0\0\0\0\0261";;

	  {93} | {=e} | {=o})
         setData "\033\004\0223\0\0\0\0\0262";;

	  {94} | {=c})
         setData "\033\004\0224\0\0\0\0\0263";;

	  {95} | {w´¨} | {´¨w})
         setData "\033\004\0225\0\0\0\0\0264";;

	  {96} | {¨w})
         setData "\033\004\0226\0\0\0\0\0265";;

	  {97} | {¨¨w})
         setData "\033\004\0227\0\0\0\0\0266";;

	  {98} | {=-})
         setData "\033\004\0230\0\0\0\0\0267";;

	  {99} | {?´¨¨?})
         setData "\033\004\0231\0\0\0\0\0270";;

	  {9a} | {^~~})
         setData "\033\004\0232\0\0\0\0\0271";;

	  {9b} | {°~~})
         setData "\033\004\0233\0\0\0\0\0272";;

	  {9c} | {x} | {off})
         setData "\033\004\0234\0\0\0\0\0273";;

	  {-e} | -o)
         wait=false; eject $cdDevice 2>/dev/null; getDataBurn;;

	  {-c})
         wait=false; eject -T $cdDevice 2>/dev/null; getDataBurn;;

	  *)
         logMsg 0 "Unknown commnad '$1' in string '$cmd'" > /dev/stderr
         exit 1;;
    esac

    if $wait; then
      waitOnStart
      waitOnEndOK
    fi
    sleep .5
    shift
  done 
  logMsg 5 "ended bravo"
 }
