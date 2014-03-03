<?php;
$GLOBALS['generalPlace']="/srv/bravo2";
$GLOBALS['bravoInput']=$GLOBALS['generalPlace']."/bravoInput";
$GLOBALS['bravoPrepare']=$GLOBALS['generalPlace']."/bravoPrepare";
$GLOBALS['bravoRobot']=$GLOBALS['generalPlace']."/bravoRobot";
$GLOBALS['bravoTmp']=$GLOBALS['generalPlace']."/bravoTmp";
$GLOBALS['bravoSourceMedia']=$GLOBALS['generalPlace']."/bravoSourceMedia";
//$GLOBALS['debug']=true;

if (!isset($functionIncluded))
 {
  if (!file_exists($GLOBALS['bravoInput']))
   {
    mkdir($GLOBALS['bravoInput']);
    shell_exec("chmod g+s,g+w ".$GLOBALS['bravoInput']);
   }
  if (!file_exists($GLOBALS['bravoPrepare']))
   {
    mkdir($GLOBALS['bravoPrepare']);
    shell_exec("chmod g+s,g+w ".$GLOBALS['bravoPrepare']);
   }
  if (!file_exists($GLOBALS['bravoRobot']))
   {
    mkdir($GLOBALS['bravoRobot']);
    shell_exec("chmod g+s,g+w ".$GLOBALS['bravoRobot']);
   }

  function logMsg($level,$error)
   {
    if ($GLOBALS['debug'])
     {
      echo "MSG$level: $error<br>\n";
     }
   }

  function getName($entry)
   {
    return ereg_replace("\..*","",ereg_replace("^\.","",$entry));
   }
  
  function getFileType($entry)
   {
    $type=strtolower(ereg_replace("\..*","",ereg_replace(getName($entry)."\.","",ereg_replace("^\.","",$entry))));
    $type==$entry?"":$entry;
    return $type;
   }
  
  function getISOtype($entry)
   {
    return getFileType($entry)=="iso"?"iso":"";
   }

  function getPNMtype($entry)
   {
    return getFileType($entry)=="pnm"?"pnm":"";
   }

  function getISOname($entry)
   {
    return getFileType($entry)=="iso"?$entry:"";
   }
  
  function getPNMname($entry)
   {
    return getFileType($entry)=="pnm"?$entry:"";
   }

//  function identify($file)
  function getIMGsize($file)
  
   {
    $f = fopen ($file, "r");
    $buffer=fread($f, 170); 
    if (substr($buffer,6,4)=="JFIF")
     {
      $x=hexdec(dechex(ord($buffer[163])).dechex(ord($buffer[164])));
      $y=hexdec(dechex(ord($buffer[165])).dechex(ord($buffer[166])));
//      echo "mame jpg ($x x $y)";
      return $x.'x'.$y;
     }
    if (substr($buffer,1,3)=="PNG")
     {
      $x=hexdec(dechex(ord($buffer[16])).dechex(ord($buffer[17])).dechex(ord($buffer[18])).dechex(ord($buffer[19])));
      $y=hexdec(dechex(ord($buffer[20])).dechex(ord($buffer[21])).dechex(ord($buffer[22])).dechex(ord($buffer[23])));
//      echo "mame png ($x x $y)";
      return $x.'x'.$y;
     }
    if (substr($buffer,0,3)=="GIF")
     {
      $x=hexdec(dechex(ord($buffer[7])).dechex(ord($buffer[6])));
      $y=hexdec(dechex(ord($buffer[9])).dechex(ord($buffer[8])));
//      echo "mame gif ($x x $y)";
      return $x.'x'.$y;
     }
    if (substr($buffer,0,2)=="P6")
     {
      $i=3;
      $x='';
      while ((ord($buffer[$i])!=32)&&($i<strlen($buffer)))
       {
        $x.=$buffer[$i];
        $i++;
       }
      $x=($i!=strlen($buffer)) ? $x : '';
      $i++;
      $y='';
      while ((ord($buffer[$i])!=10)&&($i<strlen($buffer)))
       {
        $y.=$buffer[$i];
        $i++;
       }
      $y=($i!=strlen($buffer)) ? $y : '';
///      echo "mame pnm ($x x $y)";
      return $x.'x'.$y;
     }
    fclose($f);
   }

  function getIMGsizeOld($entry)
   {
    $type=identify($entry);
echo "eee $type ee";    
//    $type = ereg_replace("JPG","JPEG",strtoupper(getFileType($entry)));
//    return ereg_replace(" .*","",ereg_replace(".*$type ","",shell_exec("identify $entry")));
//    $XY=getimagesize($entry);
echo "'$entry' $type >".getFileType($entry)."<<br>";
//    $size = getimagesize("/srv/bravo2/bravoTmp/Mensik02.jpg");
//    $size = getimagesize($entry);
echo "sssssssssss $entry ss".$size[0]." ".$size[1]."<br>";
    return $size[0].' '.$size[1];
   }
  
  function getCopies($entry)
   {
    $copies=ereg_replace("[^0-9]*","",ereg_replace("[^0-9]*([0-9]+)x.*","\\1",ereg_replace(".*\.","",$entry)));
    logMsg(3,"Ze souboru $entry jsme ziskali pocet kopii: '$copies'");
    $copies=$copies!=''?$copies:1;
    return $copies;
   }
  
  function getQuality($entry)
   {
    $quality=ereg_replace("[^0-9]*","",ereg_replace(".*q([0-9]+).*","\\1",ereg_replace(".*\.","",$entry)));
    $quality=$quality!=''?$quality:1;
    return $quality;
   }
  
  function getMedium($entry)
   {
    $medium=ereg_replace("_*q".getQuality($entry)."[_]*","",ereg_replace("_*".getCopies($entry)."x_*","",ereg_replace(".*\.","",$entry)));
    exec("wc -c '".$GLOBALS['bravoPrepare']."/$entry'",$out);
    $fileSize=ereg_replace(" .*","",$out[0]);
    $medium=(($medium=='iso')&&($fileSize>750000000))?"dvd":$medium;
    $medium=(($medium=='iso')&&($fileSize<=750000000))?"cd":$medium;
    $medium=$medium==getFileType($entry)?"cd":$medium;
    return $medium;
   }

  function autoRename($entry)
   {
    $detect=trim(ereg_replace(" data.*","",shell_exec("file -b '$entry'")));
    logMsg(3,"Ziskana signatura souboru '$entry': '$detect'");
    switch ($detect)
     {
      case "ISO 9660 CD-ROM filesystem":
          $type='iso';
          break;
      case "GIF image":
          $type='gif';
          break;
      case "JPEG image":
          $type='jpg';
          break;
      case "PNG image":
          $type='png';
          break;
      case "PNM image":
          $type='pnm';
          break;
      case "Netpbm PPM \"rawbits\" image":
          $type='pnm';
          break;
      case "empty":
          $type='~=~';
          break;
     }
    logMsg(3,"Rozpoznany typ souboru '$entry': '$type'");

    if (($type!='') && getFileType($entry)!==$type)
     {
      $newName=getName($entry).'.'.$type;
      logMsg(3,"Budeme automaticky prejmenovavat soubor: '$entry' na: '$newName'");
      rename($entry,$newName);
      return $newName;
     }
    logMsg(3,"Obrazek '$entry' je v poradku");
    return $entry;
   }
 
 function detectFileType($entry)
   {
//    $entry=autoRename($entry);
    if (strpos(strtolower($entry),'.iso')>0)
     {
      return "iso";
     }

    if ((strpos(strtolower($entry),'.pnm')>0) ||
        (strpos(strtolower($entry),'.jpg')>0) ||
        (strpos(strtolower($entry),'.jpeg')>0) ||
        (strpos(strtolower($entry),'.png')>0) ||
        (strpos(strtolower($entry),'.gif')>0))
     {
      return "img";
     }

    if ((strpos(strtolower($entry),'.iso')==0) &&
        (strpos(strtolower($entry),'.pnm')==0) &&
        (strpos(strtolower($entry),'.jpg')==0) &&
        (strpos(strtolower($entry),'.jpeg')==0) &&
        (strpos(strtolower($entry),'.png')==0) &&
        (strpos(strtolower($entry),'.gif')==0))
     {

      logMsg(3,"Budeme testovat typ souboru: '$entry' (neni to zadny znamy obrazek ani iso)");
      if ((file_exists($entry)) && (filetype($entry)=="dir"))
       {
        $full=false;
        $d = dir($entry);
        while (false !== ($entry = $d->read()))
         {
          if (substr($entry,0,1)!=".")
           {
            $full=true;
           }
         }
        return $full?"data":"nondata";
       }
      else
       {
        logMsg(3,"Soubor '$entry' nenalezen nebo to neni adresar");
        return "noniso";
       }
     }
   }

  function convert2pnm($file)
   {
    if (detectFileType($file)=="img")
     {
      if (getFileType($file)!="pnm")
       {
        logMsg(3,"Bude treba konvertovat file: '$file' na PNM");
        if (file_exists("/usr/bin/".getFileType($file)."topnm"))
         {
          $tmpfname = tempnam($GLOBALS['bravoTmp'], "bravoConvertTmp");
          logMsg(4,getFileType($file)."topnm '$file' > '$tmpfname'");
          shell_exec(getFileType($file)."topnm '$file' > '$tmpfname'");
          waitToFileExists($tmpfname);
          moveFile($tmpfname,$file);
          return autoRename($file);
         }
        else
         {
          logMsg(0,"Chybi nastroj pro konverzi file: '$file' na PNM (/usr/bin/".getFileType($file)."topnm))");
          return false;
         }
       }
      else
       {
        logMsg(3,"Obrazek je PNM, vse v poradku");
        return $file;
       }
     }
    else
     {
      logMsg(0,"Vzdyt '$file' vubec neni obrazek!");
      return false;
     }
   }

  function createNewExtension($file,$medium,$copies=1,$quality=0,$test=false)
   {
    logMsg(3,"Soubor typu: '".detectFileType($file)."'");
    if ((detectFileType($file)=='iso') || (detectFileType($file)=='data'))
     {
      $newExtension='.iso.'.$medium.'_'.$copies.'x_q'.$quality.($test?"_t":"");
     }
    if (detectFileType($file)=="img")
     {
      $newExtension='.pnm.'.$medium.'_'.$copies.'x_q'.$quality.($test?"_t":"");
     }
#    return $newExtension='.iso.'.$medium.'_'.$copies.'x_q'.$quality;
    return $newExtension;
   }  

//  function convertData2iso($file,$newName,$divide,$medium,$image=false,$newNameImage=false)
  function convertData2iso($file,$newName,$divide,$medium,$copies=1,$quality=2)
   {
    if (file_exists($GLOBALS['bravoPrepare']."/$newName") && ! is_dir($GLOBALS['bravoPrepare']."/$newName"))
     {
      unlink($GLOBALS['bravoPrepare']."/$newName");
     }
    if ($divide)
     {
      $size=$medium==="cd"?700:4500;
      logMsg(3,"Budeme trhat a konvertovat data: '$file' na iso pod jmenem: '$newName'");
//      logMsg(4,"zaloha-pc.split '".$GLOBALS['bravoInput']."/$file' '".$GLOBALS['bravoPrepare']."/$newName' -i -v$size");
//      shell_exec("zaloha-pc.split '".$GLOBALS['bravoInput']."/$file' '".$GLOBALS['bravoPrepare']."/$newName' -i -v$size");
//      logMsg(4,"/srv/zaloha-pc/zaloha-pc.split '".$GLOBALS['bravoInput']."/$file' '".$GLOBALS['bravoInput']."/$newName' -v$size >".$GLOBALS['bravoTmp']."/$newName-split.log &");
//      echo ereg_replace("\n","<br>\n",shell_exec("/srv/zaloha-pc/zaloha-pc.split '".$GLOBALS['bravoInput']."/$file' '".$GLOBALS['bravoInput']."/$newName' -v$size >".$GLOBALS['bravoTmp']."/$newName-split.log &"));
      logMsg(4,"/srv/zaloha-pc/zaloha-pc.split '".$GLOBALS['bravoInput']."/$file' '".$GLOBALS['bravoPrepare']."/$newName' -i -v$size 2>&1 >".$GLOBALS['bravoTmp']."/.$newName-split.log &");
//      echo ereg_replace("\n","<br>\n",shell_exec("/srv/zaloha-pc/zaloha-pc.split '".$GLOBALS['bravoInput']."/$file' '".$GLOBALS['bravoPrepare']."/$newName' -i -v$size >".$GLOBALS['bravoTmp']."/.$newName-split.log &"));
      exec("/srv/zaloha-pc/zaloha-pc.split '".$GLOBALS['bravoInput']."/$file' '".$GLOBALS['bravoPrepare']."/$newName' -i -v$size 2>&1 >".$GLOBALS['bravoTmp']."/.$newName-split.log &",$output);
      foreach ($output as $row)
       {
        echo "$row<br/>\n";
       }
/*
      if (file_exists($GLOBALS['bravoTmp']."/$newName"))
       {
        $d = dir($GLOBALS['bravoTmp']."/$newName");
        while (false !== ($entry = $d->read()))
         {
          if (substr($entry,0,1)!=".")
           {
            $counter=ereg_replace("\.iso$","",$entry);
            moveFile($GLOBALS['bravoTmp']."/$newName/$entry",$GLOBALS['bravoPrepare']."/$counter-$newName");
            if ($image && $newNameImage)
             {
              moveFile($GLOBALS['bravoInput']."/$image",$GLOBALS['bravoPrepare']."/$counter-$newNameImage");
             }
            if (file_exists($GLOBALS['bravoPrepare']."/$counter-$newName"))
             {
              rmdir_r($GLOBALS['bravoInput']."/$file");
             }
           }
         }
        rmdir($GLOBALS['bravoTmp']."/$newName");
       }
      else
       {
        logMsg(0,"Nepovedlo se trhani do '".$GLOBALS['bravoTmp']."/$newName'");
       }
*/       
     }
    else
     {
      $newName=$newName.createNewExtension($file.".iso",$medium,$copies,$quality);
      $name=getName($newName);
      logMsg(3,"Budeme konvertovat data: '$file' na iso pod jmenem: '$newName'");
//      logMsg(4,"mkisofs -iso-level 4 -J -R -joliet-long -output-charset cp1250 -o '".$GLOBALS['bravoPrepare']."/.$newName.iso' '".$GLOBALS['bravoInput']."/$file' 2>".$GLOBALS['bravoTmp']."/.$newName.iso-mkisofs.log &");
//      echo "$(mkisofs -iso-level 4 -J -R -joliet-long -o '".$GLOBALS['bravoPrepare']."/.$newName.iso' '".$GLOBALS['bravoInput']."/$file' 2>&1) >".$GLOBALS['bravoTmp']."/.$newName.iso-mkisofs.log &";
//      $_GET['mkisofsOutput'] = shell_exec("(mkisofs -iso-level 4 -J -R -joliet-long -o '".$GLOBALS['bravoPrepare']."/.$newName.iso' '".$GLOBALS['bravoInput']."/$file' 2>".$GLOBALS['bravoTmp']."/.$newName-mkisofs.log; echo -n ".$GLOBALS['bravoTmp']."/.$newName.iso-mkisofs.log) &");
//      logMsg(4,"mkisofs -iso-level 4 -J -R -joliet-long -input-charset iso8859-2 -output-charset cp852 -log-file '".$GLOBALS['bravoTmp']."/.$newName.iso-mkisofs.log' -o '".$GLOBALS['bravoPrepare']."/.$newName.iso' '".$GLOBALS['bravoInput']."/$file' >/dev/null &");
//      logMsg(4,"mkisofs -V '$name' -iso-level 4 -R -input-charset iso8859-2 -output-charset cp852 -log-file '".$GLOBALS['bravoTmp']."/.$newName.iso-mkisofs.log' -o '".$GLOBALS['bravoPrepare']."/.$newName.iso' '".$GLOBALS['bravoInput']."/$file' >/dev/null &");
      logMsg(4,"mkisofs -V '$name' -iso-level 4 -R -input-charset iso8859-2 -output-charset cp852 -log-file '".$GLOBALS['bravoTmp']."/.$newName-mkisofs.log' -o '".$GLOBALS['bravoPrepare']."/.$newName' '".$GLOBALS['bravoInput']."/$file' >/dev/null &");
//      exec("mkisofs -iso-level 4 -J -R -joliet-long -input-charset iso8859-2 -output-charset cp852 -log-file '".$GLOBALS['bravoTmp']."/.$newName.iso-mkisofs.log' -o '".$GLOBALS['bravoPrepare']."/.$newName.iso' '".$GLOBALS['bravoInput']."/$file' >/dev/null &");
//      exec("mkisofs -V '$name' -iso-level 4 -R -input-charset iso8859-2 -output-charset cp852 -log-file '".$GLOBALS['bravoTmp']."/.$newName.iso-mkisofs.log' -o '".$GLOBALS['bravoPrepare']."/.$newName.iso' '".$GLOBALS['bravoInput']."/$file' >/dev/null &");
      exec("mkisofs -V '$name' -iso-level 4 -R -input-charset iso8859-2 -output-charset cp852 -allow-limited-size -log-file '".$GLOBALS['bravoTmp']."/.$newName-mkisofs.log' -o '".$GLOBALS['bravoPrepare']."/.$newName' '".$GLOBALS['bravoInput']."/$file' >/dev/null &");
//echo "<br>";
//      echo("/srv/zaloha-pc/xx '".$GLOBALS['bravoPrepare']."/.$newName.iso' '".$GLOBALS['bravoInput']."/$file' >".$GLOBALS['bravoTmp']."/.$newName.iso-mkisofs.log &");
//      exec("/srv/zaloha-pc/xx '".$GLOBALS['bravoPrepare']."/.$newName.iso' '".$GLOBALS['bravoInput']."/$file' >".$GLOBALS['bravoTmp']."/.$newName.iso-mkisofs.log &");
      
//      moveFile($GLOBALS['bravoPrepare']."/.$newName.iso",$GLOBALS['bravoPrepare']."/$newName.iso");
//      if (file_exists($GLOBALS['bravoPrepare']."/$newName.iso"))
//      {
//       rmdir_r($GLOBALS['bravoPrepare']."/$file");
//      }
     }
   }
  
  function waitToFileExists($file)
   {
   logMsg(5,"Cekame na objeveni souboru: '$file'");
    while (!file_exists($file) || (filesize($file)==0))
     {
      if (file_exists($file))
       {
        logMsg(3,"Velikost souboru na ktery cekame($file): '".filesize($file)."'");
       }
      sleep(5);
     }
   }

  function moveFile($file,$newName)
   {
    logMsg(3,"Budeme presouvat file: '$file' na: '$newName'");
    if (file_exists("$newName"))
     {
      logMsg(3,"Mazu soubor stejneho jmena jako cil: '$newName'");
      unlink("$newName");
     }
    waitToFileExists($file);
    rename($file,$newName);
    waitToFileExists($newName);
   }

  function copyFile($file,$newName)
   {
    logMsg(3,"Budeme kopirovat file: '$file' na: '$newName'");
    if (file_exists("$newName"))
     {
      unlink("$newName");
     }
    waitToFileExists($file);
    copy($file,$newName);
    waitToFileExists($newName);
   }

  function linkFile($file,$newName)
   {
    logMsg(3,"Budeme linkovat file: '$file' na: '$newName'");
    if (file_exists("$newName"))
     {
      unlink("$newName");
     }
    waitToFileExists($file);
    link($file,$newName);
    waitToFileExists($newName);
   }

  function fileRights($file)
   {
    $perms = fileperms('/etc/passwd');
 
    if (($perms & 0xC000) == 0xC000) {
     // Socket
         $info = 's';
     } elseif (($perms & 0xA000) == 0xA000) {
     // Symbolic Link
         $info = 'l';
     } elseif (($perms & 0x8000) == 0x8000) {
     // Regular
         $info = '-';
     } elseif (($perms & 0x6000) == 0x6000) {
     // Block special
         $info = 'b';
     } elseif (($perms & 0x4000) == 0x4000) {
     // Directory
         $info = 'd';
     } elseif (($perms & 0x2000) == 0x2000) {
     // Character special
         $info = 'c';
     } elseif (($perms & 0x1000) == 0x1000) {
     // FIFO pipe
         $info = 'p';
     } else {
     // Unknown
         $info = 'u';
     }
 
     // Owner
     $info .= (($perms & 0x0100) ? 'r' : '-');
     $info .= (($perms & 0x0080) ? 'w' : '-');
     $info .= (($perms & 0x0040) ?
                 (($perms & 0x0800) ? 's' : 'x' ) :
                 (($perms & 0x0800) ? 'S' : '-'));
     
     // Group
     $info .= (($perms & 0x0020) ? 'r' : '-');
     $info .= (($perms & 0x0010) ? 'w' : '-');
     $info .= (($perms & 0x0008) ?
                 (($perms & 0x0400) ? 's' : 'x' ) :
                 (($perms & 0x0400) ? 'S' : '-'));
     
     // World
     $info .= (($perms & 0x0004) ? 'r' : '-');
     $info .= (($perms & 0x0002) ? 'w' : '-');
     $info .= (($perms & 0x0001) ?
                 (($perms & 0x0200) ? 't' : 'x' ) :
                 (($perms & 0x0200) ? 'T' : '-'));
     
     return $info;
   }

  function rmdir_r($path)
   {
    if (!is_dir($path)) {return false;}
    logMsg(3,"Budeme likvidovat adresar: '$path'");
    $stack = Array($path);
    while ($dir = array_pop($stack))
     {
      if (@rmdir($dir)) {continue;}
      $stack[] = $dir;
      $dh = opendir($dir);
      while (($child = readdir($dh)) !== false)
       {
        if (($child === '.') || ($child === '..')) {continue;}
        $child = $dir . DIRECTORY_SEPARATOR . $child;
        if (is_dir($child)) {$stack[] = $child;}
        else 
         {
          if (!unlink($child)){echo "<font size='1' color='red'>Problém s mazáním '$child'</font><br>";}
         }
       }
     }
    return true;
   }

  function humanSize ( $size )
   {
    return ($size<1000 ? "$size b"
                       : (($size<1000000) ? (((integer)($size/100))/10)." Kb"
                                          : (($size<1000000000) ? (((integer)($size/100000))/10)." Mb"
                                                                : (((integer)($size/100000000))/10)." Gb")));
   }

  function testForException($dir)
   {
    echo("cd '".$GLOBALS['bravoInput']."/$dir'|find .|grep -i '\.mp3\|wma$'|wc -l");
    exec("cd '".$GLOBALS['bravoInput']."/$dir'|find .|grep -i '\.mp3\|wma$'|wc -l",$statistic);
    exec("cd '".$GLOBALS['bravoInput']."/$dir'|find .|grep -i '\.mp3\|wma$'",$output);
    echo "<br>$dir<br>stat:".$statistic[0]."<br>List:".$output[0].".";
   }

  $functionIncluded=true;
 }
?>
