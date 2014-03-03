<?php;
//$GLOBALS['debug']=true;
include("functions.php");
$sourceFiles=array();
$GLOBALS['badExtension']==0;
function createTree($oldRow,$row)
 {
  $output = $oldRow;
  $levelNext = strlen(ereg_replace("[^/]","",$row));
  $level = strlen(ereg_replace("[^/]","",$oldRow));
  $levelPlus = '';
  for ($i=0;$i<$level-1;$i++)
   {
    $levelPlus .= "| ";
   }
  $output = $levelPlus.'+'.ereg_replace(".*/","",$output);
  $output = ereg_replace("^\+","",$output);
  $output = ($levelNext<$level) ? ereg_replace("\+","`",$output) : $output;
  $output = ereg_replace("\+","|<span class='left'>-</span>",$output);
  $extension = strtolower(ereg_replace(".*\.","",$output));
  if (($extension==="mp3") || ($extension==="wma") ||
      ($extension==="mpg") || ($extension==="mpeg") ||
      ($extension==="mov") || ($extension==="ogg") ||
      ($extension==="avi") || ($extension==="wmv"))
   {
    $GLOBALS['badExtension']++;
    $output = "<font color='red'>$output</font>";
   }
  return ($row!==$oldRow)? "$output<br>":"";
 }

if (isset($_GET['dataPreview']))
 {
  if (file_exists($GLOBALS['bravoInput'].'/'.$_GET['dataPreview']))
   {
    $sourceDir = $GLOBALS['bravoInput'].'/'.$_GET['dataPreview'];
   }
  exec("cd $sourceDir;find . -print|grep -v '^\.$'|sort ",$dataInfo);

  echo "
<html>
<head>
<meta http-equiv='Content-Type' content='text/html; charset=iso-8859-2'>
<meta name='description' content='version 0.1.0'>
<meta name='copyright' content='Copyright (c) 2007 David Fabel'>
<meta name='author' content='fabel (at) sudop (dot) cz'>
</head>
<title>Bravo II - directory list</title>
<link rel='shortcut icon' href='images/iso.ico'>
<style>
.left {position:relative;left:-5}
.small {font-size:10px}
</style>
<h1>Obsah adresáøe '".$_GET['dataPreview']."'</h1>
<code class='small'>";
  $oldRow = $dataInfo[0];
  foreach ($dataInfo as $row)
   {
    echo createTree($oldRow,$row);
    $oldRow = $row;
   }
  echo createTree($oldRow,"/");
  echo "</code>";
  if ($GLOBALS['badExtension']>0)
   {
    echo "<font color='red'>Poèet souborù s podezøelými pøíponami: ".$GLOBALS['badExtension']."</font><br>";
   }
  echo "</html>";
 }

if (isset($_GET['isoPreview']))
 {
  if (file_exists($GLOBALS['bravoPrepare'].'/'.$_GET['isoPreview']))
   {
    $sourceFile = $GLOBALS['bravoPrepare'].'/'.$_GET['isoPreview'];
   }
  else
   {
    if (file_exists($GLOBALS['bravoRobot'].'/'.$_GET['isoPreview']))
     {
      $sourceFile = $GLOBALS['bravoRobot'].'/'.$_GET['isoPreview'];
     }
    else
     {
      if (file_exists($GLOBALS['bravoInput'].'/'.$_GET['isoPreview']))
       {
        $sourceFile = $GLOBALS['bravoInput'].'/'.$_GET['isoPreview'];
       }
     }
   }
//  exec("isoinfo -i '$sourceFile' -lJR |grep -v '\.\+ $' | grep -v '^$'",$isoInfo);
//  exec("isoinfo -i '$sourceFile' -fJR |sort ",$isoInfo);
  exec("isoinfo -i '$sourceFile' -fR |sort ",$isoInfo);

  echo "
<html>
<head>
<meta http-equiv='Content-Type' content='text/html; charset=iso-8859-2'>
<meta name='description' content='version 0.1.0'>
<meta name='copyright' content='Copyright (c) 2007 David Fabel'>
<meta name='author' content='fabel (at) sudop (dot) cz'>
</head>
<title>Bravo II - ISO preview</title>
<link rel='shortcut icon' href='images/iso.ico'>
<style>
.left {position:relative;left:-5}
.small {font-size:10px}
</style>
<h1>Obsah ISO souboru '".$_GET['isoPreview']."'</h1>
<code class='small'>";
  $oldRow = $isoInfo[0];
  foreach ($isoInfo as $row)
   {
    echo createTree($oldRow,$row);
    $oldRow = $row;
   }
  echo createTree($oldRow,"/");
  echo "</code>
</html>
";
 }

