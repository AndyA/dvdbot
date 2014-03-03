#!/bin/sh

# knihovna funkci pro ovladani zarizeni Bravo II
# library of function for Bravo II
# version 0.1.1 David Fabel (c) 2007

# changelog
# posilani octetu uz neni na 3 ale na 4 znaky \216 => \0216

. /srv/bravo2/bin/scriptFunction

# Sezam funkci a jejich zapis
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
#pictureMax="2700"
bravoDir="/srv/bravo2/bin"
sourceMedia="/srv/bravo2/bravoSourceMedia"

#check:
function check()
 {
  if ! which $1 > /dev/null; then 
    logMsg 0 "CHYBA! Nenalezen nastroj $1"
    exit
  fi
 }

function waitOnDevice()
 {
  logMsg 5 "startuje waitOnDevice"
  device=""
  while [ -z "device" ]; do
    device=$(ls $lpDdevice 2>/dev/null)
    logMsg 4 "Cekam na existenci '$lpDdevice'"
    sleep .5
  done
  logMsg 5 "konci waitOnDevice"
 }

function setStatusBurn()
 {
  logMsg 5 "startuje setStatusBurn"
#  if [ "$(getStatus burn)" != "B" ] || [ "$1" = "?" ]; then
    status=""
    if [ -e "/tmp/bravoStatus" ]; then
      status="$(cat < /tmp/bravoStatus |
               grep -v "^burn:")\n"
    fi
    if [ -n "$1" ] && [ "$1" != "?" ]; then
      burnStatus="$1"
    else
      burnStatus="?"
#    logMsg 2 "Nebyl zadan parametr pro status vypalovacky"
    fi
    logMsg 3 "Nastaven status vypalovacky na: '$burnStatus'"

    echo -e "${status}burn:$burnStatus # status vypalovacky zname: I (zavrena), O (otevrena), D (plna), B (pali), E (chyba), ? (neznamy)" > /tmp/bravoStatus
    logMsg 3 "Vypalovacka nastavena na: '$burnStatus'";
#  fi
  logMsg 5 "konci setStatusBurn"  
 }

function getDataBurn()
 {
# prazdna zavrena
#cdrom-test /dev/sr0
# Drive status: This Should not happen!
# Empty slot.     Unknown disc type 0x1!  Changed.
# Not a DVD: No medium found
# RMI is: 0xb7
#
# otevrena
#cdrom-test /dev/sr0
# Drive status: Tray Open.
# CD-ROM tray open.
# Unknown disc type 0x1!  Changed.
# Not a DVD: No medium found
# MI is: 0xb7
#
# s CD uvnitr
#cdrom-test /dev/sr0
# Drive status: Ready.
# Disc present.   Data disc type 1.       Changed.
# Not a DVD: Input/output error
# RMI is: 0xb7
#
# s DVD uvnitr
#cdrom-test /dev/sr0
# Drive status: Ready.
# Disc present.   Data disc type 1.       Changed.
# DVD not encrypted
# RMI is: 00
	
# s prazdne CD uvnitr
#cdrom-test /dev/sr0
# Drive status: Ready.
# Disc present.   Unknown disc type 0x0!  Changed.
# Not a DVD: Input/output error
# RMI is: 0xb7

  logMsg 5 "startuje getDataBurn"
  if [ -f /tmp/bravoStatus ]; then
    status=$(cat < /tmp/bravoStatus |
             grep "burn:" |
             sed "s|[^:]*:\(.\).*|\1|")
  fi
  logMsg 3 "Stavajici status vypalovacky je: '$status'"

  if [ "$status" != "B" ]; then
    data=$($bravoDir/cdrom-test $cdDevice 2>/dev/null)
    status=$(echo -e "$data" |
             head -n2 |
             tail -n1 |
             sed "s|\..*|.|")
    logMsg 4 "Nactena data z '$cdDevice': '$data' a status: '$status'"
    case  "$status" in
      "Empty slot.")
        setStatusBurn I;;

      "CD-ROM tray open.")
        setStatusBurn O;;
        
      "Disc present.")
        setStatusBurn D;;      
    esac
   fi
  logMsg 5 "konci getDataBurn"
 }

