<?php if (!isset($_POST['submit'])): ?>
<?php header("Location:generate.php")?>
<?php else: ?>
<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Generate Salary Slips</title>
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
</head>

<body>
<?php

/**	Error reporting		**/
error_reporting(E_ALL);
?>
<div id="panel_header"><a href="generate.php"><h2>Salary Slip Generator</h2></a>
<a href="signout.php"><img id="signout" src="images/exit.png" alt="sign out"/></a>
<a href="generate.php"><img id="home" src="images/home.png" alt="go home"/></a>
</div>
<div style="text-align:center;line-height:150%;display:inline-block;margin-top: 105px; width:100%;">
<div style="background:rgba(255,255,255,0.5);border-radius:10px;padding: 25px 100px;width:70%;display:inline-block;text-align: left;">
<?php
/**	Include path		**/
include_once 'gentable_all.php';
include_once 'db_conf.php';
include_once "email.php";
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . '/Classes/');
include 'PHPExcel/IOFactory.php';
$objReader = PHPExcel_IOFactory::createReaderForFile($_FILES['file']['tmp_name']);
$objPHPExcel = $objReader->load($_FILES['file']['tmp_name']);

//get structure from database
$query="Select * from salary_structure order by sal_str_code DESC LIMIT 1";
$res = $mysqli->query($query) or die($mysqli->error);
$row = $res->fetch_assoc();
$earn_db=$row['earn'];
$default_earn_db=$row['default_earn'];
$ded_db=$row['ded'];
$leaves_db=$row['leaves'];
global $sal_str_code;
$sal_str_code=$row['sal_str_code'];
mysqli_free_result($res);

