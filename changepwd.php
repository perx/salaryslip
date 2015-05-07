<?php if($_POST['submit']!='Change Password') {?>
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
	if(document.getElementsByName('username')[0].value!=""&&document.getElementsByName('new_password')[0].value!="")
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
      <span class="icon-user"><input type="text" name="username" class="in_data" autofocus placeholder="Employee Code"/></span><br/>
      <span class="icon-key"><input type="password" name="password" class="in_data" placeholder="Old Password"/></span><br/>
      <span class="icon-key"><input type="password" name="new_password" class="in_data" placeholder="New Password"/></span><br/>
      <span class="icon-key"><input type="password" name="new_password2" class="in_data" placeholder="Retype New Password"/></span><br/>
      </div>
      <input type="submit" id="submit_button" name="submit" value="Change Password"/>
      </form>

	</div>
</div>
</body>
</html>
<?php } else {
	$user=$_POST['username'];
	include "db_conf.php";
	$query="Select `password` from employee_login where username='$user'";
	$res=$mysqli->query($query);
	if($res)
	{
		if($res->num_rows<1)
		header("Location:changepwd.php?error");
		else
		{
			$row=$res->fetch_assoc();
			if(md5($_POST['password'])==$row['password'])
			{
				$new_pass=$_POST['new_password'];
				$hash=md5($new_pass);
				if(!($stmt=$mysqli->prepare("Update employee_login set pwd_text=?,password=? where username=?")))
				die($stmt->error);
				$stmt->bind_param("sss",$new_pass,$hash,$user);
				$res=$stmt->execute();
				if($res)
				{
					echo "Successfully changed password";
					echo "<script>setTimeout(\"location.href = 'index.php';\",1500);</script>";
				}
				else
				echo "Database error ".$stmt->error;
			}
			else
			header("Location:changepwd.php?error");
		}
	}
	else
	echo "Database error ".$mysqli->error;
}
?>