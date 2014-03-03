<html>
<head>
<meta http-equiv='Refresh' content='60;url=index.php?mkisofsOutput=<?php;echo$_GET['mkisofsOutput']."'";?>>
<meta http-equiv='Content-Type' content='text/html; charset=iso-8859-2'>
<meta name='description' content='version 0.1.2'>
<meta name='copyright' content='Copyright (c) 2007 David Fabel'>
<meta name='author' content='fabel (at) sudop (dot) cz'>
<title>Bravo II</title>
<link rel='shortcut icon' href='images/iso.ico'>
</head>

<body bgcolor='#eeffee'>
<center>
<form method='post' action='indexAction.php' enctype='multipart/form-data'>
<?php
include("functions.php");

if (file_exists("/var/log/apps/autoBurn.log")) 
 {
  $error=shell_exec("grep 'Uloha: .* skoncila spatne: ' /var/log/apps/autoBurn.log");
  echo "<font size='7' color='red'>$error</font><br>";
 }

$nondata=array();
$noniso=array();
$data=array('bez dat');
$iso=array();
$img=array('bez obrázku');

function inputRow($cell1,$typ1,$cell2,$typ2,$counter)
 {
  logMsg(3,"Soubor: '$cell1' edit: jmeno: '".$GLOBALS['editName']."' ");
  $typ1=$cell1=='bez dat'?'non':$typ1;
  $typ2=ereg_replace("bez obrázku","",$typ2);
  echo "<tr>
<td align='center'>".(isset($cell1)&&($cell1!='')&&($typ1==='iso'||$typ1==='data')?"<input type='image' name='inputDelete' src='images/trash.png' value='$cell1' border='0'>":"")."</td>
<td align='center'>".(isset($cell1)&&($cell1!='')&&($typ1==='iso'||$typ1==='data'||$typ1==='non')?"<input type='hidden' name='fileType' value='$typ1'><input type='radio' name='file' onChange='showHideDivide()' value='$cell1'".
(getname($cell1)==$GLOBALS['editName']?" checked":"").">":"")."</td>
<td>$cell1</td>
<td>".(isset($cell1)&&($cell1!='')&&($typ1==='iso'||$typ1==='data')?"<a href='indexAction.php?".$typ1."Preview=$cell1' target='preview'><img src='images/$typ1.png' border='0'></a>":"")."</td>
<td>&nbsp;</td>
<td>".(isset($cell2)&&($cell2!='')?"<input type='radio' name='image' value='$cell2'".(getname($cell2)==$GLOBALS['editName']?" checked":"").">":"")."</td>
<td>$cell2</td>
<td>".(isset($cell1)&&($cell2!='')&&($typ2!=='')?"<a href='indexAction.php?imgPreview=$cell2' target='preview'><img src='images/img.png' border='0'></a>":"")."</td>
<td>".(isset($cell2)&&($cell2!='')&&($typ2!=='')?"<input type='image' name='inputDelete' src='images/trash.png' value='$cell2' border='0'>":"")."</td>
</tr>

";
 }