//Iterate Workbook
//echo date('H:i:s') , " Iterate worksheets"."<br/>";
$worksheet=$objPHPExcel->getActiveSheet(); 
$cell=$worksheet->toArray();
//get max columns
$max_column=PHPExcel_Cell::columnIndexFromString($worksheet->getHighestColumn());
//get max rows
for($max_row=1;$worksheet->getCellByColumnAndRow(1,$max_row)->getValue()!="TOTAL";$max_row++)
{echo "";}
//echo $max_row;
//$head=[];$ded=[];$earn=[];$leaves=[];
//$emp=[];
//get headings
for($i=1;$i<$max_column;$i++)
{
	if($cell[6][$i]!=NULL)
	{
		if($cell[5][$i]=="DEDUCTIONS")
		$ded[$i]=$cell[6][$i];
		else if($cell[5][$i]=="EARNINGS")
		$earn[$i]=$cell[6][$i];
		else if($cell[5][$i]=="LEAVES")
		$leaves[$i]=$cell[6][$i];
		else if($cell[5][$i]=="EARNINGS PAYABLE")
		$earn_pay[$i]=$cell[6][$i];
		else
		$head[$cell[6][$i]]=$i;
	}
}
if(implode(";",$ded)!=$ded_db)
{
	echo "Incorrectly formatted file - Error in Deductions";
	exit();
}
else if(implode(";",$leaves)!=$leaves_db)
{
	echo "Incorrectly formatted file - Error in Leaves";
	exit();
}
else if(implode(";",$earn)!=$earn_db)
{
	echo "Incorrectly formatted file - Error in Allowances";
	exit();
}
else
{
	$dir="slips/uploads";
	if(!is_dir($dir))
	{
		mkdir($dir,0777);
	}
	$excel_file="slips/uploads/MSS".rand().".xlsx";
	while(is_file($excel_file))
	{
		$excel_file="slips/uploads/MSS".rand().".xlsx";
	}
	if(!move_uploaded_file($_FILES['file']['tmp_name'],$excel_file))
	{
		exit("Unable to move uploaded file");
	}
	$file_query="Insert into excel_uploads(`location`) values ('$excel_file')";
	$file_res=$mysqli->query($file_query);
	if(!$file_res)
	{
		exit("Could not upload file details ".$mysqli->error);
	}
	$emp['file_id']=$mysqli->insert_id;
	$total_salary_this_month=0;
	$not_in_total=get_not_in_total($earn,explode(";",$default_earn_db));
	$emp['gross']=0;$emp['total_ded']=0;$emp['gross_pay']=0;$emp['net_pay']=0;$emp['salary_in_hand']=0;
	echo ("<br/>");
	for($row_no=8;$row_no<$max_row;$row_no++)
	{
		//echo "<pre>";
		/*$emp['earn']=[];
		$emp['ded']=[];
		$emp['leaves']=[];
		$emp['empinfo']=[];*/
		//put values for an employee into emp array
		$total_emp_salary=0;
		foreach($head as $k=>$v)
		{
			$emp[$k]=$worksheet->getCellByColumnAndRow($v,$row_no)->getCalculatedValue();
		}
		foreach($ded as $k=>$v)
		{
			$ded_val=$worksheet->getCellByColumnAndRow($k,$row_no)->getCalculatedValue();
			$emp['ded'][$v]=$ded_val;
		}
		foreach($earn as $k=>$v)
		{
			$earn_val=$worksheet->getCellByColumnAndRow($k,$row_no)->getCalculatedValue();
			$emp['earn'][$v]=$earn_val;
		}
		foreach($earn_pay as $k=>$v)
		{
			$earn_payable_val=$worksheet->getCellByColumnAndRow($k,$row_no)->getCalculatedValue();
			$emp['earn_payable'][$v]=$earn_payable_val;
		}
		$emp['gross']=$emp['TOTAL'];
		$emp['gross_pay']=$emp['TOTAL PAYABLE'];
		$emp['total_ded']=$emp['Total Ded'];
		$emp['net_pay']=$emp['Net Pay'];
		$emp['salary_in_hand']=$emp['Salary in hand'];
		if($emp['Release Date']!="ON HOLD")
		$total_salary_this_month+=$emp['Salary in hand'];
		
		$total_emp_salary+=$emp['Salary in hand'];
	
		$emp['gross_pay']=number_format(round($emp['gross_pay']),2);
		$emp['gross']=number_format(round($emp['gross']),2);
		$emp['total_ded']=number_format($emp['total_ded'],2);
	
		$emp['salary_in_hand']=number_format(round($emp['salary_in_hand']),2);
		$emp['net_pay']=number_format(round($emp['net_pay']),2);
		foreach($leaves as $k=>$v)
		{
			$emp['leaves'][$v]=$worksheet->getCellByColumnAndRow($k,$row_no)->getCalculatedValue();
		}
		//receive employee info from database using code
		$code=$emp['Employee Code'];
		//receive month year
		$emp['empinfo']['Month']=date('F Y', strtotime($_POST['month']));
		
		if(trim($code)=="")
		{
			echo("Please enter employee code for ".$emp['Name of the Employee'])."<br/>";
			continue;
		}
		
		
		$query="SELECT `Name of the Employee`, `Designation`, `Department`, `PF Account No.`, `Date of Joining`, `Salary Start Date`,`email`,`active` FROM `employee_info` WHERE `Employee Code`='$code'";
		$res = $mysqli->query($query) or die($mysqli->error);
		if($res->num_rows<1)
		{
			echo("Couldn't find employee ".$code)."<br/>";
			continue;
		}
		$row = $res->fetch_assoc();
		if($row['active']==0)
		{
			echo("Employee Deactivated ".$code)."<br/>";
			continue;
		}
		$emp['Designation']=$row['Designation'];
		$emp['Name of the Employee']=$row['Name of the Employee'];
		$emp['email']=$row['email'];
		$emp['empinfo']['Department']=$row['Department'];
		$emp['empinfo']['PF Account No.']=$row['PF Account No.'];
		$emp['empinfo']['Date of Joining']=date('d-M-Y',strtotime($row['Date of Joining']));
		$emp['empinfo']['Salary Start Date']=date('d-M-Y',strtotime($row['Salary Start Date']));
		mysqli_free_result($res);
		
		//enter arrays to be passed
		$sess['emp']=$emp;
		$sess['leaves']=$leaves;
		$sess['ded']=$ded;
		$sess['earn']=$earn;
		$sess['status']=1;
		//echo "<pre>";
		//print_r($emp);
		//echo "</pre>";
		//call generating function
		$sucess=gen($sess,true);
		if($sucess!=NULL)
		{
			$year=date('Y',strtotime($emp['empinfo']['Month']));
			$slip_query="Select `earn_payable`,`ded` from salary_slips where `Employee Code`='$code' AND year='$year'";
			$slip_res=$mysqli->query($slip_query);
			while($slip_row=$slip_res->fetch_assoc())
			{
				$total_emp_pay=explode(";",$slip_row['earn_payable']);
				foreach($total_emp_pay as $v)
				$total_emp_salary+=floatval(str_replace(",","",$v));
				$total_emp_ded=explode(";",$slip_row['ded']);
				foreach($total_emp_ded as $v)
				$total_emp_salary-=floatval(str_replace(",","",$v));
			}
			if($total_emp_salary>=150000)
			{
				$str=$code." - ".$emp['Name of the Employee']." = ".$total_emp_salary."(Employee salary above 1,50,000 for this year)";
				$notifications[]=$str;
				$notify_query="Insert into notifications(data) Values ('$str')";
				$notify_res=$mysqli->query($notify_query);
			}
			else
			{
				$str=$code." - ".$emp['Name of the Employee']." = ".$total_emp_salary;
				$notifications[]=$str;
			}
		}
		else
		{
			echo ($emp['Name of the Employee']." = Error<br/>");
			exit();
		}
	}
	$str="Total Salary in hand for ".date('F Y', strtotime($_POST['month']))."= ".$total_salary_this_month;
	$notifications[]=$str;
	$notify_query="Insert into notifications(data) Values ('$str')";
	$notify_res=$mysqli->query($notify_query);
	mysqli_close($mysqli);
	$msg="Notifications :";
	foreach($notifications as $v)
	{
		$msg.="<br>".$v;
	}
	$to_email="jaspreet.cityinnovates@gmail.com";
				
	$from='hr.cityinnovates@gmail.com';
	$fromname='HR Admin';
	$subject='Notifications from Salary Slip Management';
	$res=send_email($from,$fromname,$to_email,$subject,$msg,false);
	echo "Successfully uploaded excel file. Click <a href='generate.php'>here</a> to continue.";
	//echo (count($sucess));
}
?>
</div>
</div>
<?php endif ?>
</body>
</html>