if (isset($_GET['imgPreview']))
 {
  if (file_exists($GLOBALS['bravoPrepare'].'/'.$_GET['imgPreview']))
   {
    $sourceFile = $GLOBALS['bravoPrepare'].'/'.$_GET['imgPreview'];
   }
  else
   {
    if (file_exists($GLOBALS['bravoRobot'].'/'.$_GET['imgPreview']))
     {
      $sourceFile = $GLOBALS['bravoRobot'].'/'.$_GET['imgPreview'];
     }
    else
     {
      if (file_exists($GLOBALS['bravoInput'].'/'.$_GET['imgPreview']))
       {
        $sourceFile = $GLOBALS['bravoInput'].'/'.$_GET['imgPreview'];
       }
     }
   }


  $previewFile = 'images/bravoTmp/'.$_GET['imgPreview'].'.png';
  $size = sprintf("%u", filesize($sourceFile));
  $imgXY = getIMGsize($sourceFile);
  $imgX = ereg_replace("x.*","",$imgXY);
  $imgY = ereg_replace(".*x","",$imgXY);
  $imgScale = $imgY/$imgX;
  if (file_exists($sourceFile) && !file_exists($previewFile))
   {
    logMsg(3,"convert '$sourceFile' -resize ".($imgScale*800)."x".($imgScale*800)." '$previewFile'");
    shell_exec("convert '$sourceFile' -resize ".($imgScale*800)."x".($imgScale*800)." '$previewFile'");
   }
  while (!file_exists($previewFile))
   {
    sleep(5);
   }

  echo "<html>
<head>
<meta http-equiv='Content-Type' content='text/html; charset=iso-8859-2'>
<meta name='description' content='version 0.1.0'>
<meta name='copyright' content='Copyright (c) 2007 David Fabel'>
<meta name='author' content='fabel (at) sudop (dot) cz'>
</head>
<title>Bravo II - image preview</title>
<link rel='shortcut icon' href='images/iso.ico'>
<body>
<center>
<h1>Obrázek '".$_GET['imgPreview']."'</h1>
Velikost: ".humanSize($size).", rozmìr: <span ".($imgScale!=1?"style='background-color:#ff0000'":"").">$imgXY</span>
<br>
<br>
<img src='images/mustr.png' width='800px' height='800px' style='position:relative;z-index:1'><br
><img src='".$previewFile."' border='1' style='position:relative;top:-800px'>
</center>
</body>
</html>
";
//<!--><img src='tmp/".$_GET['imgPreview']."' border='1' style='position:relative;top:-800px'>-->
  exit;
 }

if (isset($_GET['mkisofsOutput']))
 {
  if (file_exists($GLOBALS['bravoTmp']."/.".$_GET['mkisofsOutput']."-mkisofs.log"))
   {
    $fd=fopen ($GLOBALS['bravoTmp']."/.".$_GET['mkisofsOutput']."-mkisofs.log", "r");
    while (!feof ($fd))
     {
      echo ereg_replace("\n","<br>",fgets($fd, 4096));
     }
    fclose($fd);
   }
  exit;
 }

