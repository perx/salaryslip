<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="bodystyle.css" type="text/css"/>
<style>
#panel_header
{
	width: 100%;
}
#signout{margin-right: 8%;}
#home{margin-right:10px;}
#home,#signout{float: right;margin-top: 9px;}
#panel_header a{text-decoration:none;
margin-left: -15%;
color:white;}
#panel_header a h2{margin: 10px 0px;
display: inline-block;}
</style>
<?php if(!(isset($_POST['submit']))) { ?>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
<script>
$(document).ready(function(){
	$('.editable').click(function(e) {
		if(this.children.length==0)
        {
			var val=this.innerHTML;
			var name=this.previousElementSibling.previousElementSibling.innerHTML;
			this.innerHTML="<input type='text' name='"+name+"' id='"+val+"' value='"+val+"'/>";
		}
    });
});
</script>
<title>Edit Salary Slip</title>
</head>
<body>

<div id="panel_header"><a href="generate.php"><h2>Salary Slip Generator</h2></a>
<a href="signout.php"><img id="signout" src="images/exit.png" alt="sign out"/></a>
<a href="generate.php"><img id="home" src="images/home.png" alt="go home"/></a>
</div>
<div id="slip" style='margin-top:50px;background:#fff;display:inline-block'>
<form method='post' action=''>
<?php include_once $_GET['slip']; ?>
<input type='submit' name='submit' value='Submit'/>
</form>
</div>
<?php } else { ?>
<?php print_r($_POST); ?>
<?php } ?>
</body>
</html>