#function setStatusBurnMedia()
# {
#  # zavira dvirka!
#  logMsg 5 "startuje setStatusBurnMedia"
#  if [ "$(getStatus burn)" != "B" ]; then
#    waitOnDevice
#    if scsitape -f $cdDevice 2>&1 | grep "No medium found" > /dev/null; then 
#      setStatusBurn I
#    else
#      setStatusBurn D
#    fi
#  fi
#  logMsg 5 "konci setStatusBurnMedia"
# }

function setStatus()
 {
  logMsg 5 "startuje setStatus s parametrem '$1'"
  robotStatus=$(echo -ne "$1" |
                grep -a "^00000040" |
                sed -e "s/.*|[0-9][0-9][0-9]...//" \
                    -e "s|\(...\).*|\1|" \
                    -e "s|\(.\)|\1~|g")
  
  robotStatusExport=$(echo -e "$robotStatus" |
                      tr "~" "\n" |
                      grep -v "^$" |
                      grep -n "" |
                      sed -e "s|1:\(.\)|bravo:\1 # status prikazu zname: I (bez akce), B (v akci), C (otevreny dekl)|" \
                          -e "s|2:\(.\)|printer:\1 # status tiskarny zname: I (zavrena), O (otevrena), D (plna),  X (otevrena prazdna?)|" \
                          -e "s|3:\(.\)|robot:\1 # status ruky zname: X (prazdna), O (plna)|")
  logMsg 3 "Ziskany status je: '$robotStatus'"
  if [ -e "/tmp/bravoStatus" ]; then
    burnStatus="\n$(grep "^burn:" /tmp/bravoStatus)"
  fi
  echo -e "$robotStatusExport$burnStatus" > /tmp/bravoStatus

  robotError=$(echo -ne "$1" |
               grep -a "^00000040" |
               sed -e "s|00000040  .. .. .. .. .. ||" \
                   -e "s|\(..\).*|\1|")
  logMsg 3 "Ziskany error je: '$robotError'"
  echo -e "$robotError
# zname: 00 OK
#        04 ?
#        24 No disk on source" > /tmp/bravoError
  logMsg 5 "konci setStatus"
 }

function getStatus()
 {
  logMsg 5 "startuje getStatus($1)"
  if [ -z "$1" ]; then
    logMsg 0 "Je nutne zadat parametr pro status konkretniho zarizeni"
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
  logMsg 3 "Ziskany status zarizeni '$1' je: '$status'"
  if [ -z "$status" ]; then
    if [ "$1" = "burn" ]; then
      logMsg 2 "Nepodarilo se ziskat status zarizeni '$1'"
      status="?"
    else
      logMsg 0 "Nepodarilo se ziskat status zarizeni '$1'"
      exit 1
    fi
  fi
  echo -n "$status"
  logMsg 5 "konci getStatus"
 }

function getError()
 {
  logMsg 5 "startuje getError"
  error=$(cat < /tmp/bravoError |
          head -n1 |
          tr -d "\n")
  logMsg 3 "Ziskany error zarizeni je: '$error'"
  if [ -z "$error" ]; then
    logMsg 0 "Nepodarilo se ziskat error zarizeni"
    exit 1
  fi
  echo -n "$error"
  logMsg 5 "konci getError"
 }

function getBurnError()
 {
  logMsg 5 "startuje getBurnError"
  error=$(cat < /tmp/burnError |
          head -n1 |
          tr -d "\n")
  logMsg 3 "Ziskany error vypalovacky je: '$error'"
  if [ -z "$error" ]; then
    logMsg 0 "Nepodarilo se ziskat error vypalovacky"
    exit 1
  fi
  echo -n "$error"
  logMsg 5 "konci getBurnError"
 }

function getData()
 {
  logMsg 5 "startuje getData"
  data=""
  while [ -z "$data" ]; do 
    waitOnDevice
    logMsg 4 "Ctu z '$lpDdevice'"
    data=$(head -c89 $lpDdevice 2>/dev/null |
           hexdump -C)
  done
  setStatus "$data"
  logMsg 4 "Nactena data z '$lpDdevice': '$(echo -e "$data"|sed "s/|.*//")'"
  logMsg 5 "konci getData"
 }

