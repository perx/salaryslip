<?php
	set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . '/PHPMailer-master/');
	function update_database(&$db_entry)
	{
		$code=&$db_entry['emp']['Employee Code'];
		$earn=implode(";",$db_entry['emp']['earn']);
		$ded=implode(";",$db_entry['emp']['ded']);
		global $sal_str_code;
		$earn_payable=implode(";",$db_entry['emp']['earn_payable']);
		$leaves=implode(";",$db_entry['emp']['leaves']);
		$designation=&$db_entry['emp']['Designation'];
		$department=&$db_entry['emp']['empinfo']['Department'];
		$paid_days=&$db_entry['emp']['Working/Paid Days'];
		$month=date('m',strtotime($db_entry['emp']['empinfo']['Month']));
		$year=date('Y',strtotime($db_entry['emp']['empinfo']['Month']));
		$release_date=&$db_entry['emp']['Release Date'];
		$file_id=&$db_entry['emp']['file_id'];
		include 'db_conf.php';
		$query="UPDATE `employee_info` SET `ded`='$ded',`earn`='$earn' where `Employee Code`='$code'";
		$res=$mysqli->query($query);
		if (!($stmt = $mysqli->prepare("INSERT INTO `excel_slips`(`id`,`Employee Code`, `sal_str_code`, `month`,`year`, `earn`, `earn_payable`, `ded`, `leaves`, `Designation`, `Department`, `Working/Paid Days`,`Release Date`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)"))) 
		{
    		echo( "Salary Slip Statement could not be prepared: (" . $mysqli->errno . ") " . $mysqli->error."<br/>");
			return NULL;
		}
		$stmt->bind_param("isiiissssssds",$file_id,$code,$sal_str_code, $month,$year,$earn,$earn_payable,$ded,$leaves,$designation,$department,$paid_days,$release_date);
		if (!$stmt->execute()) 
		{
    		echo("Could not insert salary slip into database: (" . $stmt->errno . ") " . $stmt->error."<br/>");
			return NULL;
		}
		if (!($stmt2 = $mysqli->prepare("UPDATE `employee_info` SET `earn`=?,`ded`=? where `Employee Code`=?"))) 
		{
    		echo( "Salary Distribution for $code could not be updated (" . $mysqli->errno . ") " . $mysqli->error."<br/>");
		}
		$stmt2->bind_param("sss",$earn,$ded,$code);
		if (!$stmt2->execute()) 
		{
    		echo("Could not insert salary distribution for $code into database: (" . $stmt2->errno . ") " . $stmt2->error."<br/>");
			return NULL;
		}
		
	}
	function add_keys($a,$b)
	{
		$key="";
		for($i=0;$i<$b;$i++)
		{
			$key.=" ";
			$a[$key]=$key;
		}
		return $a;
	}
	function days_in_month($date) 
	{ 
		$year=date('Y',strtotime($date));
		$month=date('m',strtotime($date));
		// calculate number of days in a month 
		return $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31); 
	} 
	function check_if_exists(&$code,$date)
	{
		$year=date('Y',strtotime($date));
		$month=date('m',strtotime($date));
		include 'db_conf.php';
		$query="SELECT * from `salary_slips` where `Employee Code`='$code' AND `month`='$month' AND `year`='$year'";
		$res=$mysqli->query($query);
		if($res->num_rows>0)
		return true;
		else
		return false;
	}
	function get_not_in_total($earn,$default_earn)
	{
		$not_in_total[]="";
		$earn_val=array_combine($earn,$default_earn);
		foreach($earn_val as $k=>$v)
		{
			if($v[strlen($v)-1]=='!')
			$not_in_total[]=$k;
		}
		return $not_in_total;
	}