function setSources()
 {
  echo "Zásobníky: ";
  if (!file_exists($GLOBALS['bravoSourceMedia']))
   {
    $fd=fopen ($GLOBALS['bravoSourceMedia'], "w");
    fwrite ($fd,"# soubor aktualniho obsazeni leveho, vypalovacky a praveho zdroje

leftSource=DVD
burnDrive=
rightSource=CD
"); 
    fclose ($fd);
   }
  $fd = fopen ($GLOBALS['bravoSourceMedia'], "r");
  while (!feof ($fd))
   {
    $buffer=fgets($fd, 4096);
    if (substr($buffer,0,11)=="leftSource=")
     { 
      $leftSource=ereg_replace("\"","",ereg_replace(" *\r*\n","",ereg_replace("leftSource=","",$buffer)));
      if ((strtolower($leftSource)==="dvd")||(strtolower($leftSource)==="cd"))
       {
        echo "<select name='sourceLeft'>
<option".(strtolower($leftSource)==="dvd"?" selected":"").">DVD
<option".(strtolower($leftSource)==="cd"?" selected":"").">CD
<option>Special
</select>
";
       } else {
        echo "<select name='sourceLeft'>
<option".(strtolower($leftSource)==="dvd"?" selected":"").">DVD
<option".(strtolower($leftSource)==="cd"?" selected":"").">CD
<option selected>Special
</select>
<input type='text' name='specialLeft' value='$leftSource'>
";
       }
     }
    if (substr($buffer,0,10)=="burnDrive=")
     { 
      $burnDrive = ereg_replace("\"","",ereg_replace(" *\r*\n","",ereg_replace("burnDrive=","",$buffer)));
      echo " / $burnDrive / \n"; 
     }
    if (substr($buffer,0,12)=="rightSource=")
     { 
      $rightSource=ereg_replace("\"","",ereg_replace(" *\r*\n","",ereg_replace("rightSource=","",$buffer)));
      if ((strtolower($rightSource)==="dvd")||(strtolower($rightSource)==="cd"))
       {
        echo "<select name='sourceRight'>
<option".(strtolower($rightSource)==="dvd"?" selected":"").">DVD
<option".(strtolower($rightSource)==="cd"?" selected":"").">CD
<option>Special
</select>
";
       } else {
        echo "<select name='sourceRight'>
<option".(strtolower($rightSource)==="dvd"?" selected":"").">DVD
<option".(strtolower($rightSource)==="cd"?" selected":"").">CD
<option selected>Special
</select>
<input type='text' name='specialRight' value='$rightSource'>
";
       }
     }
   }
  fclose ($fd);
  echo "<input type='submit' name='mediaSources' value='Ulož'>";
 }

$d = dir($GLOBALS['bravoInput']);
while (false !== ($entry = $d->read()))
 {
  autoRename($GLOBALS['bravoInput']."/$entry");
 }

$d = dir($GLOBALS['bravoInput']);
while (false !== ($entry = $d->read()))
 {
//$GLOBALS['debug']=true;
  logMsg(3,"Nacten soubor ".$GLOBALS['bravoInput']."/$entry" );
  if (substr($entry,0,1)!=".")
   {
    logMsg(3,"Test filetype ".$GLOBALS['bravoInput']."/$entry" );
    if (detectFileType($GLOBALS['bravoInput']."/$entry")=="data")
     {
      logMsg(3,"Detect data");
      array_push($data,$entry);
     }

    if (detectFileType($GLOBALS['bravoInput']."/$entry")=="nondata")
     {
      logMsg(3,"Detect nondata");
      array_push($nondata,$entry);
     }

    if (detectFileType($GLOBALS['bravoInput']."/$entry")=="noniso")
     {
      logMsg(3,"Detect noniso");
      array_push($noniso,$entry);
     }

    if (detectFileType($GLOBALS['bravoInput']."/$entry")=="img")
     {
      logMsg(3,"Detect img");
      array_push($img,$entry);
     }

    if (detectFileType($GLOBALS['bravoInput']."/$entry")=="iso")
     {
      logMsg(3,"Detect iso");
      array_push($iso,$entry);
     }
   }
 }
$d->close();

setSources();
echo "<hr>
<h1>Vytvořte úlohu</h1>
<input type='submit' name='create' value='Vytvoř'>
<!--<input type='hidden' name='MAX_FILE_SIZE' value='409000000'>-->
<input type='hidden' name='load' value='load'>
<input type='file' name='userFile' size='40'>
<input type='submit' name='loadFile' value='Načíst'>
<table>
<tr>
<td valign='top'>
<table border='1' cellpadding='0' cellspacing='0'>\n";
$i=0;
if (sizeof($data)+sizeof($iso)+sizeof($nondata)+sizeof($noniso)>sizeof($img))
 {
  foreach ($data as $name)
   {
    inputRow($name,'data',$img[$i],ereg_replace(".*\.","",$img[$i]),$i);
    $i++;
   }
  foreach ($iso as $name)
   {
    inputRow($name,'iso',$img[$i],ereg_replace(".*\.","",$img[$i]),$i);
    $i++;
   }
  foreach ($nondata as $name)
   {
    inputRow($name,'nondata',$img[$i],ereg_replace(".*\.","",$img[$i]),$i);
    $i++;
   }
  foreach ($noniso as $name)
   {
    inputRow($name,'noniso',$img[$i],ereg_replace(".*\.","",$img[$i]),$i);
    $i++;
   }
  }
else
 {
  foreach ($img as $name)
   {
    if ($i<sizeof($data))
     {
      inputRow($data[$i],'data',$name,ereg_replace(".*\.","",$name),$i);
     }
    else
     {
      if ($i-sizeof($data)<sizeof($iso))
       {
        inputRow($iso[$i-sizeof($data)],'iso',$name,ereg_replace(".*\.","",$name),$i);
       }
      else
       {
        if ($i-sizeof($data)-sizeof($iso)<sizeof($nondata))
         {
          inputRow($nondata[$i-sizeof($data)-sizeof($iso)],'nondata',$name,ereg_replace(".*\.","",$name),$i);
         }
        else
         {
          if ($i-sizeof($data)-sizeof($iso)-sizeof($nondata)<sizeof($noniso))
           {
            inputRow($noniso[$i-sizeof($data)-sizeof($iso)-sizeof($nondata)],'noniso',$name,ereg_replace(".*\.","",$name),$i);
           }
          else
           {
            inputRow('','',$name,ereg_replace(".*\.","",$name),$i);
           }
         }
       }
     }
    $i++;
   }
 }
logMsg(3,"Edit: jmeno: '".$GLOBALS['editName']."' type: '".$GLOBALS['editType']."' kopie: '".$GLOBALS['editCopies']."' kvalita: '".$GLOBALS['editQuality']."' media: '".$GLOBALS['editMedium']."' ");
echo "</table>
</td>

<td>
<table>
<tr><td>Název:</td><td><input type='text' name='projectName' value='".$GLOBALS['editName']."'></td></tr>

<tr><td>Typ:</td><td><select name='medium' onChange='showHideSpecialName()'>
<option value='dvd'".(strtolower($GLOBALS['editMedium'])=='dvd'?" selected":"").">DVD
<option value='cd'".(strtolower($GLOBALS['editMedium'])=='cd'?" selected":"").">CD
<option value='Special'".((strtolower($GLOBALS['editMedium'])!='dvd')&&(strtolower($GLOBALS['editMedium'])!='cd')&&($GLOBALS['editMedium']!='')?" selected":"").">Special
</select>
<input type='text' name='specialName' value='".((strtolower($GLOBALS['editMedium'])!='dvd')&&(strtolower($GLOBALS['editMedium'])!='cd')?$GLOBALS['editMedium']:"")."'></td></tr>

<tr><td>Kopie:</td><td><input type='text' size='3' name='copies' value='".$GLOBALS['editCopies']."'></td></tr>
<tr><td>Tisk:</td><td><select name='quality'>
<option value='0'".($GLOBALS['editQuality']==0?" selected":"").">nízká
<option value='1'".(!isset($GLOBALS['editQuality'])||$GLOBALS['editQuality']==1?" selected":"").">nižší
<option value='2'".($GLOBALS['editQuality']==2?" selected":"").">střední
<option value='3'".($GLOBALS['editQuality']==3?" selected":"").">vysoká
</select></td></tr>

<tr><td>Trhat:</td><td><div name='divideCell'><input type='checkbox' name='divide' disabled></div></td>
<td rowspan='4'><input type='submit' name='dataSources' value='Dále'></td></tr>
</table>
</td>
</tr>
</table>
</form>
<script>
function showHideSpecialName()
 {
  if (document.forms[0].medium.value=='Special')
   {
    document.forms[0].specialName.style.cssText='';
   }
  else
   {
    document.forms[0].specialName.style.cssText='display:none';
   }
 }
showHideSpecialName();

function showHideDivide()
 {
  filesLength=document.forms[0].fileType.length;
  if(filesLength>1)
   {
    for(i=0;i<filesLength;i++)
     {
      if (document.forms[0].file[i].checked)
       {
        if(document.forms[0].fileType[i].value=='data')
         {
          document.forms[0].divide.disabled=false;
          document.forms[0].divide.checked=true;
         }
        else
         {
          document.forms[0].divide.disabled=true;
          document.forms[0].divide.checked=false;
         }
       }
     }
   }
 }
</script>
";
//if (file_exists($_GET['mkisofsOutput']))
// {
//  echo "<font size='1' color='red'><h2>Chyby ve vytváření ISO souboru</h2></center><br>";
//  $fd = fopen ($_GET['mkisofsOutput'], "r");
//  while (!feof ($fd))
//   {
//    $buffer=fgets($fd, 4096);
//    echo ereg_replace("\n","<br><br>",ereg_replace("Warning: .*","",$buffer));
//   }
// }
//echo "</font><center><hr>\n";
echo "<center><hr>\n";

$prepare=array();
$prepareNames=array();
$d = dir($GLOBALS['bravoPrepare']);
while (false !== ($entry = $d->read()))
 {
//$GLOBALS['debug']=true;
  logMsg(3,"Testuji isofs soubor '".$GLOBALS['bravoTmp']."/$entry-mkisofs.log' jako novy" );
  if (file_exists($GLOBALS['bravoTmp']."/$entry-mkisofs.log"))
   {
    logMsg(3,"Ano, soubor '".$GLOBALS['bravoTmp']."/$entry-mkisofs.log' existuje");
//    echo "tail -n1 '".$GLOBALS['bravoTmp']."/$entry-mkisofs.log'";
    $mkisofsLogLastRow=shell_exec("tail -n1 '".$GLOBALS['bravoTmp']."/$entry-mkisofs.log'");
    logMsg(3,$mkisofsLogLastRow);

    if (strpos("-".$mkisofsLogLastRow,"extents written")>0)
     {
      $name=getName(ereg_replace("^.","",$entry));
      logMsg(3,"Nasel novy ISO soubor '$entry' a musim zjistit zda pod jmenem '$name' je obrazek");
      $d1 = dir($GLOBALS['bravoPrepare']);
      $image = false;
      while ((false !== ($entryImg = $d1->read())) && !$image)
       {
        $nameImg=getName($entryImg);
        $typeImg=getFileType($entryImg);
        if (($typeImg=='pnm')&&($nameImg===$name))
         {
          logMsg(3,"Nasel jsem obrazek '$entryImg' k iso '$name'");
          $image = $entryImg;
         }
       }
      if ($image)
       {
        $nameImg=getName($image);
        $typeImg=getFileType($image);
        $medium=getMedium($image);
        $copies=getCopies($image);
        $quality=getQuality($image);
        logMsg(3,"Obrazek '$image' (Jmeno: '$nameImg', typ: '$typeImg', medium '$medium', kopie '$copies', kvalita '$quality')");
        $newName=$name.createNewExtension($entry,$medium,$copies,$quality);
        logMsg(3,"Soubor '$entry' a dostane podle obrazku '$image' jmeno '$newName'");
       }
      else
       {
        $medium=getMedium($entry);
        $copies=getCopies($entry);
        $quality=getQuality($entry);
        logMsg(3,"Soubor '$entry' (medium '$medium', kopie '$copies', kvalita '$quality')");
        $newName=$name.createNewExtension($entry,$medium,$copies,$quality);
        logMsg(3,"Soubor '$entry' a dostane podle souboru '$image' jmeno '$newName'");
       }
      moveFile($GLOBALS['bravoPrepare']."/$entry",$GLOBALS['bravoPrepare']."/$newName");
      logMsg(3,"Log file nalezeneho disku '$entry' smazeme (".$GLOBALS['bravoTmp']."/.$entry-mkisofs.log)");
      unlink($GLOBALS['bravoTmp']."/$entry-mkisofs.log");
      $entry = $newName;
     }
   }

  $name=getName($entry);
  $type=getFileType($entry);
//  $name=ereg_replace("\.iso","",ereg_replace("[0-9]*-","",$entry));
  $nameShort=ereg_replace("^[0-9]+-","",$name);

//$GLOBALS['debug']=true;

  logMsg(3,"Testuji split soubor '".$GLOBALS['bravoTmp']."/.$nameShort-split.log' jako novy" );
  if (($GLOBALS['debug'])&&($type=='iso'))
   {
    logMsg(3,"Ano, soubor '$entry' je iso");
    if (file_exists($GLOBALS['bravoTmp']."/.$nameShort-split.log"))
     {
      logMsg(3,"Ano, soubor '".$GLOBALS['bravoTmp']."/.$nameShort-split.log' existuje");
      logMsg(3,"tail -n1 '".$GLOBALS['bravoTmp']."/.$nameShort-split.log' | grep 'Deleni dokonceno'");
     }
   }
  else
   {
    logMsg(3,"NE, soubor '$entry' NENI iso ($type)");
   }
  if (($type=='iso')&&(file_exists($GLOBALS['bravoTmp']."/.$nameShort-split.log")) && (shell_exec("tail -n1 '".$GLOBALS['bravoTmp']."/.$nameShort-split.log' | grep 'Deleni dokonceno'")!==''))
   {
    logMsg(3,"Nasel jsem vysledek trhani '$entry' a musim zjistit zda pod jmenem '$nameShort' jsou obrazky");
    $d1 = dir($GLOBALS['bravoPrepare']);
    $image = false;
    while ((false !== ($entryImg = $d1->read())) && !$image)
     {
      $nameImg=getName($entryImg);
      $typeImg=getFileType($entryImg);
      if (($typeImg=='pnm')&&($nameImg===$nameShort))
       {
        logMsg(3,"Nasel jsem obrazek '$entryImg' k iso '$entry'");
        $image = $entryImg;
       }
     }

    $i='01';
    logMsg(3,"Hledam patricnou cast trhaneho souboru '".$GLOBALS['bravoPrepare']."/$i-$nameShort.iso'");
    while (file_exists($GLOBALS['bravoPrepare']."/$i-$nameShort.iso"))
     {
      $i=ereg_replace(".*(..)$","\\1",'0'.++$i);
      logMsg(3,"Hledam patricnou cast trhaneho souboru '".$GLOBALS['bravoPrepare']."/$i-$nameShort.iso'");
     }
    
   if (($image) && ($i>1))
     {
      $i=ereg_replace(".*(..)$","\\1",'0'.--$i);
      logMsg(3,"Celkem je '$nameShort' natrhano na '$i' casti");
      $nameImg=getName($image);
      $typeImg=getFileType($image);
      $medium=getMedium($image);
      $copies=getCopies($image);
      $quality=getQuality($image);
      logMsg(3,"Obrazek '$image' (Jmeno: '$nameImg', typ: '$typeImg', medium '$medium', kopie '$copies', kvalita '$quality')");
  
      $d1 = dir($GLOBALS['bravoPrepare']);
      while (false !== ($entryIso = $d1->read()))
       {
        $nameIso=getName($entryIso);
        $typeIso=getFileType($entryIso);
        $nameShortIso=ereg_replace("[0-9]*-","",$nameIso);
        if (($typeIso=='iso')&&($nameImg===$nameShortIso))
         {
          $newNameImg=$name.createNewExtension($image,$medium,$copies,$quality);
          logMsg(3,"Nasel jsem disk '$entryIso', takze obrazek '$image' zkopirujeme pod jmenem: '$newNameImg'");
          copyFile($GLOBALS['bravoPrepare']."/$image",$GLOBALS['bravoPrepare']."/$newNameImg");
          $newNameIso=$name.createNewExtension($entry,$medium,$copies,$quality);
          logMsg(3,"Nalezeny disk '$entry' prejmenujeme v souladu s obrazkem na '$newNameIso'");
          moveFile($GLOBALS['bravoPrepare']."/$entry",$GLOBALS['bravoPrepare']."/$newNameIso");
         }
       }
      logMsg(3,"Zvladli jsme vsechny disky, smazeme puvodni obrazek '$image'");
      unlink($GLOBALS['bravoPrepare']."/$image");
      logMsg(3,"Log file nalezeneho disku '$entry' smazeme (".$GLOBALS['bravoTmp']."/.$nameShort-split.log)");
      unlink($GLOBALS['bravoTmp']."/.$nameShort-split.log");
//      moveFile($GLOBALS['bravoTmp']."/.$nameShort-split.log", $GLOBALS['bravoTmp']."/.$nameShort-split.old");
      $entry='.';
     }
    else
     {
      logMsg(3,"Bohuzel, k soboru '$entry' neni obrazek a neni tedy informace o poctu kopii");
      logMsg(3,"Log file nalezeneho disku '$entry' smazeme (".$GLOBALS['bravoTmp']."/.$nameShort-split.log)");
      unlink($GLOBALS['bravoTmp']."/.$nameShort-split.log");
     }
   }

  if (($entry!=='.') && ($entry!=='..'))
   {
    $name=getName($entry);
    $type=getFileType($entry);
    $iso=getISOtype($entry);
    $img=getPNMtype($entry);
    $entryISO=getISOname($entry);
    $entryIMG=getPNMname($entry);
    $copies=getCopies($entry);
    $quality=getQuality($entry);
    $medium=getMedium($entry);
    $fullPathName=$GLOBALS['bravoPrepare'].'/'.$entry;
    $size=`stat -c%s '$fullPathName'`;
    $imgXY=getIMGsize($fullPathName);
    $working=(strpos("$entry",".")==0)||(is_dir($fullPathName));
    if (!in_array($name, $prepareNames))
     {
      array_push($prepareNames,$name);
      array_push($prepare,array($entryISO,$entryIMG,$name,$type,$iso,$img,$copies,$quality,$medium,($iso=='iso'?$size:0),($iso!='iso'?$size:0),($iso!='iso'?$imgXY:0),$working));
     } else {
      $key=array_search($name,$prepareNames);
      $prepare[$key][0]=$iso=='iso'?$entryISO:$prepare[$key][0];
      $prepare[$key][1]=$iso!='iso'?$entryIMG:$prepare[$key][1];
      $prepare[$key][4]=$prepare[$key][4].$iso;
      $prepare[$key][5]=$prepare[$key][5].$img;
      $prepare[$key][9]=$iso=='iso'?$size:$prepare[$key][9];
      $prepare[$key][10]=$iso!='iso'?$size:$prepare[$key][10];
      $prepare[$key][11]=$iso!='iso'?$imgXY:$prepare[$key][11];
      $prepare[$key][12]=$prepare[$key][12]||$working;
     }
   }
 }
$d->close();

echo "<h1>Fronta úloh</h1>
<a href='index.php'>Obnov</a>
<table border='1' cellpadding='0' cellspacing='0'>
<tr>
<td><i><b>Jméno</td>
<td><i><b>Typ</td>
<td><i><b>Kopie</td>
<td align='center'><img src='images/zippo.png' border='0' title='Úlohu vypal a vymaž'>+<img src='images/trash.png' border='0' title='Úlohu vypal a vymaž'></td>
<td align='center'><img src='images/zippo.png' border='0' title='Úlohu vypal a zkontroluj'>+<img src='images/zippoVerify.png' border='0' title='Úlohu vypal a zkontroluj'></td>
<td><i><b>Akce</td>
</tr>
";
foreach ($prepare as $row)
 {
  if (!$row[12])
   {
    echo "<form method='post' action='indexAction.php'>
<tr>
<td>".$row[8].": ".$row[2]."</td>
<td align='center'>".($row[4]!=''?"<a href='indexAction.php?isoPreview=".$row[0]."' target='preview'><img src='images/iso.png' border='0' border='0' title='Úloha obsahuje ISO soubor o velikosti ".humanSize($row[9])."'></a>":"").
($row[5]!=''?"<a href='indexAction.php?imgPreview=".$row[1]."' target='preview'><img border='0' src='images/img.png' border='0' title='Úloha obsahuje obrázek ".$row[11]." o velikosti ".humanSize($row[10])."'><img src='images/q".$row[7].".png' border='0' title='Úloha se bude tisknout v kvalitě ".$row[7]."'></a>":"")."</td>
<td align='center'><input type='text' name='copies' value='".$row[6]."' size='3'></td>
<td align='center'><input type='checkbox' name='burnAndTrash' title='Úlohu vypal a vymaž'></td>
<td align='center'><input type='checkbox' name='burnAndVeify' title='Úlohu vypal a zkontroluj'></td>
<td><button type='submit' name='prepareToRobot' value='".$row[2]."'><img src='images/zippo.png' border='0' title='Pošli do fronty na pálení'></button>
<button type='submit' name='prepareDelete' value='".$row[2]."'><img src='images/trash.png' border='0' title='Vymaž'></button>
<button type='submit' name='prepareEdit' value='".$row[2]."'><img src='images/tools1.png' border='0' title='Změň parametry'></button>
</td>
</tr>
</form>

";
   }
  else
   {
    $mkisofsPercent =='';
    if (file_exists($GLOBALS['bravoTmp']."/.".$row[2].".iso-mkisofs.log"))
     {
      $mkisofsPercent = ereg_replace("%.*","",shell_exec("grep -a '%' '".$GLOBALS['bravoTmp']."/.".$row[2].".iso-mkisofs.log' | tail -n1"));
     }

    if (($mkisofsPercent=='') && file_exists($GLOBALS['bravoTmp']."/.".$row[2].".iso.".$row[8]."_".$row[6]."x_q".$row[7]."-mkisofs.log"))
     {
      $mkisofsPercent = ereg_replace("%.*","",shell_exec("grep -a '%' '".$GLOBALS['bravoTmp']."/.".$row[2].".iso.".$row[8]."_".$row[6]."x_q".$row[7]."-mkisofs.log' | tail -n1"));
     }

    if (file_exists($GLOBALS['bravoTmp']."/.".$row[2]."-split.log"))
     {
      $disk = trim(shell_exec("grep '+ disk' '".$GLOBALS['bravoTmp']."/.".$row[2]."-split.log' |sed 's|+ disk ||'"));
      if ($disk!=='')
       {
        $splitMkisofsPercent = $disk.': '.ereg_replace("%.*","",shell_exec("grep -a '%' '".$GLOBALS['bravoTmp']."/.".$row[2]."-split.log' | tail -n1"))."%";
       }
      else
       {
        $splitMkisofsPercent = "trhám 01";
       }
     }
//<td>".$row[8].": ".$row[2]."</td>
    echo "<form method='post' action='indexAction.php'>
<tr bgcolor=#808000>
<td>Zpracovavam: ".$row[2]."</td>
<td align='center'><img src='images/iso.png' border='0' title='Vytváří se ISO soubor o velikosti ".humanSize($row[9])."'></td>
<td></td>
<td></td>
<td><a href='indexAction.php?".(strlen($splitMkisofsPercent)>0
?"splitMkisofsOutput=".$row[2]."' target='split.log'>$splitMkisofsPercent"
:"mkisofsOutput=".$row[2]."' target='mkisofs.log'>$mkisofsPercent%")."</a></td>
</tr>
</form>

";
   }
 }