if (isset($_GET['splitMkisofsOutput']))
 {
  if (file_exists($GLOBALS['bravoTmp']."/.".$_GET['splitMkisofsOutput']."-split.log"))
   {
    $fd=fopen ($GLOBALS['bravoTmp']."/.".$_GET['splitMkisofsOutput']."-split.log", "r");
    while (!feof ($fd))
     {
      echo ereg_replace("\n","<br>",fgets($fd, 4096));
     }
    fclose($fd);
   }
  exit;
 }

# change media
if (isset($_POST['mediaSources']) && ($_POST['mediaSources']=="Ulo¾"))
 {
  $fd=fopen ($GLOBALS['generalPlace']."/bravoSourceMedia", "w");
  fwrite ($fd,"# soubor aktualniho obsazeni leveho a praveho zdroje

leftSource=".($_POST['sourceLeft']!="Special"?$_POST['sourceLeft']:$_POST['specialLeft'])."
rightSource=".($_POST['sourceRight']!="Special"?$_POST['sourceRight']:$_POST['specialRight'])."
");
  fclose ($fd);
  include("index.php");
  exit;
 }

$file=$_POST['file'];
$image=$_POST['image'];
if ($file=="bez dat")
 {
  unset($file);
 }
if ($image=="bez obrázku")
 {
  unset($image);
 }

# input to prepare
if (isset($_POST['dataSources']) && $_POST['dataSources']=="Dále")
 {
  $projectName=((isset($_POST['projectName'])&&($_POST['projectName']!=''))
              ?$_POST['projectName']
              :(isset($image)?getName($image):getName($file)));
  logMsg(3,"Sestavene jmeno projektu: '$projectName'");
  if ($projectName!="")
   {
    $medium=strtolower($_POST['medium']=="Special"?$specialName:$_POST['medium']);
    logMsg(3,"Pouzite medium: '$medium'");
    $copies=isset($_POST['copies'])&&$_POST['copies']>0?$_POST['copies']:1;
    logMsg(3,"Pouzite kopie: '$copies'");
    $quality=isset($_POST['quality'])&&$_POST['quality']>=0?$_POST['quality']:0;
    logMsg(3,"Pouzite kvalita: '$quality'");

    if (isset($file) && isset($image) && ($file!="bez dat") && ($image!="bez obrázku"))
     {
      logMsg(3,"Budeme pripravovat projekt s daty: '$file' i obrazkem: '$image");
      if (detectFileType($GLOBALS['bravoInput']."/$file")=='iso')
       {
        logMsg(3,"Bude to jeden ISO file: '$file'");
        moveFile($GLOBALS['bravoInput']."/$file",$GLOBALS['bravoPrepare']."/$projectName".createNewExtension($GLOBALS['bravoInput']."/$file",$medium,$copies,$quality));
       }

      if (detectFileType($GLOBALS['bravoInput']."/$file")=='data')
       {
        logMsg(3,"Bude to jeden DATA file: '$file', s attributy: '$medium' '$copies' '$quality'");
//        convertData2iso($file,$projectName.createNewExtension($GLOBALS['bravoInput']."/$file",$medium,$copies,$quality),isset($_POST['divide']),$medium,$image,$projectName.createNewExtension($GLOBALS['bravoInput']."/$image",$medium,$copies,$quality));
        convertData2iso($file,$projectName,isset($_POST['divide']),$medium,$copies,$quality);
       }
      logMsg(3,"Budeme pridavat do projektu obrazek: '$image'");
      $image=convert2pnm($GLOBALS['bravoInput']."/$image");
      logMsg(3,"Obrazek byl automaticky zkonvertovan na: '$image'");
//      moveFile($GLOBALS['bravoInput']."/$image",$GLOBALS['bravoPrepare']."/$projectName".createNewExtension($GLOBALS['bravoInput']."/$image",$medium,$copies,$quality));
      moveFile($image,$GLOBALS['bravoPrepare']."/$projectName".createNewExtension($image,$medium,$copies,$quality));
     }
    else
     {
      if (isset($file) && $file!="bez dat")
       {
        logMsg(3,"Budeme pripravovat projekt s daty: '$file'".getFileType($file));
        if (detectFileType($GLOBALS['bravoInput']."/$file")=='iso')
         {
          logMsg(3,"Bude to jeden ISO file: '$file");
          moveFile($GLOBALS['bravoInput']."/$file",$GLOBALS['bravoPrepare']."/$projectName".createNewExtension($GLOBALS['bravoInput']."/$file",$medium,$copies,$quality));
         }

        if (detectFileType($GLOBALS['bravoInput']."/$file")=='data')
         {
          logMsg(3,"Bude to jeden DATA file: '$file', s attributy: '$medium' '$copies' '$quality'");
//          convertData2iso($file,$projectName.createNewExtension($GLOBALS['bravoInput']."/$file",$medium,$copies,$quality),isset($_POST['divide']),$medium);
          convertData2iso($file,$projectName,isset($_POST['divide']),$medium,$copies,$quality);
         }
       }
      if (isset($image) && $image!="bez obrázku")
       {
        logMsg(3,"Budeme pripravovat projekt s obrazkem: '$image'");
        $image=convert2pnm($GLOBALS['bravoInput']."/$image");
        logMsg(3,"Obrazek byl automaticky zkonvertovan na: '$image'");
//        moveFile($GLOBALS['bravoInput']."/$image",$GLOBALS['bravoPrepare']."/$projectName".createNewExtension($GLOBALS['bravoInput']."/$image",$medium,$copies,$quality));
        moveFile($image,$GLOBALS['bravoPrepare']."/$projectName".createNewExtension($image,$medium,$copies,$quality));
       }
     }
   }
  include("index.php");
  exit;
 }

# load file
if (isset($_POST['loadFile']) && $_POST['loadFile']=="Naèíst")
 {
  logMsg(3,"Budeme nacitat soubor '".$_FILES['userFile']['name']."'");
  if (is_uploaded_file($HTTP_POST_FILES['userFile']['tmp_name']))
   {
    copyFile($_FILES['userFile']['tmp_name'],$GLOBALS['bravoInput']."/".$_FILES['userFile']['name']);
   }
  include("index.php");
  exit;
 }

# edit (prepare to input)
if (isset($_POST['prepareEdit']) && $_POST['prepareEdit']!="")
 {
  $d = dir($GLOBALS['bravoPrepare']);
  while (false !== ($entry = $d->read()))
   {
    logMsg(3,"Testuji pro zaznam '$entry' jmeno: '".getName($entry)."' a edit: '".$_POST['prepareEdit']."' ");
    if (getName($entry)===$_POST['prepareEdit'])
     {
      logMsg(3,"Budeme presouvat soubor: '$entry'");
      $GLOBALS['editName']=getName($entry);
      $GLOBALS['editType']=getFileType($entry);
      $GLOBALS['editCopies']=getCopies($entry);
      $GLOBALS['editQuality']=getQuality($entry);
      $GLOBALS['editMedium']=getMedium($entry);
      moveFile($GLOBALS['bravoPrepare']."/$entry",$GLOBALS['bravoInput'].'/'.$GLOBALS['editName'].'.'.$GLOBALS['editType']);      
     }
   }
  include("index.php");
  exit;
 }

# delete in prepare
if (isset($_POST['prepareDelete']) && $_POST['prepareDelete']!="")
 {
  $d = dir($GLOBALS['bravoPrepare']);
  while (false !== ($entry = $d->read()))
   {
    if (getName($entry)===$_POST['prepareDelete'])
     {
      unlink($GLOBALS['bravoPrepare']."/$entry");
     }
   }
  include("index.php");
  exit;
 }

# delete in input
if (isset($_POST['inputDelete']) && $_POST['inputDelete']!="")
 {
  $d = dir($GLOBALS['bravoInput']);
  while (false !== ($entry = $d->read()))
   {
    if ($entry===$_POST['inputDelete'])
     {
      if (filetype($GLOBALS['bravoInput']."/$entry")=="dir")
       {
        rmdir_r($GLOBALS['bravoInput']."/$entry");
       }
      else
       {
        unlink($GLOBALS['bravoInput']."/$entry");
       }
     }
   }
  include("index.php");
  exit;
 }

# delete in robot
if (isset($_POST['robotDelete']) && $_POST['robotDelete']!="")
 {
  $d = dir($GLOBALS['bravoRobot']);
  while (false !== ($entry = $d->read()))
   {
    if (getName($entry)===$_POST['robotDelete'])
     {
      unlink($GLOBALS['bravoRobot']."/$entry");
     }
   }
  include("index.php");
  exit;
 }

# prepare to robot
if (isset($_POST['prepareToRobot']) && $_POST['prepareToRobot']!="")
 {
  $d = dir($GLOBALS['bravoPrepare']);
  while (false !== ($entry = $d->read()))
   {
    if (getName($entry)===$_POST['prepareToRobot'])
     {
      $name=getName($entry);
      $quality=getQuality($entry);
      $copies=$_POST['copies'];
      $medium=getMedium($entry);
      if (isset($_POST['burnAndTrash']))
       {
        moveFile($GLOBALS['bravoPrepare']."/$entry",$GLOBALS['bravoRobot']."/$name".createNewExtension($GLOBALS['bravoPrepare']."/$entry",$medium,$copies,$quality,$_POST['burnAndVeify']=="on"));
       }
      else
       {
        linkFile($GLOBALS['bravoPrepare']."/$entry",$GLOBALS['bravoRobot']."/$name".createNewExtension($GLOBALS['bravoPrepare']."/$entry",$medium,$copies,$quality,$_POST['burnAndVeify']=="on"));
       }
     }
   }
  include("index.php");
  exit;
 }

# prepare to robot and test
/*
if (isset($_POST['prepareToRobotTest']) && $_POST['prepareToRobotTest']!="")
 {
  $d = dir($GLOBALS['bravoPrepare']);
  while (false !== ($entry = $d->read()))
   {
    if (getName($entry)===$_POST['prepareToRobotTest'])
     {
      $name=getName($entry);
      $quality=getQuality($entry);
      $copies=$_POST['copies'];
      $medium=getMedium($entry);
      if (isset($_POST['burnAndTrash']))
       {
        moveFile($GLOBALS['bravoPrepare']."/$entry",$GLOBALS['bravoRobot']."/$name".createNewExtension($GLOBALS['bravoPrepare']."/$entry",$medium,$copies,$quality,true));
       }
      else
       {
        linkFile($GLOBALS['bravoPrepare']."/$entry",$GLOBALS['bravoRobot']."/$name".createNewExtension($GLOBALS['bravoPrepare']."/$entry",$medium,$copies,$quality,true));
       }
     }
   }
  include("index.php");
  exit;
 }
*/

# robot to prepare
if (isset($_POST['robotToPrepare']) && $_POST['robotToPrepare']!="")
 {
  $d = dir($GLOBALS['bravoRobot']);
  while (false !== ($entry = $d->read()))
   {
    if (getName($entry)===$_POST['robotToPrepare'])
     {
      $lstat=lstat($GLOBALS['bravoRobot']."/$entry");
      if ($lstat[3]==1)
       {
        moveFile($GLOBALS['bravoRobot']."/$entry",$GLOBALS['bravoPrepare']."/$entry");
       }
      else
       {
        unlink($GLOBALS['bravoRobot']."/$entry");
       }
     }
   }
  include("index.php");
  exit;
 }

# create image
if (isset($_POST['create']) && $_POST['create']=="Vytvoø")
 {
  include("create.php");
  exit;
 }
?>
