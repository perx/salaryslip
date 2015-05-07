<?php
session_start();
if($_SESSION['user']=='payslipadmin')
{
	include 'db_conf.php';
	$year=$_POST['date'];
	if($_POST['empcode']=="all")
	{
		$query="Select month,salary_structure.earn,`earn_payable` from salary_slips INNER JOIN salary_structure ON salary_slips.sal_str_code=salary_structure.sal_str_code where year='$year' AND `Release Date`!='ON HOLD'";
	}
	else
	{
		$code=$_POST['empcode'];
		$query="Select month,salary_structure.earn,`earn_payable` from salary_slips INNER JOIN salary_structure ON salary_slips.sal_str_code=salary_structure.sal_str_code where `Employee Code`='$code' AND year='$year' AND `Release Date`!='ON HOLD'";
	}
	$res=$mysqli->query($query) or die("database");
	if($res->num_rows<1)
	die("false");
	while($row=$res->fetch_assoc())
	{
		$earn_values=array_combine(explode(";",$row['earn']),explode(";",$row['earn_payable']));
		foreach($earn_values as $k=>$v)
		{
			for($i=1;$i<=12;$i++)
			{
				$month=date("F",strtotime($i."/27/2015"));
				$monthly_records[$month][$k]+=0;
			}
			$month=date("F",strtotime($row['month']."/27/2015"));
			$monthly_records[$month][$k]+=0;
			$monthly_records[$month][$k]+=floatval(str_replace(",","",$v));
		}
	}
	foreach($monthly_records['January'] as $k=>$v)
	{
		$json['headings'][]=$k;
	}
	foreach($monthly_records as $k=>$v)
	{
		$json['values'][$k]=array_values($monthly_records[$k]);
	}
	echo json_encode($json);
}
?>