<?php
include_once "session.php";
?>
<?php if($_SESSION['user']!="admin")
header("Location:index.php"); ?>
<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="bodystyle.css" type="text/css"/>
<link rel="stylesheet" href="accordion.css" type="text/css"/>
<?php include_once "favicon.html"; ?>
<title>
<?php
if (isset($_GET['edit']))
echo "Edit Employee Information";
else
echo "Add New Employee";
?>
</title>
</head>
<body>
<div id="panel_header"><a href="generate.php"><span>Salary Slip Generator</span></a>
<a href="signout.php"><img id="signout" src="images/exit.png" alt="sign out"/></a>
<a href="generate.php"><img id="home" src="images/home.png" alt="go home"/></a>
</div>
<?php if(!isset($_POST['submit'])){
if(isset($_GET['edit']))
{
	include 'db_conf.php';
	$code=$_GET['edit'];
	$query="Select * from employee_info where `Employee Code`='$code'";
	$res=$mysqli->query($query);
	$row=$res->fetch_assoc();
	$emp=$row;
}
?>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
<script>
$(document).ready(function() {
    function close_accordion_section() {
        $('.accordion .accordion-section-title').removeClass('active');
        $('.accordion .accordion-section-content').slideUp(300).removeClass('open');
    }
 
    $('.accordion-section-title').click(function(e) {
        // Grab current anchor value
        var currentAttrValue = $(this).attr('href');
 
        if($(e.target).is('.active')) {
            close_accordion_section();
        }else {
            close_accordion_section();
 
            // Add active class to section title
            $(this).addClass('active');
            // Open up the hidden content panel
            $('.accordion ' + currentAttrValue).slideDown(300).addClass('open'); 
        }
 
        e.preventDefault();
    });
	$('#gen').trigger('click');
});
function validate()
{
	var flag_empty=true;
	var flag_val=true;
	var flag_val2=true;
	$('input').each(function() {
		if($(this).val()=="")
		{
			alert(this.name+" cannot be left blank");
			flag_empty=false;
			return false;
		}
    });
	if(flag_empty)
	{
		$('#accordion-earn input').each(function() {
			if(isNaN($(this).val()))
			{
				alert(this.name+" must be numerical");
				flag_val=false;
				return false;
			}
    	});
	}
	if(flag_val)
	{
		$('#accordion-ded input').each(function() {
			if(isNaN($(this).val()))
			{
				alert(this.name+" must be numerical");
				flag_val2=false;
				return false;
			}
    	});	
	}
	if(flag_empty&&flag_val&&flag_val2)
	return true;
	
	return false;
}
</script>
<?php
if (isset($_GET['edit']))
echo "<h3>Edit Employee Information</h3>";
else
echo "<h3>Add New Employee</h3>";
?>
<form action="" method="post" onSubmit="return validate();">
<div class="accordion">
<div class="accordion-section">
<a class="accordion-section-title" id='gen' href="#accordion-general">General Information</a>
<div id="accordion-general" class="accordion-section-content">
<span>Employee Code</span><br/>
<input type='text' name='Employee Code' id='Employee Code' required <?php if(isset($emp)) echo "readonly";?> value='<?php if(isset($emp)) echo $emp['Employee Code'];?>'/><br/>
<span>Name of the Employee</span><br/>
<input type='text' name='Name of the Employee' id='Name of the Employee' value='<?php if(isset($emp)) echo $emp['Name of the Employee'];?>'/><br/>
<span>Department</span><br/>
<input type='text' name='Department' id='Department' value='<?php if(isset($emp)) echo $emp['Department'];?>'/><br/>
<span>Designation</span><br/>
<input type='text' name='Designation' id='Designation' value='<?php if(isset($emp)) echo $emp['Designation'];?>'/><br/>
<span>PF Account No.</span><br/>
<input type='text' name='PF Account No.' id='PF Account No.' value='<?php if(isset($emp)) echo $emp['PF Account No.'];?>'/><br/>
<span>Date of Joining</span><br/>
<input type='date' name='Date of Joining' id='Date of Joining' value='<?php if(isset($emp)) echo $emp['Date of Joining'];?>'/><br/>
<span>Salary Start Date</span><br/>
<input type='date' name='Salary Start Date' id='Salary Start Date' value='<?php if(isset($emp)) echo $emp['Salary Start Date'];?>'/><br/>
<span>Email Address</span><br/>
<input type='email' name='email' id='email' required value='<?php if(isset($emp)) echo $emp['email'];?>'/><br/>
</div>

<?php
include 'db_conf.php';
$query="Select `earn`,`ded`,`sal_str_code` from salary_structure order by sal_str_code DESC LIMIT 1";
$res=$mysqli->query($query) or die("Error accessing database");
$row=$res->fetch_assoc();
$earn=explode(";",$row['earn']);
$ded=explode(";",$row['ded']);
$sal_str_code=$row['sal_str_code'];
echo "<input type='hidden' name='sal_str_code' value='$sal_str_code'/>";
if(isset($_GET['edit']))
{
	$emp_earn=array_combine($earn,explode(";",$emp['earn']));
	$emp_ded=array_combine($ded,explode(";",$emp['ded']));
}
?>
<a class="accordion-section-title" href="#accordion-earn">Allowances</a>
<div id="accordion-earn" class="accordion-section-content">
<?php
foreach($earn as $v)
{
	echo "<span>$v</span><br/>";
	echo "<input type='text' id='earn[$v]' name='earn[$v]'";
	if(isset($emp))
		echo "value='$emp_earn[$v]'";
	else
		echo "value='0'";
	echo " /><br/>";
}
?>
</div>

<a class="accordion-section-title" href="#accordion-ded">Deductions</a>
<div id="accordion-ded" class="accordion-section-content">
<?php
foreach($ded as $v)
{
	echo "<span>$v</span><br/>";
	echo "<input type='text' id='ded[$v]' name='ded[$v]'"; 
	if(isset($emp))
		echo "value='$emp_ded[$v]'";
	else
		echo "value='0'";
	echo "/><br/>";
}
?>
</div>
<?php if(!isset($_GET['edit'])){ ?>
<a class="accordion-section-title" href="#accordion-login">Login Information</a>
<div id="accordion-login" class="accordion-section-content">
<span>Login Password</span><br/>
<input type='text' name='password'/>
<br/>
</div>
<?php } ?>
</div>
</div>
<input type='submit' name='submit' class="button submit_button" value='<?php if(isset($_GET['edit'])) echo 'Edit'; else echo 'Submit'; ?>'/>
</form>
<?php } else {
	foreach($_POST as $k=>$v)
	{
		if(is_array($v))
		{
			$v=implode(";",$v);
		}
		$$k=$v;
	}
	include "db_conf.php";
	if(isset($_GET['edit']))
	{
		if(!$query=$mysqli->prepare("UPDATE `employee_info` SET `Name of the Employee`=?,`Department`=?,`Designation`=?,`PF Account No.`=?,`Date of Joining`=?,`Salary Start Date`=?,`email`=?,`earn`=?,`ded`=? where `Employee Code`=?"))
		die( "Statement could not be prepared: (" . $mysqli->errno . ") " . $mysqli->error."<br/>");
		$query->bind_param("ssssssssss",$Name_of_the_Employee,$Department,$Designation,$PF_Account_No_,$Date_of_Joining,$Salary_Start_Date,$email,$earn,$ded,$_GET['edit']);
	}
	else
	{
		if(!$query=$mysqli->prepare("INSERT INTO `employee_info`(`Employee Code`,`Name of the Employee`,`Department`,`Designation`,`PF Account No.`,`Date of Joining`,`Salary Start Date`,`email`,`earn`,`ded`,`sal_str_code`) VALUES(?,?,?,?,?,?,?,?,?,?,?)"))
		die( "Statement could not be prepared: (" . $mysqli->errno . ") " . $mysqli->error."<br/>");
		$query->bind_param("ssssssssssi",$Employee_Code,$Name_of_the_Employee,$Department,$Designation,$PF_Account_No_,$Date_of_Joining,$Salary_Start_Date,$email,$earn,$ded,$sal_str_code);
	}
	if (!$query->execute()) 
	{
    	die("Could not update/insert $Employee_Code into database: (" . $query->errno . ") " . $query->error."<br/>");
	}
	else
	{
		echo "<div style='display: inline-block;margin-top: 65px;margin-left: 20px;background: rgba(255,255,255,0.5);padding: 30px;border-radius: 10px;'><pre>";
		echo "Successfully added/updated Employee $Employee_Code<br/>";
		print_r($_POST);
		echo "</pre></div>";
		echo "<script>setTimeout(function(){location.href='generate.php';},2000);</script>";
	}
	if(!isset($_GET['edit']))
	{
		$hash=md5($password);
		$query="Insert into `employee_login`(`username`,`pwd_text`,`password`) VALUES('$Employee_Code','$password','$hash')";
		
		$res=$mysqli->query($query);
		if(!$res)
		{
			die("Could not insert $Employee_Code into login database: (" . $mysqli->errno . ") " . $mysqli->error."<br/>");
		}
		else
		{
			include_once "email.php";
			$to_email=$email;
			$from='hr.cityinnovates@gmail.com';
			$fromname='HR Admin';
			$subject='Password Recovery';
			$msg="Your password is $password";
			$res=send_email($from,$fromname,$to_email,$subject,$msg,$filename);
			if($res=="true")
			echo "Email sent to ".$to_email;
			else
			echo $res;
		}
	}
 } ?>
</body>
</html>
