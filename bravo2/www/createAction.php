<?php;
include("functions.php");

//phpInfo();
if (isset($_POST['toolText']))
 {
  include("create.php");
  exit;
 }

if (isset($_POST['picture_x']))
 {
  $text[1]="<div style='position:relative;left:".$_POST['picture_x']."px;top:".(-800+$_POST['picture_y'])."px'>".$_POST['text'].' '.$_POST['picture_x'].' '.$_POST['picture_y']."</div>";
  include("create.php");
  exit;
 }
exit;
?>