?>
<?php function gen($sess,$db_bool) { ?>
<?php
	//echo "<pre>";
	//$empinfo_heads=[];
	$name=$sess['emp']['Name of the Employee'];
	$emp_code=$sess['emp']['Employee Code'];
	$designation=$sess['emp']['Designation'];
	$paid=$sess['emp']['Working/Paid Days'];
	$no_of_days=days_in_month($sess['emp']['empinfo']['Month']);
	$month=$sess['emp']['empinfo']['Month'];
	$dir=str_replace(' ', '',"slips/".$month);
	if(!is_dir($dir))
	{
		mkdir($dir,0777);
	}
	$filename=str_replace(' ', '',$dir."/".$month."-".$sess['emp']['Name of the Employee'].".html");
	
	if($db_bool===true)
	{
		if(check_if_exists($emp_code,$month))
		{
			$db_bool=false;
			echo "Salary Slip already exists";
		}	
	}
	$i=0;
	//intern flag
	if($sess['emp']['Designation']=="INTERN") 
	{
		$intern_flag=true; 
		$salary_slip_heading="Stipend";
	}
	else
	{
		 $intern_flag=false;
		 $salary_slip_heading="Pay Slip";
	}

	$db_entry['emp']=$sess['emp'];
	foreach($sess['emp']['empinfo'] as $k=>$v)
	{
		$empinfo_heads[$i++]=$k;
	}
	foreach($sess['emp']['earn'] as $k=>$v)
	{
		if($v=="0"||$v=="0.00")
		{
			if (($key = array_search($k, $sess['earn'])) !== false) 
			{
    			unset($sess['earn'][$key]);
			}
		}
		
	}
	$diff=count($sess['ded'])-count($sess['earn']);
		if($diff>0)
		{
			$sess['earn']=add_keys($sess['earn'],$diff);
			$sess['emp']['earn']=add_keys($sess['emp']['earn'],$diff);
		}
		else if($diff<0)
		{
			$sess['ded']=add_keys($sess['ded'],0-$diff);
			$sess['emp']['ded']=add_keys($sess['emp']['ded'],0-$diff);
		}
	$diff2=count($sess['leaves'])-count($empinfo_heads);
		if($diff2<0)
		{
			$sess['leaves']=add_keys($sess['leaves'],0-($diff2));
			$sess['emp']['leaves']=add_keys($sess['emp']['leaves'],0-($diff2));
		}
		else if($diff2>0)
		{
			$sess['emp']['empinfo']=add_keys($sess['emp']['empinfo'],$diff2);
			$empinfo_heads=add_keys($empinfo_heads,$diff2);
		}
	$xls_rows=array_combine($sess['earn'],$sess['ded']);
	$leave_rows=array_combine($sess['leaves'],$empinfo_heads);
	//print_r($xls_rows);
	//echo "</pre>";
?>
<?php
	$filehandler=fopen($filename,'w');
	if($filehandler==NULL)
	{
		echo ("Cannot create file"."<br/>");
		return NULL;
	}
	chmod($filename,0777);
?>
<?php
$str=<<<MYTAB
<!DOCTYPE html>
<html>
<head>
<meta name=Title content=""/>
<meta name=Keywords content=""/>
<meta http-equiv=Content-Type content="text/html; charset=UTF-8"/>
<meta name=ProgId content=Excel.Sheet/>
<meta name=Generator content="Microsoft Excel 14"/>
</head>
<style>
	{mso-displayed-decimal-separator:"\.";
	mso-displayed-thousand-separator:"\,";}
@page
	{margin:.75in .7in .75in .7in;
	mso-header-margin:.3in;
	mso-footer-margin:.3in;}
.font5
	{color:windowtext;
	font-size:8.0pt;
	font-weight:700;
	font-style:normal;
	text-decoration:none;
	font-family:Arial, sans-serif;
	mso-font-charset:0;}
.font12
	{color:windowtext;
	font-size:12.0pt;
	font-weight:700;
	font-style:normal;
	text-decoration:none;
	font-family:Arial, sans-serif;
	mso-font-charset:0;}
.style43
	{mso-number-format:"_\(* \#\,\#\#0\.00_\)\;_\(* \\\(\#\,\#\#0\.00\\\)\;_\(* \\-??_\)\;_\(\@_\)";
	color:windowtext;
	font-size:10.0pt;
	font-weight:400;
	font-style:normal;
	text-decoration:none;
	font-family:Arial, sans-serif;
	mso-font-charset:0;
	mso-style-name:Comma;
	mso-style-id:3;}
.style0
	{mso-number-format:General;
	text-align:general;
	vertical-align:bottom;
	white-space:nowrap;
	mso-rotate:0;
	mso-background-source:auto;
	mso-pattern:auto;
	color:windowtext;
	font-size:10.0pt;
	font-weight:400;
	font-style:normal;
	text-decoration:none;
	font-family:Arial, sans-serif;
	mso-font-charset:0;
	border:none;
	mso-protection:locked visible;
	mso-style-name:Normal;
	mso-style-id:0;}
td
	{mso-style-parent:style0;
	padding:0px;
	mso-ignore:padding;
	color:windowtext;
	font-size:10.0pt;
	font-weight:400;
	font-style:normal;
	text-decoration:none;
	font-family:Arial, sans-serif;
	mso-font-charset:0;
	mso-number-format:General;
	text-align:general;
	vertical-align:bottom;
	border:none;
	mso-background-source:auto;
	mso-pattern:auto;
	mso-protection:locked visible;
	white-space:nowrap;
	mso-rotate:0;}
.xl70
	{mso-style-parent:style0;
	font-size:8.0pt;
	text-align:center;}
.xl71
	{mso-style-parent:style0;
	font-size:8.0pt;
	text-align:center;
	mso-protection:locked hidden;}
.xl72
	{mso-style-parent:style0;
	font-size:8.0pt;
	font-weight:700;
	text-align:center;}
.xl73
	{mso-style-parent:style0;
	font-size:8.0pt;
	
	mso-pattern:black none;}
.xl74
	{mso-style-parent:style0;
	font-size:8.0pt;
	font-weight:700;
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:none;
	border-bottom:1.0pt solid black;
	border-left:1.0pt solid windowtext;}
.xl75
	{mso-style-parent:style0;
	font-size:8.0pt;
	font-weight:700;
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:none;
	border-bottom:1.0pt solid black;
	border-left:none;}
.xl76
	{mso-style-parent:style0;
	font-size:8.0pt;
	font-weight:700;
	text-align:center;
	vertical-align:middle;
	border-top:1.0pt solid windowtext;
	border-right:none;
	border-bottom:1.0pt solid black;
	border-left:none;}
.xl77
	{mso-style-parent:style0;
	font-size:8.0pt;
	font-weight:700;
	text-align:center;
	vertical-align:middle;
	border-top:1.0pt solid windowtext;
	border-right:1.0pt solid windowtext;
	border-bottom:1.0pt solid black;
	border-left:none;}
.xl78
	{mso-style-parent:style0;
	font-size:8.0pt;
	font-weight:700;
	text-align:left;
	border-top:none;
	border-right:none;
	border-bottom:none;
	border-left:1.0pt solid windowtext;}
.xl79
	{mso-style-parent:style0;
	font-size:8.0pt;
	font-weight:700;
	text-align:left;}
.xl80
	{mso-style-parent:style0;
	font-size:8.0pt;
	text-align:center;
	border-top:none;
	border-right:1.0pt solid windowtext;
	border-bottom:none;
	border-left:none;}
.xl81
	{mso-style-parent:style0;
	font-size:8.0pt;
	mso-number-format:"Short Date";
	text-align:center;}
.xl82
	{mso-style-parent:style0;
	font-size:8.0pt;
	mso-number-format:"mmm\\ yyyy";
	text-align:center;}
.xl83
	{mso-style-parent:style0;
	font-size:8.0pt;
	font-weight:700;
	text-decoration:underline;
	text-underline-style:single;
	text-align:left;}
.xl84
	{mso-style-parent:style0;
	font-size:8.0pt;
	text-align:left;}
.xl85
	{mso-style-parent:style0;
	font-size:8.0pt;
	text-align:center;
	mso-protection:unlocked visible;}
.xl86
	{mso-style-parent:style0;
	font-size:8.0pt;
	text-align:center;
	border-top:none;
	border-right:none;
	border-bottom:none;
	border-left:1.0pt solid windowtext;}
.xl87
	{mso-style-parent:style0;
	font-size:8.0pt;
	text-align:left;
	border-top:none;
	border-right:none;
	border-bottom:none;
	border-left:1.0pt solid windowtext;}
.xl88
	{mso-style-parent:style0;
	font-size:8.0pt;
	font-weight:700;
	text-decoration:underline;
	text-underline-style:single;
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:none;
	border-bottom:1.0pt solid windowtext;
	border-left:1.0pt solid windowtext;}
.xl89
	{mso-style-parent:style0;
	font-size:8.0pt;
	font-weight:700;
	text-decoration:underline;
	text-underline-style:single;
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:none;
	border-bottom:1.0pt solid windowtext;
	border-left:none;}
.xl90
	{mso-style-parent:style0;
	font-size:8.0pt;
	font-weight:700;
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:.5pt solid black;
	border-bottom:1.0pt solid windowtext;
	border-left:1.0pt solid windowtext;}
.xl91
	{mso-style-parent:style0;
	font-size:8.0pt;
	font-weight:700;
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:1.0pt solid windowtext;
	border-bottom:1.0pt solid windowtext;
	border-left:.5pt solid black;}
.xl92
	{mso-style-parent:style0;
	font-size:8.0pt;
	font-weight:700;
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:none;
	border-bottom:1.0pt solid windowtext;
	border-left:none;}
.xl93
	{mso-style-parent:style43;
	font-size:8.0pt;
	mso-number-format:"\#\,\#\#0\.00\;\[Red\]\#\,\#\#0\.00";
	text-align:center;
	border-top:none;
	border-right:.5pt solid windowtext;
	border-bottom:none;
	border-left:.5pt solid windowtext;}
.xl94
	{mso-style-parent:style43;
	font-size:8.0pt;
	mso-number-format:"\#\,\#\#0\.00\;\[Red\]\#\,\#\#0\.00";
	text-align:center;
	border-top:none;
	border-right:1.0pt solid windowtext;
	border-bottom:none;
	border-left:.5pt solid black;}
.xl95
	{mso-style-parent:style0;
	font-size:8.0pt;
	text-align:center;
	border-top:none;
	border-right:.5pt solid windowtext;
	border-bottom:none;
	border-left:.5pt solid windowtext;}
.xl96
	{mso-style-parent:style43;
	font-size:8.0pt;
	mso-number-format:"\#\,\#\#0\.00\;\\-\#\,\#\#0\.00";
	text-align:center;
	border-top:none;
	border-right:.5pt solid windowtext;
	border-bottom:none;
	border-left:.5pt solid windowtext;}
.xl97
	{mso-style-parent:style43;
	font-size:8.0pt;
	mso-number-format:Standard;
	text-align:center;
	border-top:none;
	border-right:.5pt solid windowtext;
	border-bottom:none;
	border-left:.5pt solid windowtext;}
.xl98
	{mso-style-parent:style43;
	font-size:8.0pt;
	mso-number-format:"_\(* \#\,\#\#0_\)\;_\(* \\\(\#\,\#\#0\\\)\;_\(* \\-??_\)\;_\(\@_\)";
	text-align:center;
	border-top:none;
	border-right:.5pt solid windowtext;
	border-bottom:none;
	border-left:.5pt solid windowtext;}
.xl99
	{mso-style-parent:style0;
	font-size:8.0pt;
	font-weight:700;
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:none;
	border-bottom:1.0pt solid windowtext;
	border-left:1.0pt solid windowtext;}
.xl100
	{mso-style-parent:style43;
	font-size:8.0pt;
	font-weight:700;
	mso-number-format:"\#\,\#\#0\.00\;\[Red\]\#\,\#\#0\.00";
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:.5pt solid black;
	border-bottom:1.0pt solid windowtext;
	border-left:.5pt solid black;}
.xl101
	{mso-style-parent:style43;
	font-size:8.0pt;
	font-weight:700;
	mso-number-format:"\#\,\#\#0\.00\;\[Red\]\#\,\#\#0\.00";
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:1.0pt solid windowtext;
	border-bottom:1.0pt solid windowtext;
	border-left:.5pt solid black;}
.xl102
	{mso-style-parent:style43;
	font-size:8.0pt;
	mso-number-format:"_\(* \#\,\#\#0_\)\;_\(* \\\(\#\,\#\#0\\\)\;_\(* \\-??_\)\;_\(\@_\)";
	text-align:center;
	border-top:none;
	border-right:1.0pt solid windowtext;
	border-bottom:none;
	border-left:.5pt solid black;}
.xl103
	{mso-style-parent:style0;
	font-size:8.0pt;
	text-align:center;
	border-top:none;
	border-right:none;
	border-bottom:1.0pt solid windowtext;
	border-left:1.0pt solid windowtext;}
.xl104
	{mso-style-parent:style0;
	font-size:8.0pt;
	text-align:center;
	border-top:none;
	border-right:none;
	border-bottom:1.0pt solid windowtext;
	border-left:none;}
.xl105
	{mso-style-parent:style0;
	font-size:8.0pt;
	font-weight:700;
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:1.0pt solid windowtext;
	border-bottom:1.0pt solid windowtext;
	border-left:none;}
.xl106
	{mso-style-parent:style43;
	font-size:8.0pt;
	font-weight:700;
	mso-number-format:"\#\,\#\#0\.00\;\[Red\]\#\,\#\#0\.00";
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:1.0pt solid windowtext;
	border-bottom:1.0pt solid windowtext;
	border-left:none;}
.xl107
	{mso-style-parent:style0;
	font-size:8.0pt;
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:none;
	border-bottom:1.0pt solid windowtext;
	border-left:1.0pt solid windowtext;}
.xl108
	{mso-style-parent:style0;
	font-size:8.0pt;
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:none;
	border-bottom:1.0pt solid windowtext;
	border-left:none;}
.xl109
	{mso-style-parent:style0;
	font-size:8.0pt;
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:1.0pt solid windowtext;
	border-bottom:1.0pt solid windowtext;
	border-left:none;}
.xl110
	{mso-style-parent:style0;
	font-size:8.0pt;
	font-weight:700;
	mso-number-format:"\#\,\#\#0\.00\;\[Red\]\#\,\#\#0\.00";
	text-align:center;
	border-top:none;
	border-right:1.0pt solid windowtext;
	border-bottom:1.0pt solid windowtext;
	border-left:none;}
.xl111
	{mso-style-parent:style0;
	font-size:8.0pt;
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:none;
	border-bottom:none;
	border-left:1.0pt solid windowtext;}
.xl112
	{mso-style-parent:style0;
	font-size:8.0pt;
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:1.0pt solid windowtext;
	border-bottom:none;
	border-left:none;}
.xl113
	{mso-style-parent:style0;
	font-size:8.0pt;
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:none;
	border-bottom:none;
	border-left:none;}
.xl114
	{mso-style-parent:style0;
	font-size:8.0pt;
	text-align:right;
	border-top:none;
	border-right:none;
	border-bottom:none;
	border-left:1.0pt solid windowtext;}
.xl115
	{mso-style-parent:style0;
	font-size:8.0pt;
	text-align:center;
	border-top:none;
	border-right:1.0pt solid windowtext;
	border-bottom:1.0pt solid windowtext;
	border-left:none;}
.xl116
	{mso-style-parent:style0;
	font-size:8.0pt;
	text-align:right;
	border-top:none;
	border-right:none;
	border-bottom:1.0pt solid windowtext;
	border-left:none;}
.xl117
	{mso-style-parent:style0;
	font-size:8.0pt;
	text-align:center;
	white-space:normal;
	mso-text-control:shrinktofit;}
.xl118
	{mso-style-parent:style43;
	font-size:8.0pt;
	font-weight:700;
	mso-number-format:"\#\,\#\#0\;\[Red\]\#\,\#\#0";
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:1.0pt solid windowtext;
	border-bottom:1.0pt solid windowtext;
	border-left:none;}
.xl119
	{mso-style-parent:style0;
	font-size:12.0pt;
	font-weight:700;
	text-align:left;}
.xl120
	{mso-style-parent:style0;
	text-align:left;}
.xl121
	{mso-style-parent:style0;
	font-size:8.0pt;}
.xl122
	{mso-style-parent:style0;
	font-size:8.0pt;
	font-weight:700;
	text-align:left;
	border:1.0pt solid windowtext;}
.xl123
	{mso-style-parent:style43;
	font-size:8.0pt;
	mso-number-format:"\#\,\#\#0\.00\;\[Red\]\#\,\#\#0\.00";
	text-align:center;
	border:1.0pt solid windowtext;}
.xl124
	{mso-style-parent:style0;
	font-size:8.0pt;
	text-align:left;
	border:1.0pt solid windowtext;}
.xl125
	{mso-style-parent:style0;
	font-size:8.0pt;
	text-align:center;
	border:1.0pt solid windowtext;}
.xl126
	{mso-style-parent:style43;
	font-size:8.0pt;
	mso-number-format:"\#\,\#\#0\.00\;\\-\#\,\#\#0\.00";
	text-align:center;
	border:1.0pt solid windowtext;}
.xl127
	{mso-style-parent:style43;
	font-size:8.0pt;
	mso-number-format:Standard;
	text-align:center;
	border:1.0pt solid windowtext;}
.xl128
	{mso-style-parent:style43;
	font-size:8.0pt;
	mso-number-format:"_\(* \#\,\#\#0_\)\;_\(* \\\(\#\,\#\#0\\\)\;_\(* \\-??_\)\;_\(\@_\)";
	text-align:center;
	border:1.0pt solid windowtext;}
.xl129
	{mso-style-parent:style0;
	border:1.0pt solid windowtext;}
.xl130
	{mso-style-parent:style0;
	font-size:8.0pt;
	border:1.0pt solid windowtext;}
.xl131
	{mso-style-parent:style0;
	font-size:8.0pt;
	text-align:center;
	white-space:nowrap;
	mso-text-control:shrinktofit;}
.xl132
	{mso-style-parent:style0;
	font-size:8.0pt;
	mso-number-format:Standard;
	text-align:center;
	border:1.0pt solid windowtext;}
.xl133
	{mso-style-parent:style0;
	font-size:8.0pt;
	font-weight:700;
	text-align:center;
	border:1.0pt solid windowtext;}
.xl134
	{mso-style-parent:style0;
	font-size:8.0pt;
	font-weight:700;
	text-decoration:underline;
	text-underline-style:single;
	text-align:center;
	border:1.0pt solid windowtext;}
.xl135
	{mso-style-parent:style43;
	font-size:8.0pt;
	font-weight:700;
	mso-number-format:"\#\,\#\#0\.00\;\[Red\]\#\,\#\#0\.00";
	text-align:center;
	border:1.0pt solid windowtext;}
.xl136
	{mso-style-parent:style0;
	font-size:8.0pt;
	font-weight:700;
	mso-number-format:"\#\,\#\#0\.00\;\[Red\]\#\,\#\#0\.00";
	text-align:center;
	border:1.0pt solid windowtext;}
.xl137
	{mso-style-parent:style0;
	font-size:8.0pt;
	text-align:right;}
.xl138
	{mso-style-parent:style0;
	font-size:12.0pt;
	font-weight:700;
	text-align:left;
	white-space:normal;}
.xl139
	{mso-style-parent:style0;
	font-size:12.0pt;
	font-weight:700;}
.xl140
	{mso-style-parent:style0;
	font-size:12.0pt;
	font-weight:700;
	border-top:1.0pt solid windowtext;
	border-right:1.0pt solid windowtext;
	border-bottom:none;
	border-left:none;}
.xl141
	{mso-style-parent:style0;
	font-size:8.0pt;
	font-weight:700;
	text-align:right;
	
	mso-pattern:black none;}
.xl142
	{mso-style-parent:style0;
	font-size:8.0pt;
	text-align:right;
	
	mso-pattern:black none;}
.xl143
	{mso-style-parent:style0;
	font-size:8.0pt;
	font-weight:700;
	font-family:Tahoma, sans-serif;
	mso-font-charset:0;
	text-align:right;
	vertical-align:middle;
	border-top:none;
	border-right:none;
	border-bottom:1.0pt solid windowtext;
	border-left:none;}
.xl144
	{mso-style-parent:style0;
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:none;
	border-bottom:1.0pt solid windowtext;
	border-left:none;}
.xl145
	{mso-style-parent:style0;
	font-size:12.0pt;}
.xl146
	{mso-style-parent:style0;
	font-size:12.0pt;
	font-weight:700;
	text-align:center;}
.xl147
	{mso-style-parent:style0;
	font-size:12.0pt;
	
	mso-pattern:black none;}
.xl148
	{mso-style-parent:style0;
	font-size:12.0pt;
	text-align:center;}
.xl149
	{mso-style-parent:style0;
	font-size:12.0pt;
	font-weight:700;
	text-align:right;
	
	mso-pattern:black none;}
.xl150
	{mso-style-parent:style0;
	font-size:12.0pt;
	text-align:right;
	
	mso-pattern:black none;}
.xl151
	{mso-style-parent:style0;
	font-size:12.0pt;
	font-weight:700;
	font-family:Tahoma, sans-serif;
	mso-font-charset:0;
	text-align:right;
	vertical-align:middle;
	border-top:none;
	border-right:none;
	border-bottom:1.0pt solid windowtext;
	border-left:none;}
.xl152
	{mso-style-parent:style0;
	font-size:12.0pt;
	border-top:1.0pt solid windowtext;
	border-right:none;
	border-bottom:1.0pt solid windowtext;
	border-left:1.0pt solid windowtext;}
.xl153
	{mso-style-parent:style0;
	font-size:12.0pt;
	font-weight:700;
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:none;
	border-bottom:1.0pt solid black;
	border-left:none;}
.xl154
	{mso-style-parent:style0;
	font-size:12.0pt;
	font-weight:700;
	text-align:center;
	vertical-align:middle;
	border-top:1.0pt solid windowtext;
	border-right:none;
	border-bottom:1.0pt solid black;
	border-left:none;}
.xl155
	{mso-style-parent:style0;
	font-size:12.0pt;
	font-weight:700;
	text-align:center;
	vertical-align:middle;
	border-top:1.0pt solid windowtext;
	border-right:1.0pt solid windowtext;
	border-bottom:1.0pt solid black;
	border-left:none;}
.xl156
	{mso-style-parent:style0;
	font-size:12.0pt;
	border-top:none;
	border-right:none;
	border-bottom:none;
	border-left:1.0pt solid windowtext;}
.xl157
	{mso-style-parent:style0;
	font-size:12.0pt;
	font-weight:700;
	text-align:left;
	border-top:1.0pt solid black;
	border-right:none;
	border-bottom:none;
	border-left:none;}
.xl158
	{mso-style-parent:style0;
	font-size:12.0pt;
	text-align:center;
	border-top:none;
	border-right:1.0pt solid windowtext;
	border-bottom:none;
	border-left:none;}
.xl159
	{mso-style-parent:style0;
	font-size:12.0pt;
	mso-number-format:"Short Date";
	text-align:center;}
.xl160
	{mso-style-parent:style0;
	font-size:12.0pt;
	text-align:center;
	white-space:normal;
	mso-text-control:shrinktofit;}
.xl161
	{mso-style-parent:style0;
	font-size:12.0pt;
	mso-number-format:"mmm\\ yyyy";
	text-align:center;}
.xl162
	{mso-style-parent:style0;
	font-size:12.0pt;
	font-weight:700;
	text-decoration:underline;
	text-underline-style:single;
	text-align:left;}
.xl163
	{mso-style-parent:style0;
	font-size:12.0pt;
	text-align:left;}
.xl164
	{mso-style-parent:style0;
	font-size:12.0pt;
	text-align:center;
	mso-protection:unlocked visible;}
.xl165
	{mso-style-parent:style0;
	font-size:12.0pt;
	mso-number-format:"Medium Date";
	text-align:center;}
.xl166
	{mso-style-parent:style0;
	font-size:12.0pt;
	text-align:center;
	border-top:none;
	border-right:1.0pt solid windowtext;
	border-bottom:none;
	border-left:none;
	white-space:normal;
	mso-text-control:shrinktofit;}
.xl167
	{mso-style-parent:style0;
	font-size:12.0pt;
	text-align:left;
	border-top:none;
	border-right:none;
	border-bottom:1.0pt solid windowtext;
	border-left:none;}
.xl168
	{mso-style-parent:style0;
	font-size:12.0pt;
	border-top:1.0pt solid windowtext;
	border-right:none;
	border-bottom:none;
	border-left:1.0pt solid windowtext;}
.xl169
	{mso-style-parent:style0;
	font-size:12.0pt;
	font-weight:700;
	text-decoration:underline;
	text-underline-style:single;
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:1.0pt solid windowtext;
	border-bottom:1.0pt solid windowtext;
	border-left:none;}
.xl170
	{mso-style-parent:style0;
	font-size:12.0pt;
	font-weight:700;
	text-decoration:underline;
	text-underline-style:single;
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:none;
	border-bottom:1.0pt solid windowtext;
	border-left:none;}
.xl171
	{mso-style-parent:style0;
	font-size:12.0pt;
	font-weight:700;
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:.5pt solid black;
	border-bottom:1.0pt solid windowtext;
	border-left:1.0pt solid windowtext;}
.xl172
	{mso-style-parent:style0;
	font-size:12.0pt;
	font-weight:700;
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:1.0pt solid windowtext;
	border-bottom:1.0pt solid windowtext;
	border-left:.5pt solid black;}
.xl173
	{mso-style-parent:style0;
	font-size:12.0pt;
	font-weight:700;
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:none;
	border-bottom:1.0pt solid windowtext;
	border-left:none;}
.xl174
	{mso-style-parent:style0;
	font-size:12.0pt;
	font-weight:700;
	text-align:center;
	border:1.0pt solid windowtext;}
.xl175
	{mso-style-parent:style0;
	font-size:12.0pt;
	font-weight:700;
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:1.0pt solid windowtext;
	border-bottom:1.0pt solid windowtext;
	border-left:none;}
.xl176
	{mso-style-parent:style0;
	font-size:12.0pt;
	text-align:left;
	border-top:1.0pt solid windowtext;
	border-right:1.0pt solid windowtext;
	border-bottom:1.0pt solid windowtext;
	border-left:none;}
.xl177
	{mso-style-parent:style43;
	font-size:12.0pt;
	mso-number-format:"\#\,\#\#0\.00\;\[Red\]\#\,\#\#0\.00";
	text-align:center;
	border:1.0pt solid windowtext;}
.xl178
	{mso-style-parent:style43;
	font-size:12.0pt;
	mso-number-format:"\#\,\#\#0\.00\;\[Red\]\#\,\#\#0\.00";
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:none;
	border-bottom:1.0pt solid windowtext;
	border-left:1.0pt solid windowtext;}
.xl179
	{mso-style-parent:style0;
	font-size:12.0pt;
	text-align:left;
	border:1.0pt solid windowtext;}
.xl180
	{mso-style-parent:style0;
	font-size:12.0pt;
	mso-number-format:Standard;
	text-align:center;
	border:1.0pt solid windowtext;}
.xl181
	{mso-style-parent:style0;
	font-size:12.0pt;
	mso-number-format:"\#\,\#\#0\.00\;\[Red\]\#\,\#\#0\.00";
	text-align:center;
	border:1.0pt solid windowtext;}
.xl182
	{mso-style-parent:style0;
	font-size:12.0pt;
	mso-number-format:"\#\,\#\#0\.00\;\[Red\]\#\,\#\#0\.00";
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:none;
	border-bottom:1.0pt solid windowtext;
	border-left:1.0pt solid windowtext;}
.xl183
	{mso-style-parent:style0;
	font-size:12.0pt;
	text-align:left;
	border-top:none;
	border-right:1.0pt solid windowtext;
	border-bottom:none;
	border-left:none;}
.xl184
	{mso-style-parent:style0;
	font-size:12.0pt;
	mso-number-format:Standard;
	text-align:center;}
.xl185
	{mso-style-parent:style0;
	font-size:12.0pt;
	mso-number-format:Standard;
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:none;
	border-bottom:1.0pt solid windowtext;
	border-left:1.0pt solid windowtext;}
.xl186
	{mso-style-parent:style0;
	font-size:12.0pt;
	border-top:none;
	border-right:none;
	border-bottom:1.0pt solid windowtext;
	border-left:1.0pt solid windowtext;}
.xl187
	{mso-style-parent:style43;
	font-size:12.0pt;
	mso-number-format:Standard;
	text-align:center;
	border:1.0pt solid windowtext;}
.xl188
	{mso-style-parent:style43;
	font-size:12.0pt;
	mso-number-format:Standard;
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:none;
	border-bottom:1.0pt solid windowtext;
	border-left:1.0pt solid windowtext;}
.xl189
	{mso-style-parent:style0;
	font-size:12.0pt;
	font-weight:700;
	text-align:left;
	border:1.0pt solid windowtext;}
.xl190
	{mso-style-parent:style43;
	font-size:12.0pt;
	mso-number-format:"_\(* \#\,\#\#0_\)\;_\(* \\\(\#\,\#\#0\\\)\;_\(* \\-??_\)\;_\(\@_\)";
	text-align:center;
	border:1.0pt solid windowtext;}
.xl191
	{mso-style-parent:style43;
	font-size:12.0pt;
	mso-number-format:"_\(* \#\,\#\#0_\)\;_\(* \\\(\#\,\#\#0\\\)\;_\(* \\-??_\)\;_\(\@_\)";
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:none;
	border-bottom:1.0pt solid windowtext;
	border-left:1.0pt solid windowtext;}
.xl192
	{mso-style-parent:style0;
	font-size:12.0pt;
	border:1.0pt solid windowtext;}
.xl193
	{mso-style-parent:style43;
	font-size:12.0pt;
	font-weight:700;
	mso-number-format:"\#\,\#\#0\.00\;\[Red\]\#\,\#\#0\.00";
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:.5pt solid black;
	border-bottom:1.0pt solid windowtext;
	border-left:.5pt solid black;}
.xl194
	{mso-style-parent:style43;
	font-size:12.0pt;
	font-weight:700;
	mso-number-format:"\#\,\#\#0\.00\;\[Red\]\#\,\#\#0\.00";
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:1.0pt solid windowtext;
	border-bottom:1.0pt solid windowtext;
	border-left:.5pt solid black;}
.xl195
	{mso-style-parent:style43;
	font-size:12.0pt;
	font-weight:700;
	mso-number-format:"\#\,\#\#0\.00\;\[Red\]\#\,\#\#0\.00";
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:none;
	border-bottom:1.0pt solid windowtext;
	border-left:none;}
.xl196
	{mso-style-parent:style43;
	font-size:12.0pt;
	font-weight:700;
	mso-number-format:"\#\,\#\#0\.00\;\[Red\]\#\,\#\#0\.00";
	text-align:center;
	border:1.0pt solid windowtext;}
.xl197
	{mso-style-parent:style0;
	font-size:12.0pt;
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:none;
	border-bottom:none;
	border-left:none;}
.xl198
	{mso-style-parent:style0;
	font-size:12.0pt;
	text-align:center;
	border-top:none;
	border-right:none;
	border-bottom:1.0pt solid windowtext;
	border-left:none;}
.xl199
	{mso-style-parent:style0;
	font-size:12.0pt;
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:none;
	border-bottom:1.0pt solid windowtext;
	border-left:1.0pt solid windowtext;}
.xl200
	{mso-style-parent:style43;
	font-size:12.0pt;
	font-weight:700;
	mso-number-format:"\#\,\#\#0\.00\;\[Red\]\#\,\#\#0\.00";
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:1.0pt solid windowtext;
	border-bottom:1.0pt solid windowtext;
	border-left:none;}
.xl201
	{mso-style-parent:style0;
	font-size:12.0pt;
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:none;
	border-bottom:1.0pt solid windowtext;
	border-left:none;}
.xl202
	{mso-style-parent:style0;
	font-size:12.0pt;
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:1.0pt solid windowtext;
	border-bottom:1.0pt solid windowtext;
	border-left:none;}
.xl203
	{mso-style-parent:style43;
	font-size:12.0pt;
	font-weight:700;
	mso-number-format:"\#\,\#\#0\;\[Red\]\#\,\#\#0";
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:1.0pt solid windowtext;
	border-bottom:1.0pt solid windowtext;
	border-left:none;}
.xl204
	{mso-style-parent:style0;
	font-size:12.0pt;
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:1.0pt solid windowtext;
	border-bottom:none;
	border-left:none;}
.xl205
	{mso-style-parent:style0;
	font-size:12.0pt;
	text-align:center;
	border-top:1.0pt solid windowtext;
	border-right:none;
	border-bottom:none;
	border-left:1.0pt solid windowtext;}
.xl206
	{mso-style-parent:style0;
	font-size:12.0pt;
	text-align:left;
	border-top:none;
	border-right:none;
	border-bottom:none;
	border-left:1.0pt solid windowtext;}
.xl207
	{mso-style-parent:style0;
	font-size:12.0pt;
	text-align:right;}
.xl208
	{mso-style-parent:style0;
	font-size:12.0pt;
	text-align:center;
	border-top:none;
	border-right:none;
	border-bottom:none;
	border-left:1.0pt solid windowtext;}
.xl209
	{mso-style-parent:style0;
	font-size:12.0pt;
	text-align:center;
	border-top:none;
	border-right:1.0pt solid windowtext;
	border-bottom:1.0pt solid windowtext;
	border-left:none;}
.xl210
	{mso-style-parent:style0;
	font-size:12.0pt;
	text-align:center;
	border-top:none;
	border-right:none;
	border-bottom:1.0pt solid windowtext;
	border-left:1.0pt solid windowtext;}
.xl211
	{mso-style-parent:style0;
	font-size:12.0pt;
	text-align:right;
	border-top:none;
	border-right:none;
	border-bottom:1.0pt solid windowtext;
	border-left:none;}
.xl212
	{mso-style-parent:style0;
	font-size:12.0pt;
	text-align:center;
	border:1.0pt solid windowtext;}
.xl213
	{mso-style-parent:style0;
	font-size:12.0pt;
	border-top:1.0pt solid windowtext;
	border-right:none;
	border-bottom:none;
	border-left:none;}
.xl214
	{mso-style-parent:style0;
	font-size:12.0pt;
	border-top:none;
	border-right:none;
	border-bottom:1.0pt solid windowtext;
	border-left:none;}

</style>
<body link=blue vlink=purple>
<span
  style='position:absolute;margin-left:6px;width:240px;height:80px'><img width=275 height=87.5 src="http://cityeduhub.com/salaryslip/ssm/Table_files/logo.png"
  alt="LOGO"></span>
<table border=0 cellpadding=0 cellspacing=0 width=775 style='border-collapse:
 collapse;width:775pt;position: relative;'>
 <col width=7 style='mso-width-source:userset;mso-width-alt:298;width:7pt'>
 <col width=174 style='mso-width-source:userset;mso-width-alt:7424;width:174pt'>
 <col width=18 style='mso-width-source:userset;mso-width-alt:768;width:18pt'>
 <col width=160 style='mso-width-source:userset;mso-width-alt:6826;width:160pt'>
 <col width=96 style='mso-width-source:userset;mso-width-alt:4096;width:96pt'>
 <col width=6 style='mso-width-source:userset;mso-width-alt:256;width:6pt'>
 <col width=183 style='mso-width-source:userset;mso-width-alt:7808;width:183pt'>
 <col width=14 style='mso-width-source:userset;mso-width-alt:597;width:14pt'>
 <col width=137 style='mso-width-source:userset;mso-width-alt:5845;width:137pt'>
 <col width=53 span=3 style='width:53pt'>
 
 <tr height=21 style='mso-height-source:userset;height:21.0pt'>
  <td height=21 class=xl145 style='height:21.0pt'></td>
  <td colspan=8 class=xl149>City Innovates Pvt. Ltd.</td>
  <td></td>
  <td></td>
  <td></td>
 </tr>
 <tr height=21 style='mso-height-source:userset;height:21.0pt'>
  <td height=21 class=xl145 style='height:21.0pt'></td>
  <td colspan=8 class=xl150>Unit No - 58, Hartron Complex, Electronic City, Udyog Vihar, Phase - IV,<br/> Gurgaon, Haryana</td>
  <td></td>
  <td></td>
  <td></td>
 </tr>
 <tr height=21 style='mso-height-source:userset;height:21.0pt'>
  <td height=21 class=xl145 style='height:21.0pt'></td>
  <td colspan=8 class=xl151> <font class="font12">$salary_slip_heading for the month of $month</font></td>
  <td></td>
  <td></td>
  <td></td>
 </tr>
 <tr height=21 style='mso-height-source:userset;height:21.0pt'>
  <td height=21 class=xl152 style='height:21.0pt'>&nbsp;</td>
  <td class=xl153 style='border-top:none'>Particulars</td>
  <td class=xl153 style='border-top:none'>&nbsp;</td>
  <td class=xl154 style='border-top:none'>&nbsp;</td>
  <td class=xl154 style='border-top:none'>&nbsp;</td>
  <td class=xl154 style='border-top:none'>&nbsp;</td>
  <td class=xl153 style='border-top:none'>Particulars</td>
  <td class=xl153 style='border-top:none'>&nbsp;</td>
  <td class=xl155 style='border-top:none'>&nbsp;</td>
  <td></td>
  <td></td>
  <td></td>
 </tr>
 <tr height=21 style='mso-height-source:userset;height:21.0pt'>
  <td height=21 class=xl156 style='height:21.0pt'>&nbsp;</td>
  <td class=xl157 style='border-top:none'>Name of the Employee</td>
  <td class=xl146>:</td>
  <td class=xl148>$name</td>
  <td class=xl148></td>
  <td class=xl148></td>
  <td class=xl119>No of Days in the Month</td>
  <td class=xl146>:</td>
  <td class=xl158>$no_of_days</td>
  <td></td>
  <td></td>
  <td></td>
 </tr>
 <tr height=21 style='mso-height-source:userset;height:21.0pt'>
  <td height=21 class=xl156 style='height:21.0pt'>&nbsp;</td>
  <td class=xl119>Employee Code</td>
  <td class=xl146>:</td>
  <td class=xl159>$emp_code</td>
  <td class=xl148></td>
  <td class=xl148></td>
  <td class=xl119>Paid Days</td>
  <td class=xl146>:</td>
  <td class=xl158>$paid</td>
  <td></td>
  <td></td>
  <td></td>
 </tr>
 <tr height=21 style='mso-height-source:userset;height:21.0pt'>
  <td height=21 class=xl156 style='height:21.0pt'>&nbsp;</td>
  <td class=xl119>Designation</td>
  <td class=xl146>:</td>
  <td class=xl160 width=160 style='width:160pt'>$designation</td>
  <td class=xl161></td>
  <td class=xl161></td>
  <td class=xl162>LEAVES AVAILED</td>
  <td class=xl146></td>
  <td class=xl158>&nbsp;</td>
  <td></td>
  <td></td>
  <td></td>
 </tr>
MYTAB
?>
<?php
$str_leave_col="";
foreach($leave_rows as $k=>$v)
{
	$leaves_syb="";
	$empinfo_syb="";
	$empinfo_val=$sess['emp']['empinfo'][$v];
	$leave_val=$sess['emp']['leaves'][$k];
	if(trim($v)!="") $empinfo_syb=":";
	if(trim($k)!="") $leaves_syb=":";
	$str_leave_col.=<<<LEAVEROWS
	<tr height=21 style='mso-height-source:userset;height:21.0pt'>
  	<td height=21 class=xl156 style='height:21.0pt'>&nbsp;</td>
  	<td class=xl119>$v</td>
  	<td class=xl146>$empinfo_syb</td>
  	<td class=xl148>$empinfo_val</td>
  	<td class=xl161></td>
  	<td class=xl161></td>
  	<td class=xl163>$k</td>
  	<td class=xl146>$leaves_syb</td>
  	<td class=xl158>$leave_val</td>
  	<td></td>
  	<td></td>
  	<td></td>
 	</tr>
LEAVEROWS
;
}
$str.=$str_leave_col;
$str.=<<<MYTABLEAVE

 <tr height=10 style='mso-height-source:userset;height:10.0pt'>
  <td height=10 class=xl156 style='height:10.0pt'>&nbsp;</td>
  <td class=xl167>&nbsp;</td>
  <td class=xl148></td>
  <td class=xl148></td>
  <td class=xl148></td>
  <td class=xl148></td>
  <td class=xl163></td>
  <td class=xl146></td>
  <td class=xl158>&nbsp;</td>
  <td></td>
  <td></td>
  <td></td>
 </tr>
 <tr height=21 style='mso-height-source:userset;height:21.0pt'>
  <td height=21 class=xl168 style='height:21.0pt'>&nbsp;</td>
  <td class=xl169 style='border-top:none'>SALARY DETAILS</td>
  <td class=xl170><u style='visibility:hidden;mso-ignore:visibility'>&nbsp;</u></td>
  <td class=xl171>Amount (Rs)</td>
  <td class=xl172 style='border-left:none'>Amt. Payable</td>
  <td class=xl173></td>
  <td class=xl173>Deductions</td>
  <td class=xl174></td>
  <td class=xl175>Amount (Rs)</td>
  <td></td>
  <td></td>
  <td></td>
 </tr>
MYTABLEAVE
 ?>
 <?php
 $allrows="";
 $earn=$sess['emp']['earn'];
 $ded=$sess['emp']['ded'];
 $earn_payable=$sess['emp']['earn_payable'];
 if($earn['Variable']==$ded['Security'])
 {
	 $note="";
	 $note_height=948;
 }
 else
 {
	 $note="* Performance bonus disbursal certificate shall be issued seperately based on monthly performance.<br>";
	 $note.="It will not be reflected as a part of monthly salary slip";
	 if($sess['emp']['empinfo']['Department']=="BD")
	 {
		 $note.=" and is directly linked to incentive scheme as<br> mutually discussed & agreed.";
		 $note_height=923;
	 }
	 else
	 {
	 	$note.=".";
		$note_height=948;
	 }
 }
 foreach($xls_rows as $k=>$v)
 {
	 $note_height-=30;
	 $ded_syb="";
 	 $earn_syb="";
	 $amt=number_format(0.0,2);
	 $amt_ded=number_format(0.0,2);
	 $amt_pay=number_format(0.0,2);
	 if(is_numeric($earn[$k])) $amt=number_format($earn[$k],2);
	 if($amt=="0.00") $amt="";
	 
	 if(array_key_exists($k,$earn_payable)) $amt_pay=number_format($earn_payable[$k],2);
	 if($amt=="") $amt_pay="";
	 
	 if(is_numeric($ded[$v])) $amt_ded=number_format($ded[$v],2);
	 	 
	 if(trim($v)!="") $ded_syb=":";
	 if(trim($k)!="") $earn_syb=":";
	 if($k=="Variable") $k="Variable*<br/>(Performance Bonus)";
	 if($intern_flag==true&&$k=="Basic Salary") $k="Stipend";
	 $allrows.=<<<MYROW
 <tr height=21 style='mso-height-source:userset;height:21.0pt'>
  <td height=21 class=xl152 style='height:21.0pt'></td>
  <td class=xl176 style='border-top:none'>$k</td>
  <td class=xl174 style='border-top:none;border-left:none'>$earn_syb</td>
  <td class=xl177 style='border-top:none;border-left:none'>$amt</td>
  <td class=xl177 style='border-top:none;border-left:none'>$amt_pay</td>
  <td class=xl178 style='border-top:none;border-left:none'></td>
  <td class=xl176 style='border-top:none'>$v</td>
  <td class=xl174 style='border-top:none;border-left:none'>$ded_syb</td>
  <td class=xl177 style='border-top:none;border-left:none'>$amt_ded</td>
  <td></td>
  <td></td>
  <td></td>
 </tr>
MYROW
;
 }
 $total_d=$sess['emp']['total_ded'];
 $net_pay=$sess['emp']['net_pay'];
 $gross_pay=$sess['emp']['gross_pay'];
 $gross=$sess['emp']['gross'];
 $salary_in_hand=$sess['emp']['salary_in_hand'];
 $str.=$allrows;
 ?>
 <?php
$str.=<<<MYTAB2
<tr height=21 style='mso-height-source:userset;height:21.0pt'>
  <td height=21 class=xl186 style='height:21.0pt'>&nbsp;</td>
  <td class=xl173 style='border-top:none'>Gross Salary</td>
  <td class=xl173 style='border-top:none'>&nbsp;</td>
  <td class=xl193 style='border-top:none'>$gross</td>
  <td class=xl194 style='border-top:none;border-left:none'>$gross_pay</td>
  <td class=xl195 style='border-top:none'>&nbsp;</td>
  <td class=xl173 style='border-top:none'>Total Deductions</td>
  <td class=xl173 style='border-top:none'>&nbsp;</td>
  <td class=xl196 style='border-top:none'>$total_d</td>
  <td></td>
  <td></td>
  <td></td>
 </tr>
 <tr height=21 style='mso-height-source:userset;height:21.0pt'>
  <td height=21 class=xl156 style='height:21.0pt'>&nbsp;</td>
  <td class=xl197 style='border-top:none'>&nbsp;</td>
  <td class=xl148></td>
  <td class=xl148></td>
  <td class=xl158>&nbsp;</td>
  <td class=xl148></td>
  <td class=xl148></td>
  <td class=xl148></td>
  <td class=xl190 style='border-top:none'>&nbsp;</td>
  <td></td>
  <td></td>
  <td></td>
 </tr>
 <tr height=21 style='mso-height-source:userset;height:21.0pt'>
  <td height=21 class=xl156 style='height:21.0pt'>&nbsp;</td>
  <td class=xl198>&nbsp;</td>
  <td class=xl198>&nbsp;</td>
  <td class=xl198>&nbsp;</td>
  <td class=xl158>&nbsp;</td>
  <td class=xl199 style='border-left:none'>&nbsp;</td>
  <td class=xl173>NET PAY</td>
  <td class=xl175>&nbsp;</td>
  <td class=xl200 style='border-top:none'>$net_pay</td>
  <td></td>
  <td></td>
  <td></td>
 </tr>
 <tr height=21 style='mso-height-source:userset;height:21.0pt'>
  <td height=21 class=xl152 style='height:21.0pt'>&nbsp;</td>
  <td class=xl144 style='border-top:none'></td>
  <td class=xl144 style='border-top:none'>&nbsp;</td>
  <td class=xl144 style='border-top:none'>&nbsp;</td>
  <td class=xl202>&nbsp;</td>
  <td class=xl201 style='border-top:none'>&nbsp;</td>
  <td class=xl173 style='border-top:none'>Salary in Hand*</td>
  <td class=xl202 style='border-top:none'>&nbsp;</td>
  <td class=xl203 style='border-top:none'>$salary_in_hand</td>
  <td></td>
  <td></td>
  <td></td>
 </tr>
<!-- </table>
 <span style='font-family: arial;
  font-size: 12px;
  margin-top: 10px;
  display: inline-block;'>$note</span>
 <table border=0 cellpadding=0 cellspacing=0 width=702 style='border-collapse:
 collapse;width: 702pt;'>-->
 <tr height=21 style='mso-height-source:userset;height:21.0pt'>
  <td height=21 class=xl213 style='height:21.0pt;border-top:none'>&nbsp;</td>
  <td class=xl145></td>
  <td class=xl145></td>
  <td class=xl145></td>
  <td class=xl145></td>
  <td class=xl145></td>
  <td class=xl145></td>
  <td class=xl145></td>
  <td class=xl145></td>
  <td></td>
  <td></td>
  <td></td>
 </tr>
 <tr height=21 style='mso-height-source:userset;height:21.0pt'>
  <td height=21 class=xl145 style='height:21.0pt'></td>
  <td class=xl119>For City Innovates Pvt. Ltd.</td>
  <td class=xl119></td>
  <td class=xl119></td>
  <td class=xl148></td>
  <td class=xl148></td>
  <td class=xl148></td>
  <td class=xl148></td>
  <td class=xl148></td>
  <td></td>
  <td></td>
  <td></td>
 </tr>

MYTAB2
;
if($sess['status']!=0){
$str.=<<<MYTAB3
<tr height=21 style='mso-height-source:userset;height:21.0pt'>
  <td height=21 class=xl214 style='height:21.0pt'>&nbsp;</td>
  <td class=xl138 width=174 style='width:174pt'></td>
  <td class=xl138 width=18 style='width:18pt'></td>
  <td class=xl138 width=160 style='width:160pt'></td>
  <td class=xl148></td>
  <td class=xl148></td>
  <td class=xl148></td>
  <td class=xl148></td>
  <td class=xl148></td>
  <td></td>
  <td></td>
  <td></td>
 </tr>
 <tr height=21 style='mso-height-source:userset;height:21.0pt'>
  <td height=21 class=xl168 style='height:21.0pt;border-top:none'>&nbsp;</td>
  <td class=xl197>&nbsp;</td>
  <td class=xl204>&nbsp;</td>
  <td class=xl205 style='border-left:none'>&nbsp;</td>
  <td class=xl204>&nbsp;</td>
  <td class=xl197>&nbsp;</td>
  <td class=xl197>&nbsp;</td>
  <td class=xl197>&nbsp;</td>
  <td class=xl204>&nbsp;</td>
  <td></td>
  <td></td>
  <td></td>
 </tr>
 <tr height=21 style='mso-height-source:userset;height:21.0pt'>
  <td height=21 class=xl156 style='height:21.0pt'>&nbsp;</td>
  <td class=xl163>Prepared by</td>
  <td class=xl148></td>
  <td class=xl206>&nbsp;&nbsp;Checked by</td>
  <td class=xl158>&nbsp;</td>
  <td class=xl148></td>
  <td class=xl163>Authorised by</td>
  <td class=xl148></td>
  <td class=xl158>&nbsp;</td>
  <td></td>
  <td></td>
  <td></td>
 </tr>
 <tr height=21 style='mso-height-source:userset;height:21.0pt'>
  <td height=21 class=xl186 style='height:21.0pt'>&nbsp;</td>
  <td class=xl207></td>
  <td class=xl158>&nbsp;</td>
  <td class=xl208 style='border-left:none'>&nbsp;</td>
  <td class=xl158>&nbsp;</td>
  <td class=xl148></td>
  <td class=xl148></td>
  <td class=xl148></td>
  <td class=xl158>&nbsp;</td>
  <td></td>
  <td></td>
  <td></td>
 </tr>
 <tr height=21 style='mso-height-source:userset;height:21.0pt'>
  <td height=21 class=xl156 style='height:21.0pt'>&nbsp;</td>
  <td class=xl197>&nbsp;</td>
  <td class=xl204>&nbsp;</td>
  <td class=xl205 style='border-left:none'>&nbsp;</td>
  <td class=xl204>&nbsp;</td>
  <td class=xl197>&nbsp;</td>
  <td class=xl197>&nbsp;</td>
  <td class=xl197>&nbsp;</td>
  <td class=xl204>&nbsp;</td>
  <td class=xl121></td>
  <td class=xl121></td>
  <td class=xl121></td>
 </tr>
 <tr height=21 style='mso-height-source:userset;height:21.0pt'>
  <td height=21 class=xl156 style='height:21.0pt'>&nbsp;</td>
  <td class=xl163>For Approvals</td>
  <td class=xl158>&nbsp;</td>
  <td class=xl206 style='border-left:none'>&nbsp;&nbsp;CEO</td>
  <td class=xl158>&nbsp;</td>
  <td class=xl148></td>
  <td class=xl163>Governing Body</td>
  <td class=xl148></td>
  <td class=xl158>&nbsp;</td>
  <td></td>
  <td></td>
  <td></td>
 </tr>
 <tr height=21 style='mso-height-source:userset;height:21.0pt'>
  <td height=21 class=xl186 style='height:21.0pt'>&nbsp;</td>
  <td class=xl198>&nbsp;</td>
  <td class=xl209>&nbsp;</td>
  <td class=xl210 style='border-left:none'>&nbsp;</td>
  <td class=xl209>&nbsp;</td>
  <td class=xl198>&nbsp;</td>
  <td class=xl211>&nbsp;</td>
  <td class=xl198>&nbsp;</td>
  <td class=xl209>&nbsp;</td>
  <td></td>
  <td></td>
  <td></td>
 </tr>
 <tr height=21 style='mso-height-source:userset;height:21.0pt'>
  <td height=21 class=xl213 style='height:21.0pt;border-top:none'>&nbsp;</td>
  <td class=xl148></td>
  <td class=xl148></td>
  <td class=xl148></td>
  <td class=xl148></td>
  <td class=xl148></td>
  <td class=xl148></td>
  <td class=xl148></td>
  <td class=xl148></td>
  <td></td>
  <td></td>
  <td></td>
 </tr>
 <tr height=21 style='mso-height-source:userset;height:21.0pt'>
  <td height=21 class=xl214 style='height:21.0pt'>&nbsp;</td>
  <td class=xl139>Employee Signature<span
  style="mso-spacerun:yes">&nbsp;</span></td>
  <td class=xl139></td>
  <td class=xl148></td>
  <td class=xl148></td>
  <td class=xl148></td>
  <td class=xl146>Date:</td>
  <td class=xl148></td>
  <td class=xl148></td>
  <td></td>
  <td></td>
  <td></td>
 </tr>
 <tr height=21 style='mso-height-source:userset;height:21.0pt'>
  <td height=21 class=xl168 style='height:21.0pt;border-top:none'>&nbsp;</td>
  <td class=xl197>&nbsp;</td>
  <td class=xl197>&nbsp;</td>
  <td class=xl140>&nbsp;</td>
  <td class=xl148></td>
  <td class=xl148></td>
  <td class=xl212>&nbsp;</td>
  <td class=xl148></td>
  <td class=xl148></td>
  <td></td>
  <td></td>
  <td></td>
 </tr>
 <tr height=21 style='mso-height-source:userset;height:21.0pt'>
  <td height=21 class=xl186 style='height:21.0pt'>&nbsp;</td>
  <td class=xl198>&nbsp;</td>
  <td class=xl198>&nbsp;</td>
  <td class=xl209>&nbsp;</td>
  <td class=xl148></td>
  <td class=xl148></td>
  <td class=xl148></td>
  <td class=xl148></td>
  <td class=xl148></td>
  <td></td>
  <td></td>
  <td></td>
 </tr>
 <tr height=21 style='mso-height-source:userset;height:21.0pt'>
  <td height=21 style='height:21.0pt;'></td>
  <td></td>
  <td></td>
  <td></td>
  <td></td>
  <td></td>
  <td></td>
  <td></td>
  <td></td>
  <td></td>
  <td></td>
  <td></td>
 </tr>
MYTAB3
;}
$str.=<<<MYTAB4
</table>
<span style='position: absolute;font-size:14px;bottom:$note_height px;left: 1%;text-align: left;'>$note</span>
<span>*This is an electronically generated salary slip, hence does not require signatures. Once email sent, will be treated as counter signed by an employee</span>
</body>
</html>
MYTAB4
?>
<?php
if(fwrite($filehandler,iconv('macintosh','UTF-8',$str)))
{
	fclose($filehandler);
	//print_r($db_entry);
	//$file2=$_SERVER['DOCUMENT_ROOT']."/ssm/".str_replace(" ","\ ",$filename);
	//$file_pdf=$_SERVER['DOCUMENT_ROOT']."/ssm/".str_replace(" ","\ ",$filename).".pdf";
	//exec("/usr/local/bin/wkhtmltopdf $file2 $file_pdf",$output);
	//if((file_exists($filename.".pdf")))
	{
		if($db_bool===true)
		update_database($db_entry);
		//unlink($filename);
		return $filename;//.".pdf";
	}
	//else
	{
		//return "Error while generating pdf, please try again";
	}
}
else
{
	echo "Error generating Salary Slip file. Please try again."."<br/>";
	return NULL;
}
?>
<?php return $filename;} ?>