function setData()
 {
  logMsg 5 "startuje setData s parametrem '$(echo -e "$1"|hexdump -C|sed "s/|.*//")'"
  waitOnDevice
#  echo -e "$1 $lpDdevice"
#  while ! echo -ne "$1"  2>/dev/null > $lpDdevice; do
  while ! echo -ne "$1" | /srv/bravo2/bin/setdata $lpDdevice; do
    waitOnDevice
    logMsg 2 "Pisu do '$lpDdevice'"
  done
#echo -ne "$1"|./setdata $lpDdevice
  param=$(echo -n "$1"|
          sed "s/\\\\/\\\\\\\\/g")
  logMsg 2 "Zapsano do '$lpDdevice': 'echo -ne \"$param\"| /srv/bravo2/bin/setdata $lpDdevice'"
  sleep 1
  logMsg 5 "konci setData"
 }

function waitOnStart()
 {
  logMsg 5 "startuje waitOnStart"
  status=""
  while [ "$status" != "B" ]; do
    getData
    status=$(getStatus bravo)
    logMsg 3 "Ziskany status zarizeni '$status'"
    sleep .5
  done
  logMsg 2 "Provadeni prikazu zahajeno"
  logMsg 5 "konci waitOnStart"
 }

function waitOnEndOK()
 {
  logMsg 5 "startuje waitOnEndOK"
  status=""
  error=""
  while [ "$status" != "I" ] || [ "$error" != "00" ]; do
    getData
    status=$(getStatus bravo)
    error=$(getError)
    logMsg 3 "Ziskany statut zarizeni '$status' a navratovy kod '$error'"
    sleep .5
  done
  logMsg 2 "Provadeni prikazu ukonceno"
  logMsg 5 "konci waitOnEndOK"
 }

function getXofPicture()
 {
  logMsg 5 "startuje getXofPicture"
  x=$(identify "$bravoWorkDir/$1" |
      sed "s|.*PNM \([0-9]\+\)x.*|\1|")
  logMsg 3 "X obrazku '$bravoWorkDir/$1' je: '$x'"
  echo $x
  logMsg 5 "konci getXofPicture"
 }

function getYofPicture()
 {
  logMsg 5 "startuje getYofPicture"
  y=$(identify "$bravoWorkDir/$1" |
      sed "s|.*x\([0-9]\+\).*|\1|")
  logMsg 3 "Y obrazku '$bravoWorkDir/$1' je: '$y'"
  echo $y
  logMsg 5 "konci getYofPicture"
 }


function resizePicture()
 {
  logMsg 5 "startuje resizePicture"
  x=$(getXofPicture "$1")
  y=$(getYofPicture "$1")
  logMsg 2 "Zjistena velikost souboru '$bravoWorkDir/$1': x='$x' y='$y'"
  resize=""
#  if [ "$x" -gt "$y" ]; then
#    y=$x
#    resize="true"
#  fi
#  if [ "$y" -gt "$x" ]; then
#    x=$y
#    resize="true"
#  fi
  if [ "$x" != "$2" ]; then
    x="$2"
    resize="true"
  fi
  if [ "$y" != "$3" ]; then
    y="$3"
    resize="true"
  fi
  if [ -n "$resize" ]; then
#    logMsg 2 "Konvertuji convert '$bravoWorkDir/$1' -resize ${x}x$y -extent ${x}x$y '$bravoTmpDir/$$.png' && pngtopnm '$bravoTmpDir/$$.png' > '$bravoWorkDir/$1' && rm -f '$bravoTmpDir/$$.png'"
    logMsg 2 "Konvertuji convert '$bravoWorkDir/$1' -resize ${x}x$y -depth 8 '$bravoTmpDir/$$.png' && pngtopnm '$bravoTmpDir/$$.png' > '$bravoWorkDir/$1' && rm -f '$bravoTmpDir/$$.png'"
#    mv "$bravoWorkDir/$1" "$bravoWorkDir/$$_$1" && convert "$bravoWorkDir/$$_$1" -resize ${x}x$y "$bravoWorkDir/$1"
#    convert "$bravoWorkDir/$1" -resize ${x}x$y "$bravoWorkDir/$$.pnm" && mv "$bravoWorkDir/$$.pnm" "$bravoWorkDir/$1"
#    convert "$bravoWorkDir/$1" -resize ${x}x$y -extent ${x}x$y "$bravoTmpDir/$$.png" && pngtopnm "$bravoTmpDir/$$.png" > "$bravoWorkDir/$1" && rm -f "$bravoTmpDir/$$.png"
    convert "$bravoWorkDir/$1" -resize ${x}x$y -depth 8 "$bravoTmpDir/$$.png" && pngtopnm "$bravoTmpDir/$$.png" > "$bravoWorkDir/$1" && rm -f "$bravoTmpDir/$$.png"
  fi
  x=$(getXofPicture "$1")
  y=$(getYofPicture "$1")
#  if [ "$x" != "$2" ] || [ "$y" != "$2" ] || [ "$x" != "$y" ]; then
  if [ "$x" != "$2" ] || [ "$y" != "$3" ]; then
    logMsg 1 "Obrazek '$bravoWorkDir/$1' je ve spatnem rozliseni: '${x}x$y'"
  fi
  logMsg 5 "konci resizePicture"
 }

