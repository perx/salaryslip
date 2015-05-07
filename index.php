<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="bodystyle.css" type="text/css"/>
<link rel="stylesheet" href="indexstyle.css" type="text/css"/>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
<link rel="stylesheet" href="datepicker/css/bootstrap.css" type="text/css">
<script type="text/javascript" src="datepicker/zebra_datepicker.js"></script>
<script>
$(document).ready(function(){
    $('#date').Zebra_DatePicker({
  format: 'm/Y',
  view: 'years',
  always_visible:$('#calendar')
});
});
</script>

<title>Employee Management System</title>
<?php include_once "favicon.html"; ?>
</head>
<body>
<div id="login">
	<div id="login_form">
    <a id="head" href="index.php"><h2>Salary Slip Management</h2></a>
<?php if(!isset($_GET['forgot'])) { ?>
<?php if(isset($_GET['error']))
	  echo "<br/><span class='err'>Wrong Username or password</span>";
?>
      <form action="login.php" method="POST">
      <div id="input_fields">
      <span class="icon-user"><input type="text" name="username" class="in_data" autofocus placeholder="Employee Code"/></span><br/>
      <span class="icon-key"><input type="password" name="password" class="in_data" placeholder="Password"/></span><br/>
      <input type="hidden" id="date" name="date"/>
      </div>
      <div id="calendar"></div>
      <a href="index.php?forgot" class="forgot">I forgot my password</a><br/>
      <a href="changepwd.php" class="forgot">Change password</a><br/>
      <input type="submit" id="submit_button" name="submit" value="Login"/>
      </form>

<?php } //outter if ends

else
{
		if(!isset($_POST['submit'])=='Request new password')
		{ 
?>
	  <form action="index.php?forgot" method="POST">
      <span class="icon-user"><input type="text" name="username" class="in_data" autofocus placeholder="Employee Code"/></span><br/>
      <input type="submit" id="submit_button" name="submit" value="Request new password" style="margin-top:30px"/>
      </form>
<?php 	}//inner if ends 

		else
		{
			$user=$_POST['username'];
			//check if user exists
			include "db_conf.php";
			$query="Select * from `employee_login` where username='$user'";
			$res=$mysqli->query($query);
			if($res)
			{
				if($res->num_rows>=1)
				{
					$query="Select `email` from `employee_info` where `Employee Code`='$user'";
					$res=$mysqli->query($query);
					if($res)
					{
						$row=$res->fetch_assoc();
						$to_email=$row['email'];
						$time=time();
						$token=sha1(md5($user.$time.md5($salt)));
						$query="Insert into recovery(id, token, time) values ('$user','$token','$time')";
						$res=$mysqli->query($query);
						if($res)
						{
							//$msg="Dear $user, your new password is ".$pass;
							$salt="forgotmypassword";
							$msg="Dear $user, click on the link below to change your password : http://cityeduhub.com/salaryslip/ssm/reset?token=$token";
							include_once "email.php";
							send_email("hr.cityinnovates@gmail.com","Salary Slip System",$to_email,"Password Recovery",$msg,false);
							echo "<h3>Thanks $user, you will receive a recovery email shortly</h3>";
						}
						else
						echo "<h2>Database Error 3: ".$mysqli->error."</h2>";
					}
					else
					echo "<h2>Database Error 2: ".$mysqli->error."</h2>";
				}
				else
				echo "<h3>No such user exists. Please try again.</h3>";
			}
			else
			echo "<h2>Database Error 1: ".$mysqli->error."</h2>";
			//if not echo "<h3>No such user exists. Please try again.</h3>"
			//send request to HR
		}
?>

<?php } //outter else ends?>
    </div>
</div>

</body>
</html>
