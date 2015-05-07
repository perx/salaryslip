<?php
include_once 'gentable_all.php';
include_once "email.php";
function prepare_sess($slip,$status)
{
	include 'db_conf.php';
	//get salary structure
	$sal_str=$slip['sal_str_code'];
	$query="Select * from `salary_structure` where `sal_str_code`='$sal_str'";
	$res = $mysqli->query($query) or die($mysqli->error);
	$row=$res->fetch_Assoc();
	$sal_str=$row;
	
	//get employee info for sess
	$code=$slip['Employee Code'];
	$query="Select `Name of the Employee`,`email`,`PF Account No.`,`Date of Joining`,`Salary Start Date` from `employee_info` where `Employee Code`='$code'";
	$res = $mysqli->query($query) or die($mysqli->error);
	$row=$res->fetch_Assoc();
	$emp_info_db=$row;
	
	//set headings
	$sess['ded']=explode(";",$sal_str['ded']);
	$sess['earn']=explode(";",$sal_str['earn']);
	$sess['leaves']=explode(";",$sal_str['leaves']);
	
	//set values
	$emp['ded']=array_combine($sess['ded'],explode(";",$slip['ded']));
	$emp['earn']=array_combine($sess['earn'],explode(";",$slip['earn']));
	$emp['leaves']=array_combine($sess['leaves'],explode(";",$slip['leaves']));
	$emp['earn_payable']=array_combine($sess['earn'],explode(";",$slip['earn_payable']));
	
	//set total values
	//print_r($not_in_total);
	$emp['gross']=0;
	$emp['gross_pay']=0;
	$emp['net_pay']=0;	
	$emp['total_ded']=0;
	
	foreach($emp['earn'] as $v)
	{
		$emp['gross']+=$v;
	}
	foreach($emp['earn_payable'] as $k=>$v)
	{
		$emp['earn_payable'][$k]=floatval(str_replace(",","",$v));
		$emp['gross_pay']+=floatval(str_replace(",","",$v));
	}
	foreach($emp['ded'] as $v)
	{
		$emp['total_ded']+=$v;
	}
	$emp['net_pay']=$emp['gross_pay']-$emp['total_ded'];
	
	$emp['gross_pay']=number_format(round($emp['gross_pay']),2);
	$emp['gross']=number_format(round($emp['gross']),2);
	$emp['total_ded']=number_format($emp['total_ded'],2);
	
	$emp['salary_in_hand']=number_format(round($emp['net_pay']),2);
	$emp['net_pay']=number_format($emp['net_pay'],2);
	
	
	//set empinfo(dynamic details in salary slip)
	$emp['empinfo']['Department']=$slip['Department'];
	$emp['empinfo']['PF Account No.'] =$emp_info_db['PF Account No.'];
    $emp['empinfo']['Month'] = date('F Y', strtotime($slip['month']."/27/".$slip['year']));
    $emp['empinfo']['Date of Joining'] = date('d-M-Y',strtotime($emp_info_db['Date of Joining']));
    $emp['empinfo']['Salary Start Date'] = date('d-M-Y',strtotime($emp_info_db['Salary Start Date']));
	
	//set employee details
	$emp['Working/Paid Days']=$slip['Working/Paid Days'];
	$emp['Employee Code']=$slip['Employee Code'];
	$emp['Name of the Employee']=$emp_info_db['Name of the Employee'];
	$emp['Designation']=$slip['Designation'];
	$emp['email']=$emp_info_db['email'];
	
	$sess['emp']=$emp;
	//set if for user view or admin view
	$sess['status']=$status;
	//echo "<pre>";
	//print_r($sess);
	//echo "</pre>";
	return $sess;
}
function get_slip($date,$code,&$flag,&$status)
{
	$date=explode("/",$date);
	include 'db_conf.php';
	$query="Select * from `salary_slips` where `Employee Code`='$code' AND `year`='$date[1]' AND `month`='$date[0]'";
	$res = $mysqli->query($query) or die($mysqli->error);
	if($res->num_rows<=0)
	$flag=false;
	else
	{
		$row=$res->fetch_Assoc();
		$sess=prepare_sess($row,$status);
		$flag=true;
		return gen($sess,false,false);
	}
}
if($_POST['date']!="")
{
	if($_POST['func']=="approve")
	{
		include 'db_conf.php';
		$code=$_POST['code'];
		$date=$_POST['date'];
		$date=explode("/",$date);
		$query="Select * from `salary_slips` where `month`='$date[0]' AND `year`='$date[1]' AND `Employee Code`='$code'";
		$res = $mysqli->query($query) or die($mysqli->error);
		if($res->num_rows<1)
		echo "No such salary slip found";
		else
		{
			$row=$res->fetch_assoc();
			if($row['status']==0)
			{
				$query="Update `salary_slips` set `status`=1 where `month`='$date[0]' AND `year`='$date[1]' AND `Employee Code`='$code'";
				$res = $mysqli->query($query) or die($mysqli->error);
				if($res)
				echo "Successfully approved";
				else
				echo "Unsuccessful attempt";
			}
			else
			echo "Salary slip already approved";
		}
	}
	else if($_POST['func']=="getemail")
	{
		include 'db_conf.php';
		$code=$_POST['code'];
		$query="Select `email` from `employee_info` where `Employee Code`='$code'";
		$res = $mysqli->query($query) or die($mysqli->error);
		if($res->num_rows<1)
		echo "hr.cityinnovates@gmail.com";
		else
		{
			$row=$res->fetch_assoc();
			echo $row['email'];
		}
	}
	else if($_POST['func']=="emailall")
	{
		$code=$_POST['code'];
		//send email
		{
			$flag=false;
			if($_POST['choice']=="user")
			$status=0;
			else
			$status=1;
			$filename=get_slip($_POST['date'],$code,$flag,$status);
			if($flag==true)
			{
				if($_POST['choice']=="user")
				{
					include "db_conf.php";
					$query="Select `email` from `employee_info` where `Employee Code`='$code'";
					$res = $mysqli->query($query) or die($mysqli->error);
					if($res->num_rows<1)
					$to_email="hr.cityinnovates@gmail.com";
					else
					{
						$row=$res->fetch_assoc();
						$to_email=$row['email'];
					}
				}
				else
				$to_email=$_POST['to_email'];
				
				$from='hr.cityinnovates@gmail.com';
				$fromname='HR Admin';
				$subject='Salary Slip';
				$msg='Salary Slip';
				$res=send_email($from,$fromname,$to_email,$subject,$msg,$filename);
				if($res=="true")
				echo "Email sent to ".$to_email;
				else
				echo $res;
				
			}
			else
			echo "No salary slip slip found";
		}
	}
	else if($_POST['func']=="printall")
	{
		$flag=false;
		$status=1;
		$codes=explode(";",$_POST['list']);
		$fileall="slips/all.html";
		if(file_exists($fileall))
		unlink($fileall);
		$fileallhandler=fopen($fileall,'a');
		if($fileallhandler==NULL)
		{
			echo ("Cannot create file<br/>");
		}
		chmod($fileall,0777);
		$pagebreak="<br/><p style='page-break-after:always;'></p>";
		foreach($codes as $code)
		{
			$filename=get_slip($_POST['date'],$code,$flag,$status);
			if($flag==true)
			{
				if(($str=file_get_contents($filename)))
				fwrite($fileallhandler,$str);
				fwrite($fileallhandler,$pagebreak);
			}
		}
		fclose($fileallhandler);
		echo "<iframe id='sliphtml' src='$fileall' name='$status'></iframe>";
	}
	else if($_POST['func']=="downloadall")
	{
		$code=$_POST['code'];
		//generate slip
		{
			$flag=false;
			$status=1;
			$filename=get_slip($_POST['date'],$code,$flag,$status);
			if($flag==true)
			{
				$pdf=getpdf($filename);
				$zip = new ZipArchive();
				$filename = "slips/slips.zip";
				if ($zip->open($filename)!==TRUE)
					exit("cannot open <$filename>\n");
				if(!$zip->addFile($pdf))
					exit("file could not be added to zip");		
			}
			else
			exit("No salary slip found");
		}
		
		if(!$zip->close())
		echo "status:" . $zip->status . "\n";
		else
		echo "Added to zip";
	}
	else if($_POST['func']=="userview")
	{
		$flag=false;
		$status=0;
		$filename=get_slip($_POST['date'],$_POST['code'],$flag,$status);
		if($flag==true)
		echo "<iframe id='sliphtml' src='$filename' name='userview'></iframe>";
		else
		echo "<h3>No salary slips found for the specified date</h3>";
	}
	else
	{
		$flag=false;
		$status=1;
		$filename=get_slip($_POST['date'],$_POST['code'],$flag,$status);
		if($flag==true)
		echo "<iframe id='sliphtml' src='$filename' name='$status'></iframe>";
		else
		echo "<h3>No salary slips found for the specified date</h3>";
	}
}
else if($_POST['func']=="createzip")
{
	$zip = new ZipArchive();
		$filename = "slips/slips.zip";
		if ($zip->open($filename, ZIPARCHIVE::OVERWRITE)!==TRUE) {
			exit("cannot open <$filename>\n");
		}
		else
		{
			$zip->addFromString("readme.txt","This archive contains the pdfs of salary slips");
			$zip->close();
			echo "yes";
		}
}
else
{
	include 'db_conf.php';

	$code=$_POST['code'];
	$query="Select `month`,`year` from `salary_slips` where `Employee Code`='$code'";
	$res = $mysqli->query($query) or die($mysqli->error);
	$str="";
	while($row=$res->fetch_Assoc())
	{
		$date_val=$row['month']."/".$row['year'];
		$date=$row['month']."/27/".$row['year'];
		$date_view=date('F Y',strtotime($date));
		$str.="<option value='$date_val'>$date_view</option>";
	}
	echo $str;
}
?>