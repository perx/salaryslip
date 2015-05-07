<?php include "session.php"; ?>
<?php if($_SESSION['user']=='admin') { ?>
<?php if(isset($_GET['emp']))
{
	include_once 'db_conf.php';
	$query="Select `Name of the Employee`,`Employee Code` from employee_info ORDER BY `Name of the Employee`";
	$res = $mysqli->query($query) or die($mysqli->error);
	echo "<table id='names_list' style='width:500px'>";
	echo "<tr id='thead'><th>Code</th><th style='padding-left:20px;'>Name of the Employee</span></th></tr>";
	echo "<tbody>";
	while($row = $res->fetch_array())
	{
		$temp="'$row[1]'";
		echo "<tr><td><div class='squaredThree'><input type='checkbox' id='$row[1]' name='id_list[]' value=\"$temp\" /><label for='$row[1]'></label>".$row[1]."</div></td>";
		echo "<td style='padding-left:20px;'>".$row[0]."</td></tr>";
	}
	echo "<tr><td><div class='squaredThree'><input type='checkbox' id='all' name='all' value='all' onChange='select_all()'/><label for='all'></label></div></td>";
	echo "<td style='padding-left:20px;'>Select all</td></tr>";
	echo "</tbody></table>"; 
}
else if($_GET['func']=="get_emp_names")
{
	include_once 'db_conf.php';
	$query="Select `Name of the Employee`,`Employee Code` from employee_info ORDER BY `Name of the Employee`";
	$res = $mysqli->query($query) or die($mysqli->error);
	echo "<tr id='thead'><th>Code</th><th style='padding-left:20px;'>Name of the Employee</span></th></tr>";
	while($row = $res->fetch_assoc())
	{
		$code=$row['Employee Code'];
		echo "<tr><td><input type='radio' name='leaves_code' value='$code'/>$code</td>";
		echo "<td style='padding-left:20px;'>".$row['Name of the Employee']."</td></tr>";
	}
	echo "<tr><td><input type='radio' name='leaves_code' value='all'/></td>";
	echo "<td style='padding-left:20px;'>Select all</td></tr>";
}
else if($_GET['func']=="get_info")
{
	include_once 'db_conf.php';
	$query="Select employee_info.*,salary_structure.ded as ded_key,salary_structure.earn as earn_key from employee_info INNER JOIN salary_structure ON employee_info.sal_str_code=salary_structure.sal_str_code ORDER BY `Name of the Employee`";
	$res= $mysqli->query($query) or die($mysqli->error);
	$heads=$res->fetch_fields();
	echo "<h2>Personal Information&nbsp;<img src='images/excel.png' onclick=\"tabletoexcel('employee_info_personal');\" style='width: 18px;'></h2>";
	echo "<table id='employee_info_personal' style='width: 1020px;table-layout: fixed;'>";
	echo "<tr id='thead'>";
	foreach($heads as $v)
	{
		if($v->name!="earn"&&$v->name!="ded"&&$v->name!="earn_key"&&$v->name!="ded_key"&&$v->name!="sal_str_code"&&$v->name!="active")
		echo "<th>$v->name</th>";
	}
	echo "</tr>";
	echo "<tbody>";
	while($row = $res->fetch_assoc())
	{
		$earn[$row['Employee Code']]=array_combine(explode(";",$row['earn_key']),explode(";",$row['earn']));
		$ded[$row['Employee Code']]=array_combine(explode(";",$row['ded_key']),explode(";",$row['ded']));
		echo "<tr>";
		foreach($row as $k=>$v)
		{
			if($k!="earn"&&$k!="ded"&&$k!="earn_key"&&$k!="ded_key"&&$k!="sal_str_code"&&$k!="active")
			echo "<td>$v</td>";
		}
		echo "</tr>";
	}
	echo "</tbody></table>";
	
	echo "<h2>Accounts&nbsp;<img src='images/excel.png' onclick=\"tabletoexcel('employee_info_accounts');\" style='width: 18px;'></h2>";
	echo "<table id='employee_info_accounts' style='width:700px'>";
	echo "<tr id='thead'>";
	echo "<th>Employee Code</th><th>Allowances</th><th>Deductions</th>";
	echo "</tr>";
	echo "<tbody>";
	foreach($earn as $code=>$earn_arr)
	{
		echo "<tr>";
		echo "<td>$code</td><td><table style='text-align:left'>";
		foreach($earn_arr as $earn_h=>$earn_v)
		{
			echo "<tr><td>$earn_h</td><td>$earn_v</td></tr>";
		}
		echo "</table></td><td><table style='text-align:left'>";
		foreach($ded[$code] as $ded_h=>$ded_v)
		{
			echo "<tr><td>$ded_h</td><td>$ded_v</td></tr>";
		}
		echo "</table></td></tr>";
	}
	echo "</tbody></table>";
}
else if($_GET['func']=="get_leaves")
{
	$code=$_GET['code'];
	$month=$_GET['month'];
	include_once 'db_conf.php';
	if($month=="all")
	{
		if($code=="all")
		$query="Select `Employee Code`,salary_slips.leaves,salary_slips.month,salary_slips.year,salary_structure.leaves as leaves_key from salary_slips INNER JOIN salary_structure ON salary_slips.sal_str_code=salary_structure.sal_str_code";
		else
		$query="Select salary_slips.leaves,salary_slips.month,salary_slips.year,salary_structure.leaves as leaves_key from salary_slips INNER JOIN salary_structure ON salary_slips.sal_str_code=salary_structure.sal_str_code where `Employee Code`='$code'";
	}
	else
	{
		$month=explode("/",$month);
		$year=$month[1];
		$month=$month[0];
		if($code=="all")
		$query="Select `Employee Code`,salary_slips.leaves,salary_slips.month,salary_slips.year,salary_structure.leaves as leaves_key from salary_slips INNER JOIN salary_structure ON salary_slips.sal_str_code=salary_structure.sal_str_code where `month`='$month' AND `year`=$year";
		else
		$query="Select salary_slips.leaves,salary_slips.month,salary_slips.year,salary_structure.leaves as leaves_key from salary_slips INNER JOIN salary_structure ON salary_slips.sal_str_code=salary_structure.sal_str_code where `Employee Code`='$code' AND `month`='$month' AND `year`=$year";
	}
	
	$res= $mysqli->query($query) or die($mysqli->error);
	echo "<h2>Leaves</h2>";
	echo "<table style='width: 700px;table-layout: fixed;'>";
	echo "<tr id='thead'>";
	$row=$res->fetch_assoc();
	if($code=="all")
	echo "<th>Employee Code</th>";
	echo "<th>Month</th>";
	foreach(explode(";",$row['leaves_key']) as $v)
	echo "<th>$v</th>";
	echo "</tr>";
	echo "<tbody>";
	do
	{
		echo "<tr>";
		if($code=="all")
		echo "<td>".$row['Employee Code']."</td>";
		echo "<td>".$row['month']."/".$row['year']."</td>";
		foreach(explode(";",$row['leaves']) as $v_val)
		echo "<td>$v_val</td>";
		echo "</tr>";
	}
	while($row=$res->fetch_assoc());
	echo "</tbody></table>";
}

?>

<?php } ?>