$prepare=array();
$prepareNames=array();
$d = dir($GLOBALS['bravoRobot']);
while (false !== ($entry = $d->read()))
 {
  if ((substr($entry,0,1)!=".") && (strpos("$entry",".")>0))
   {
    $name=getName($entry);
    $type=getFileType($entry);
    $iso=getISOtype($entry);
    $img=getPNMtype($entry);
    $entryISO=getISOname($entry);
    $entryIMG=getPNMname($entry);
    $copies=getCopies($entry);
    $quality=getQuality($entry);
    $medium=getMedium($entry);
    $fullPathName=$GLOBALS['bravoRobot'].'/'.$entry;
    $size=`stat -c%s $fullPathName`;	    
    $imgXY=getIMGsize($fullPathName);

    if (!in_array($name, $prepareNames))
     {
      array_push($prepareNames,$name);
      array_push($prepare,array($entryISO,$entryIMG,$name,$type,$iso,$img,$copies,$quality,$medium,($iso=='iso'?$size:0),($iso!='iso'?$size:0),($iso!='iso'?$imgXY:0)));
     } else {
      $key=array_search($name,$prepareNames);
      $prepare[$key][0]=$iso=='iso'?$entryISO:$prepare[$key][0];
      $prepare[$key][1]=$iso!='iso'?$entryIMG:$prepare[$key][1];
      $prepare[$key][4]=$prepare[$key][4].$iso;
      $prepare[$key][5]=$prepare[$key][5].$img;
      $prepare[$key][9]=$iso=='iso'?$size:$prepare[$key][9];
      $prepare[$key][10]=$iso!='iso'?$size:$prepare[$key][10];
      $prepare[$key][11]=$iso!='iso'?$imgXY:$prepare[$key][11];
     }
   }
 }