function identifyPicture()
 {
  logMsg 5 "startuje identifyPicture"
  case  "$(file -b "$bravoWorkDir/$1")" in
    "Netpbm PPM \"rawbits\" image data")
      resizePicture "$1" "$2" "$3";;
    "Netpbm PGM \"rawbits\" image data")
      resizePicture "$1" "$2" "$3";;
    *)
      logMsg 0 "Obrazek '$bravoWorkDir/$1' je ve spatnem formatu: '$(file "$bravoWorkDir/$1")'";;
  esac
  logMsg 5 "konci identifyPicture"
 }

function prepareQuality
 {
  logMsg 5 "startuje prepareQuality"
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
  logMsg 5 "konci prepareQuality"
 }

function prepareMode
 {
  logMsg 5 "startuje prepareMode"
  if [ -n "$1" ]; then mode="-m$1"; else mode=""; fi
  echo "$mode"
  logMsg 5 "konci prepareMode"
 }

function preparePictureSizeX
 {
  logMsg 5 "startuje preparePictureSizeX"
  if [ "$1" = "-q0" ]; then
    pictureSizeX=$(($pictureMaxX / 2))
  else
    pictureSizeX=$pictureMaxX
  fi   
  echo "$pictureSizeX"
  logMsg 5 "konci preparePictureSizeX"
 }

function preparePictureSizeY
 {
  logMsg 5 "startuje preparePictureSizeY"
  if [ "$1" = "-q0" ]; then
    pictureSizeY=$(($pictureMaxY / 2))
  else
    pictureSizeY=$pictureMaxY
  fi   
  echo "$pictureSizeY"
  logMsg 5 "konci preparePictureSizeY"
 }

