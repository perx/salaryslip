<?php
include_once 'session.php';
//use $_SESSION['sal_str_code']
include_once 'gentable_all.php';
?>
<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<title>Generate Salary Slips</title>
<link rel="stylesheet" href="generate_head.css" type="text/css"/>
</head>
<body>
<?php include_once 'header.php'; ?>
<div style="text-align:center;line-height:150%;">
<h3>Succesfully generated Salary Slips for :</h3>
<?php
if(isset($_POST))
{
	$sess['ded']=$_SESSION['ded'];
	$sess['earn']=$_SESSION['earn'];
	$sess['leaves']=$_SESSION['leaves'];
	global $sal_str_code;
	$sal_str_code=$_SESSION['sal_str_code'];
	foreach($_POST as $emp)
	{
		$sess['emp']=$emp;

		$sucess=gen($sess,true,true);
		if($sucess!=NULL)
		{
			echo ($emp['Name of the Employee'])." -> <a href='".$sucess."'>".$sess['emp']['empinfo']['Month']."</a><br/>";
		}
		else
		{
			echo ($emp['Name of the Employee']." = Error<br/>");
		}
		
	}
}
?>
</div>
</body>
</html>