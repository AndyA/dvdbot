<html>
<head>
<meta http-equiv='Content-Type' content='text/html; charset=iso-8859-2'>
<meta name='description' content='version 0.1.2'>
<meta name='copyright' content='Copyright (c) 2007 David Fabel'>
<meta name='author' content='fabel (at) sudop (dot) cz'>
<title>Bravo II - create image</title>
<link rel='shortcut icon' href='images/iso.ico'>
</head>

<body bgcolor='#eeffee'>
<?php;
include("functions.php");
?>
<center>
<form method='post' action='createAction.php' enctype='multipart/form-data'>
<table>
<tr>
<td valign='top'>
<input type='submit' name='toolText' value='T'>
</td>
<td>
<input type='image' name='background' src='images/mustr.png' width='800px' height='800px' style='position:relative;z-index:1'
><?php; echo $text[1];
?><input type='image' name='picture' src='images/xxxxxx-bod.gif' width='800px' height='800px' style='position:relative;left:-800px;z-index:2'>
</td>
</tr>
<?php;
if (isset($_POST['toolText']))
 {
  echo "<tr>
<td colspan='2'>
<textarea name='text'>Ahoj</textarea>
</td>
</tr>"; 
 }
?>
</table>
</form>
</center>
</html>
