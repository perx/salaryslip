<?php include_once 'session.php' ?>
<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="generate_head.css" type="text/css"/>
<link rel="stylesheet" href="generate_form_style.css" type="text/css"/>
<title>Salary Details</title>
<script>
function validate()
{
	var v=document.getElementsByTagName('input');
	for(var i=0;i<v.length;i++)	
	{
		if(v[i].type=='text')
		{
			if(isNaN(v[i].value))
			{
				alert("Please enter a valid numerical value for "+v[i].name);
				return false;
			}
		}
	}
	return true;
}
function calculate_total(id)
{
	var v=id.value;
	if(isNaN(v))
	{
		alert("Please enter a valid numerical value");
	}
	else
	{
		var sum=0;
		var tr=id.parentNode.parentNode;
		for(var i=0;i<tr.childElementCount-1;i++)
		{
			if(tr.children[i].children.length!=0)
			{
				sum+=parseFloat(tr.children[i].children[0].value);
			}
			else
			continue;
		}
		tr.children[i].children[0].value=sum;
	}
}
</script>
</head>
<?php if(isset($_POST['id_list'])) {?>
<?php
	include_once 'db_conf.php';
	$query="Select * from salary_structure order by sal_str_code DESC LIMIT 1";
	$res = $mysqli->query($query) or die($mysqli->error);
	$row = $res->fetch_assoc();
	$earn=explode(";",$row['earn']);	
	$ded=explode(";",$row['ded']);
	$leaves=explode(";",$row['leaves']);
	$sal_str_code=$row['sal_str_code'];
	mysqli_free_result($res);
	$emp_id=implode(",",$_POST['id_list']);
	$query="SELECT `Name of the Employee`, `Employee Code`, `Designation`, `Department`, `PF Account No.`, `Date of Joining`, `Salary Start Date`,`sal_str_code`, `earn`, `ded`,`email` from employee_info where `Employee Code` IN($emp_id)";
	$res = $mysqli->query($query) or die($mysqli->error);
	$i=0;
	while($row=$res->fetch_assoc())
	{
		$emp_list[$i]=$row;
		$i++;
	}
	mysqli_free_result($res);
	mysqli_close($mysqli);
?>
<body>
<?php include_once 'header.php'; ?>
<form action="gentable_man.php" method="POST" onSubmit="return validate()">
	<fieldset class="sections">
    <legend><span>Allowances</span></legend>
	<div class="table_div">
    <table>
        <?php
			echo "<tr class='heads'>";
			echo "<th>Employee Code</th><th>Name of the Employee</th>";
			foreach($earn as $v)
			{
				echo "<th>$v</th>";
			}
			echo "<th>Total Allowances</th>";
			echo "</tr>";
			foreach($emp_list as $info)
			{
				$code=$info['Employee Code'];
				$name=$info['Name of the Employee'];
				echo "<tr>";
				echo "<td name='Employee Code'>$code</td><td id='Name of the Employee'>$name</td>";
				$info['earn']=explode(";",$info['earn']);
				if($info['sal_str_code']==$sal_str_code)
				{
					$id='earn';
					$total_earn=0.0;
					$combo_earn=array_combine($earn,$info['earn']);
					foreach($combo_earn as $k=>$v)
					{
						$total_earn+=$v;
						$tdid=$code."[$id][$k]";
						echo "<td><input type='text' name='$tdid' value='$v' onblur='calculate_total(this)'/></td>";
					}
				}
				$k='Total';
				$tdid=$code."[$k]";
				echo "<td><input type='text' name='$tdid' value='$total_earn' readOnly/></td>";
				echo "</tr>";
			}
		?>
    </table>
    </div>
    </fieldset>
    <fieldset class="sections">
    <legend><span>Deductions</span></legend>
    <div class="table_div">
	<table>
        <?php
			echo "<tr class='heads'>";
			echo "<th>Employee Code</th><th>Name of the Employee</th>";
			foreach($ded as $k=>$v)
			{
				echo "<th>$v</th>";
			}
			echo "<th>Total Deductions</th>";
			echo "</tr>";
			foreach($emp_list as $info)
			{
				$code=$info['Employee Code'];
				$name=$info['Name of the Employee'];
				echo "<tr>";
				echo "<td name='Employee Code'>$code</td><td id='Name of the Employee'>$name</td>";
				$info['ded']=explode(";",$info['ded']);
				if($info['sal_str_code']==$sal_str_code)
				{
					$id='ded';
					$total_ded=0.0;
					$combo_ded=array_combine($ded,$info['ded']);
					foreach($combo_ded as $k=>$v)
					{
						$total_ded+=$v;
						$tdid=$code."[$id][$k]";
						echo "<td><input type='text' name='$tdid' value='$v' onblur='calculate_total(this)'/></td>";
					}
				}
				$k='Total Ded';
				$tdid=$code."[$k]";
				echo "<td><input type='text' name='$tdid' value='$total_ded' readOnly/></td>";
				echo "</tr>";
			}
		?>
    </table>
    </div>
    </fieldset>
    <fieldset class="sections">
    <legend><span>Leaves</span></legend>
    <div class="table_div">
	<table>
        <?php
			echo "<tr class='heads'>";
			echo "<th>Employee Code</th><th>Name of the Employee</th>";
			echo "<th>Working/Paid Days</th>";
			foreach($leaves as $k=>$v)
			{
				echo "<th>$v</th>";
			}
			echo "</tr>";
			foreach($emp_list as $info)
			{
				$code=$info['Employee Code'];
				$name=$info['Name of the Employee'];
				echo "<tr>";
				echo "<td name='Employee Code'>$code</td><td id='Name of the Employee'>$name</td>";
				$info['ded']=explode(";",$info['ded']);
				$id='leaves';
				$k='Working/Paid Days';
				{
					$tdid=$code."[$k]";
					echo "<td><input type='number' name='$tdid' value='1' min='1' max='31'/></td>";
				}
				foreach($leaves as $v)
				{
					$tdid=$code."[$id][$v]";
					echo "<td><input type='number' name='$tdid' value='0'/></td>";
				}
				echo "</tr>";
			}
		?>
    </table>
    </div>
    </fieldset>
    	<?php
		foreach($emp_list as $info)
		{
			$id='empinfo';
			$code=$info['Employee Code'];
			$k='Department';
			{
				$tdid=$code."[$id][$k]";
				echo "<input type='hidden' name='$tdid' value='$info[$k]'/>";
			}
            $k='PF Account No.';
			{
				$tdid=$code."[$id][$k]";
				echo "<input type='hidden' name='$tdid' value='$info[$k]'/>";
			}
			//parse month for which salary slip is to be generated
            $k='Month';
			{
				$tdid=$code."[$id][$k]";
				$month=date('F Y', strtotime($_POST['month']));
				echo "<input type='hidden' name='$tdid' value='$month'/>";
			}
            $k='Date of Joining';
			{
				$tdid=$code."[$id][$k]";
				$v=strtotime($info[$k]);
				$v=date('d-M-Y',$v);
				echo "<input type='hidden' name='$tdid' value='$v'/>";
			}
            $k='Salary Start Date';
			{
				$tdid=$code."[$id][$k]";
				$v=strtotime($info[$k]);
				$v=date('d-M-Y',$v);
				echo "<input type='hidden' name='$tdid' value='$v'/>";
			}
			$k='Name of the Employee';
			{
				$tdid=$code."[$k]";
				echo "<input type='hidden' name='$tdid' value='$info[$k]'/>";
			}
			$k='Employee Code';
			{
				$tdid=$code."[$k]";
				echo "<input type='hidden' name='$tdid' value='$info[$k]'/>";
			}
			$k='Designation';
			{
				$tdid=$code."[$k]";
				echo "<input type='hidden' name='$tdid' value='$info[$k]'/>";
			}
			$k='email';
			{
				$tdid=$code."[$k]";
				echo "<input type='hidden' name='$tdid' value='$info[$k]'/>";
			}
		}
		$_SESSION['ded']=$ded;
		$_SESSION['earn']=$earn;
		$_SESSION['leaves']=$leaves;
		$_SESSION['sal_str_code']=$sal_str_code;
		?>
    <div style="text-align:center; margin-bottom:10px;">
    <input type="submit" id="submit" value="Submit">
    </div>
</form>
</body>
</html>
<?php } ?>