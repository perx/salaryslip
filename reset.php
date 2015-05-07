<?php
if(isset($_GET['token']))
{
	$token=$_GET['token'];
	include "db_conf.php";
	$query="Select id,time from recovery where token='$token'";
	$res=$mysqli->query($query) or die($mysqli->error);
	if($res->num_rows==0) die("Token not valid");
	$row=$res->fetch_assoc();
	$time=time()-$row['time'];
	if($time>0&&$time<3600)
	{
?>
<?php if($_POST['submit']!="Set New Password") {?>
<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="bodystyle.css" type="text/css"/>
<link rel="stylesheet" href="indexstyle.css" type="text/css"/>
<title>Change Password</title>
<?php include_once "favicon.html"; ?>
</head>
<script>
function validate()
{
	if(document.getElementsByName('new_password')[0].value!="")
	{
		if(document.getElementsByName('new_password')[0].value==document.getElementsByName('new_password2')[0].value)
		return true;
		else
		alert("New password and confirm password do not match");
		return false;
	}
	else
	alert("Fields cannot be empty");
	return false;
}
</script>
<body>
<div id="login">
	<div id="login_form">
    <a id="head" href="index.php"><h2>Salary Slip Management</h2></a>
<?php if(isset($_GET['error']))
	  echo "<br/><span class='err'>Wrong Username or password</span><br/>";
?>
      <form action="" onSubmit="return validate();" method="POST">
      <div>
      <input type="hidden" name="user" value="<?php echo $row['id'] ;?>"/>
      <span class="icon-key"><input type="password" name="new_password" class="in_data" placeholder="New Password"/></span><br/>
      <span class="icon-key"><input type="password" name="new_password2" class="in_data" placeholder="Retype New Password"/></span><br/>
      </div>
      <input type="submit" id="submit_button" name="submit" value="Set New Password"/>
      </form>

	</div>
</div>
</body>
</html>
<?php } //inner if ends 
		else //form posted
		{
		 	include "db_conf.php";
			$user=$_POST['user'];
			$query="delete from recovery where id='$user'";
			$res=$mysqli->query($query);
			if(!$res)
			die("Problem updating token in database");
			$pass=$_POST['new_password'];
			$hash=md5($pass);
			if(!($stmt=$mysqli->prepare("Update employee_login set password=?,pwd_text=? where username=?")))
			die($stmt->error);
			if(!($stmt->bind_param("sss",$hash,$pass,$user)))
			die($stmt->error);
			$res=$stmt->execute();
			if(!$res)
			die("Error updating password");
			header("location:index.php");
		} //inner else ends
?>
<?php
	}	//outer if ends
	else
	{
		$user=$row['id'];
		$query="Delete from recovery where id='$user'";
		$res=$mysqli->query($query) or die($mysqli->error);
		die("Token expired. Please try again.");
	}
}
?>