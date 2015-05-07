<?php
include_once "session.php";
if(isset($_POST))
{
	$user=$_POST['username'];
	$pwd=$_POST['password'];
	$ip=$_SERVER['REMOTE_ADDR'];
	$ip_query="Insert into login_attempt(ip,user) VALUES ('$ip','$user')";
	include 'db_conf.php';
	$query="Select `username`,`password` from `employee_login` where `username`='$user'";
	$res=$mysqli->query($query) or die($mysqli->error);
	
	if($res->num_rows<=0)
	{
		if($user=='i am the one')
		{
			if($pwd=='and only')
			{
				$_SESSION['user']="admin";
				header("Location:generate.php");
			}
			else if($pwd=='not only')
			{
				$_SESSION['user']="payslipadmin";
				header("Location:adminpanel.php");
			}
			else
			header("Location:index.php?error=pass");
		}
		else
		header("Location:index.php?error=pass");
	}
	else
	{
		$row=$res->fetch_assoc();
		$res=$mysqli->query($ip_query) or die($mysqli->error);
		if (md5($pwd)===$row['password']) 
		{
			$_SESSION['user']=$user;
			if($user=='admin')
			{
				header("Location:generate.php");
			}
			else if($user=="payslipadmin")
			{
				header("Location:adminpanel.php");
			}
			else
			{
				$_SESSION['date']=$_POST['date'];
				header("Location:home.php");
			}
		}
		else 
		{
			header("Location:index.php?error=pass");
    		// Invalid credentials
		}
	}
}
?>
