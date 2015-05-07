<?php
include_once 'session.php';
if(isset($_SESSION['user'])) { ?>
<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<title>View Salary Slips</title>
<?php include_once "favicon.html"; ?>
<link rel="stylesheet" href="bodystyle.css" type="text/css"/>
<link rel="stylesheet" href="homestyle.css" type="text/css"/>
<link rel="stylesheet" href="lightbox.css" type="text/css"/>
<?php
	if($_SESSION['user']=='admin')
	echo "<link rel='stylesheet' href='checkbox_style.css' type='text/css'/>";
?>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
<script>

function getslip(date)
{
	var user=$('#user').val();
	$.post("view.php",
    {
        code: ""+user,
        date: ""+date,
		func:""
    },
    function(data){
        $('#slip').html(data);
		var status=$('#sliphtml').attr('name');
    });
	
}
$(document).ready(function(){
<?php if($_SESSION['user']!='admin') { ?>
	if($('#logindate').val()!="")
	{
		var date=$('#logindate').val();
		var user=$('#user').val();
		$.post("view.php",
		{
			code: ""+user,
			date: ""+date,
			func:"userview"
		},
		function(data){
			$('#slip').html(data);
		});
	}
	else
	alert("No date selected");
	$('#view_button').click(function() {
        var date=$('#slips_date').val();
		var user=$('#user').val();
		$.post("view.php",
		{
			code: ""+user,
			date: ""+date,
			func:"userview"
		},
		function(data){
			$('#slip').html(data);
		});
    });
<?php } else {?>
	$(".empnames").click(function(){
		$('#user').val(this.id);
			$('#slips_date').html("");
			var user=this.id;
			$.post("view.php",
			{
				code: ""+user,
				date: "",
				func: ""
			},
			function(data){
				$('#slips_date').html(data);
			});	
	});
	
	$("#email_button").click(function(){
		var user=$('#user').val();
		var date=$('#slips_date').val();

		$.post("view.php",
		{
			code:""+user,
			date:""+date,
			func:"getemail"
		},
		function(data)
		{
			$('#email_text').val(data);
			$('#email_box').show();
		});
	});
	
	$("#email_submit").click(function(){
		var user=$('#user').val();
		var date=$('#slips_date').val();
		$.post("view.php",
		{
			code: ""+user,
			date: ""+date,
			func:"userview"
		},
		function(data){
			$('#slip').html(data);
			var filename=$('#sliphtml').attr('src');
			var to_email=$('#email_text').val();
			$.post("email.php",
			{
				total_emails:1,
				from:"hr.cityinnovates@gmail.com",
				fromname:"HR Admin",
				to_email:to_email,
				subject:"Salary Slip",
				filename:filename,
				msg:"Salary Slip"
			},
			function(data){
				if(data=='true')
				{
					alert('Email sent to '+to_email);
					$('#email_box').hide();
				}
				else
					alert(data);
				});
			});
		});
	
	$("#email_close").click(function(){
		$('#email_box').hide();
	});
	$('#view_button').click(function() {
        getslip($('#slips_date').val());
    });
	
<?php } ?>
	$('#print_button').click(function() {
		if($('#slip').children().length!=0)
		{
			document.getElementById('sliphtml').contentWindow.print();
		}
		else
        alert("Select a valid salary slip");
    });
	$('#download_slip_button').click(function() {
		if($('#slip').children().length!=0)
		{
			var filename=document.getElementById('sliphtml').contentWindow.location.href;
        	location.href="dompdf/dompdf.php?input_file="+filename+"&&paper=a3";
		}
		else
        alert("Select a valid salary slip");
    });
	$(document).ajaxStart(function () {
        $("#loading").show();
    }).ajaxStop(function () {
        $("#loading").hide();
    });
});
</script>
</head>
<body>
<div id="panel_header" <?php if($_SESSION['user']!='admin') echo "style='margin-left:0;width:100%;'"; ?>>
<a href="signout.php"><img id="signout" src="images/exit.png" alt="sign out"/></a>
<a href="generate.php"><img id="home" src="images/home.png" alt="go home"/></a>
<div id="optionbar">
<input type="hidden" id="user" name="user" value="<?php echo $_SESSION['user']; ?>"/>
<input type="hidden" id="logindate" value="<?php echo $_SESSION['date']; ?>"/>

<select id="slips_date">

<?php if($_SESSION['user']!='admin') { ?>
<?php
include 'db_conf.php';

$code=$_SESSION['user'];
$query="Select `month`,`year` from `salary_slips` where `Employee Code`='$code'";
$res = $mysqli->query($query) or die($mysqli->error);
while($row=$res->fetch_Assoc())
{
	$date=$row['month']."/".$row['year'];
	$date_view=date('F Y',strtotime($row['month']."/27/".$row['year']));
	echo "<option value='$date'>$date_view</option>";
}
?>
<?php } ?>
</select>

<input type="button" id="view_button" value="View"/>
<input type="button" id="download_slip_button" value="Download"/>
<input type="button" id="print_button" value="Print"/>
<?php if($_SESSION['user']=='admin') 
	{
		echo "<input type='button' id='email_button' value='Email'/>";
		echo "<div id='email_box'>";
		echo "<input type='email' id='email_text' style='width:85%;margin:22px'/><br/>";
		echo "<input type='button' name='email_submit' id='email_submit' value='Send'/>";
		echo "<input type='button' name='email_close' id='email_close' value='Close'/>";
		echo "</div>";
	}
?>
</div><!--option bar ends-->
</div><!--header ends-->
<?php
if($_SESSION['user']=='admin')
{
	include 'db_conf.php';
	echo "<div id='sidebar'><ul id='emp_list'>";

	$query="Select `Employee Code`,`Name of the Employee` from `employee_info` where 1 ORDER BY `Name of the Employee`";
	$res = $mysqli->query($query) or die($mysqli->error);
	while($row=$res->fetch_Assoc())
	{
		$code=$row['Employee Code'];
		$name=$row['Name of the Employee'];
		echo "<li name='$name' class='empnames' id='$code'>";
		echo "$name</li>";
	}
	echo "</ul></div>";
}
?>
<div id="slip" <?php if($_SESSION['user']!='admin') echo "style='margin-left:0;width:100%;'"; ?>>
</div>
<div id='loading'><img src="images/ajax.gif" alt="loading"/></div>
</body>
<?php } else {header("Location:index.php");} ?>