function bravoPrint()
 {
  logMsg 5 "startuje print"
  if [ -e "$bravoWorkDir/$1" ]; then
    quality=$(prepareQuality "$1" "$2")
    mode=$(prepareMode "$3")
    pictureSizeX=$(preparePictureSizeX "$quality")
    pictureSizeY=$(preparePictureSizeY "$quality")
    logMsg 2 "Velikost '$bravoWorkDir/$1' pro kvalitu: '$quality' stanovena na: '$pictureSizeX' x '$pictureSizeY'"

    identifyPicture "$1" $pictureSizeX $pictureSizeY
    x=$(getXofPicture "$1")
    y=$(getXofPicture "$1")
    vMargin=$(( (($pictureSizeX-$x) / 2) +5 ))
    hMargin=$(( (($pictureSizeY-$y) / 2) -1 ))
    fileSize=$(wc -c  "$bravoWorkDir/$1" |
               sed "s| .*||")
#    hMargin=$margin
#    vMargin=$margin
    logMsg 2 "Budeme tisknout '$bravoWorkDir/$1' velikost '$fileSize' v kvalite: '$quality' a modu: '$mode' '$bravoDir/bravo2 -i '$bravoWorkDir/$1' -f$bravoDir -o$lpDdevice -I200 -V$vMargin -H$hMargin $quality $mode'"
    logMsg 2 "$bravoDir/bravo2 -i \"$bravoWorkDir/$1\" -f$bravoDir -o$lpDdevice -I200 -V$vMargin -H$hMargin $quality $mode"
    logMsg 2 $($bravoDir/bravo2 -i "$bravoWorkDir/$1" -f$bravoDir -o$lpDdevice -I200 -V$vMargin -H$hMargin $quality $mode)
  else
    logMsg 1 "Nenalezen soubor s daty pro tisk: '$bravoWorkDir/$1'"
    exit
  fi
  sleep .5
  logMsg 5 "konci print"
 }

 function bravoBurn()
 {
  logMsg 5 "startuje burn"
  logMsg 3 "Budu palit: '$bravoWorkDir/$1'"
  if [ -e "$bravoWorkDir/$1" ]; then
   (
    logMsg 3 "Zaciname palit: '$bravoWorkDir/$1'"
    setStatusBurn "B"

#    if ! cdrecord -tao -d -speed=12 dev=$cdDevice "$bravoWorkDir/$1" >&/tmp/$$_burn.log; then
    if ! cdrecord -tao -d dev=$cdDevice "$bravoWorkDir/$1" >&/tmp/$$_burn.log; then
#    if ! growisofs -Z $cdDevice="$bravoWorkDir/$1" --use-the-force-luke=dao >&/tmp/$$_burn.log; then
      logMsg 1 "Vypalovani melo problemy: '$(cat < /tmp/$$_burn.log)'"
      setStatusBurn "E"
      echo -e "02
# zname: 00 OK
#        01 No data for burn
#        02 Burn error
#        03 Verify error" > /tmp/burnError
    else
      if [ "$2" = "verify" ]; then
        logMsg 3 "Budu kontrolovat: '$bravoWorkDir/$1'"
        if ! [ -d "/mnt/cdrom1" ]; then
          mkdir -p /mnt/cdrom1
        fi
        ( cd /mnt
          logMsg 2 "Mountuji vypalene CD '$1' ($2)"
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
            logMsg 1 "CHYBA! CD neni identicke s predlohou: '$(diff /tmp/$$_cdrom.ls /tmp/$$_iso.ls)' "
            errorISO=$(echo "$1"|
                       sed "s|\.iso\.\([^_]*\)_.*|.iso.\\1_1x_q1|")
#            errorPNM=$(echo "$errorISO"|
#                       sed "s|\.iso\.|.pnm.|")
            mv "$bravoWorkDir/$1" "$bravoWorkDir/$errorISO"
            ln "$bravoDir/ERROR.pnm" "$bravoWorkDir/$errorPNM"
            setStatusBurn "E"
           echo -e "03
# zname: 00 OK
#        01 No data for burn
#        02 Burn error
#        03 Verify error" > /tmp/burnError
          fi
#          rm -f /tmp/$$_cdrom.ls
#          rm -f /tmp/$$_iso.ls
          umount /mnt/cdrom1 2>/dev/null

      fi
    sleep .5
    logMsg 2 "Nastavuji status vypalovacky"
# POKUSNE VLOZENO !!!!!!!!!!!!!!!!!!!!!!!!!
    setStatusBurn "?"
    getDataBurn

    fi
#    sleep .5
#    logMsg 2 "Nastavuji status vypalovacky"
# POKUSNE ZAKAZANO !!!!!!!!!!!!!!!!!!!!!!!!!
#    setStatusBurn "?"
#    getDataBurn

#    setStatusBurn "?"
#    setStatusBurnMedia
   ) &
  else
    logMsg 1 "Nenalezena data k paleni"
    setStatusBurn "E"
    echo -e "01
# zname: 00 OK
#        01 No data for burn
#        02 Burn error
#        03 Verify error" > /tmp/burnError
  fi
  logMsg 5 "konci burn"
 }

function bravo()
 {
  logMsg 5 "startuje bravo: '$@'"
#  setStatusBurn "?"
  cmd="$@" 
  while [ -n "$1" ]; do
    wait=true
    logMsg 2 "Predavam prikaz: '$1'"
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
         logMsg 0 "Neznamy prikaz '$1' v retezci '$cmd'" > /dev/stderr
         exit 1;;
    esac

    if $wait; then
      waitOnStart
      waitOnEndOK
    fi
    sleep .5
    shift
  done 
  logMsg 5 "konci bravo"
 }
