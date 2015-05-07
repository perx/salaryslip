<?php
include_once "session.php";
if($_SESSION['user']!="admin"&&$_SESSION['user']!="payslipadmin")
header("Location:index.php");
include_once "db_conf.php";
if($_POST['code'])
{
	$code=$_POST['code'];
	if($_POST['func']=="deactivate")
	{
		$query="Update employee_info set active='0' where `Employee Code`='$code'";
	}
	else if($_POST['func']=="activate")
	{
		$query="Update employee_info set active='1' where `Employee Code`='$code'";
	}
	else
	{
		exit("unformatted request");
	}
	$res=$mysqli->query($query);
	if($res)
	echo "success";
	else
	echo $mysqli->error;
}
else if($_POST['filecode'])
{
	if($_POST['func']=='delete')
	{
		$id=$_POST['filecode'];
		$query="Delete from excel_slips where id='$id'";
		$res=$mysqli->query($query) or die($mysqli->error);
		$query="Select location from excel_uploads where id='$id'";
		$res=$mysqli->query($query) or die($mysqli->error);
		$row=$res->fetch_assoc() or die($mysqli->error);
		if(is_file($row['location']))
		unlink($row['location']) or die("Could not delete file");
		$query="Delete from excel_uploads where id='$id'";
		$res=$mysqli->query($query) or die($mysqli->error);
		echo "success";
	}
	else if($_POST['func']=='sendapproval')
	{
		$id=$_POST['filecode'];
		$query="Update excel_uploads set status='1' where id='$id'";
		$res=$mysqli->query($query) or die($mysqli->error);
		echo "success";
	}
	else if($_POST['func']=='generate')
	{
		$id=$_POST['filecode'];
		$query="Select status from excel_uploads where id='$id'";
		$res=$mysqli->query($query) or die($mysqli->error);
		$row=$res->fetch_assoc();
		if($row['status']==2)
		{
			$query="INSERT INTO salary_slips(`Employee Code`, `sal_str_code`, `month`,`year`, `earn`, `earn_payable`, `ded`, `leaves`, `Designation`, `Department`, `Working/Paid Days`,`Release Date`) SELECT d.`Employee Code`, d.`sal_str_code`, d.`month`,d.`year`, d.`earn`, d.`earn_payable`, d.`ded`, d.`leaves`, d.`Designation`, d.`Department`, d.`Working/Paid Days`,d.`Release Date` FROM excel_slips d WHERE id = '$id'";
			$res=$mysqli->query($query) or die($mysqli->error);	
			$query="Delete from excel_slips where id='$id'";
			$res=$mysqli->query($query) or die($mysqli->error);
			$query="Select location from excel_uploads where id='$id'";
			$res=$mysqli->query($query) or die($mysqli->error);
			$row=$res->fetch_assoc() or die($mysqli->error);
			if(is_file($row['location']))
			unlink($row['location']) or die("Could not delete file");
			$query="Delete from excel_uploads where id='$id'";
			$res=$mysqli->query($query) or die($mysqli->error);
			echo "success";	
		}
	}
	else if($_POST['func']=="approve")
	{
		$id=$_POST['filecode'];
		$query="Update excel_uploads set status='2' where id='$id'";
		$res=$mysqli->query($query) or die($mysqli->error);
		echo "success";
	}
}
else if($_POST['notification'])
{
	if($_POST['func']=="delete")
	{
		$id=$_POST['id'];
		$query="delete from notifications where id='$id'";
		$res=$mysqli->query($query) or die($mysqli->error);
		echo "success";
	}
}
?>