$d->close();
foreach ($prepare as $row)
 {
  echo "<form method='post' action='indexAction.php'>
<tr bgcolor=#ff8000>
<td>".$row[8].": ".$row[2]."</td>
<td><center>".($row[4]!=''?"<a href='indexAction.php?isoPreview=".$row[0]."' target='preview'><img src='images/iso.png' border='0' title='Úloha obsahuje ISO soubor o velikosti ".humanSize($row[9])."'></a>":"").
($row[5]!=''?"<a href='indexAction.php?imgPreview=".$row[1]."' target='preview'><img src='images/img.png' border='0' title='Úloha obsahuje obrázek ".$row[11]." o velikosti ".humanSize($row[10])."'>
<img src='images/q".$row[7].".png' border='0' title='Úloha se bude tisknout v kvalitě ".$row[7]."'></a>":"")."</center></td>
<td align='center'>".$row[6]."x</td>
<td>&nbsp;</td>
<td><button type='submit' name='robotToPrepare' value='".$row[2]."'><img src='images/nonzippo.png' border='0' title='Zastav pálení'></button>
<button type='submit' name='robotDelete' value='".$row[2]."'><img src='images/trash.png' border='0' title='Vymaž'></button></td>
</tr>
</form>

";
 }
echo "</table>";

?>

</center>
</body>
</html>
