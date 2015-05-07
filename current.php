<?php
include_once 'session.php';

if($_SESSION['user']=='admin'||$_SESSION['user']=='payslipadmin') { ?>
<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<title>Manage Salary Slips</title>
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
function select_all()
{
	if(document.getElementById('all').checked==true)
    $(":checkbox").prop('checked',true);
	else
	$(":checkbox").prop('checked',false);
}
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
    });
	
}
$(document).ready(function(){
	$(document).ajaxStart(function () {
        $("#loading").show();
    }).ajaxStop(function () {
        $("#loading").hide();
    });
	$(document).ajaxError(function(event, jqXHR, ajaxOptions, thrownError) {
        alert(thrownError);
		location.href="500.html";
    });
	$(".empnames").click(function(){
		$('#user').val(this.id);
			getslip($('#slips_date').val());
	});
	$('#print_button').click(function() {
		if($('#slip').children().length!=0)
		{
			parent.document.getElementById('sliphtml').contentWindow.print();
		}
		else
        alert("Select a valid salary slip");
    });
<?php if($_SESSION['user']=='admin'){?>
	$('#emailall_div').click(function() {
        $('#lightbox').show();
    });
	$('#emailall_close_button').click(function() {
        $('#lightbox').hide();
    });
	$('#printall_button').click(function() {
		var codes=[];
		var date=$('#slips_date').val();
		$(':checked').each(function() {
           if(this.name=='id_list[]')
		   {
			   codes.push(this.value);
		   }
        });
		codes=codes.join(";");
		$.post("view.php",
   				{
					list:codes,
					date:date,
					func:"printall"
    			},
    			function(data){
					$('#slip').html(data);
					if($('#slip').children().length!=0)
					{
						parent.document.getElementById('sliphtml').contentWindow.print();
					}
    			});
		
	});
	$('#downloadall_button').click(function() {
		$(".email_result").remove();
        var codes=[];
		var date=$('#slips_date').val();
		$(':checked').each(function() {
           if(this.name=='id_list[]')
		   {
			   $(this).parent().append("<img id=\'"+this.value+"_img\' src='images/ajax-loader.gif' alt='processing'/>");
			   codes.push(this.value);
		   }
        });
		var i=0;
		function runme(code)
		{
			$.post("view.php",
			{
				code:code,
				date:date,
				func:"downloadall"
			},
			function(data){
				var node=document.getElementById(code+'_img');
				$(node).parent().append("<div class='email_result'>"+data+"</div>");
				node.parentNode.removeChild(node);
				if(++i<codes.length)
				runme(codes[i]);
				else
				location.href="slips/slips.zip";  
			});
	    }
		$.post("view.php",{func:"createzip",date:""},
				function(data){
					if(data=="yes")
					{
						runme(codes[0]);		
					}
					else
					alert(data);
				});
    });
	$('#emailall_button').click(function() {
		$(".email_result").remove();
		$('#lightbox').hide();
		var c=confirm("Do you want to email all the selected Salary Slips?");
		if(c){
		var to_email="";
		if($('#emailall_choice_user').is(':checked'))
		var choice='user';
		else if($('#emailall_choice_custom').is(':checked'))
		{
			var choice='custom';
			to_email=$('#custom_email').val();
		}
		
		var date=$('#slips_date').val();
		var user;
		var codes=[];
		$(':checked').each(function() {
           if(this.name=='id_list[]')
		   {
			   user=this.value;
			   codes.push(user);
			   $(this).parent().append("<img id=\'"+user+"_img\' src='images/ajax-loader.gif' alt='processing'/>");
		   }
        });
		var i=0;
			function ajaxemail(code)
			{
				$.post("view.php",
				{
					code:""+code,
					date:""+date,
					func:"emailall",
					choice:""+choice,
					to_email:""+to_email
				},
				function(data)
				{
					var node=document.getElementById(code+'_img');
					$(node).parent().append("<div class='email_result'>"+data+"</div>");
					node.parentNode.removeChild(node);
					if(++i<codes.length)
					ajaxemail(codes[i]);
					//
					//
					//$(node).parent().append("<div class='email_result'>"+data+"</div>");
				});
			}
			ajaxemail(codes[0]);		
		}//if ends
	});
	$("#email_button").click(function(){
		var user=$('#user').val();
		var date=$('#slips_date').val();
		if(document.getElementById('sliphtml'))
		{
			$('#email_box').show();
		}
		else
		{
			$('#slip').html("<h3>Select a valid salary slip</h3>");
		}
	});
	
	$("#email_submit").click(function(){
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
	
	$("#email_close").click(function(){
		$('#email_box').hide();
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
<?php } ?>
});
</script>
</head>
<body>
<div id="panel_header">
<a href="signout.php"><img id="signout" src="images/exit.png" alt="sign out"/></a>
<a href="<?php if($_SESSION['user']=='admin') echo "generate.php"; else echo "adminpanel.php";?>"><img id="home" src="images/home.png" alt="go home"/></a>
<div id="optionbar">
<input type="hidden" id="user" name="user" value="<?php echo $_SESSION['user']; ?>"/>
<input type="hidden" id="logindate" value="<?php echo $_SESSION['date']; ?>"/>

<select id="slips_date">
<?php

$month=date('m',time());
$year=date('Y',time());

for($i=0;$i<12;$i++)
{
	$date_val=$month."/".$year;
	$date=$month."/27/".$year;
	$date_view=date('F Y',strtotime($date));
	echo "<option value='$date_val'>$date_view</option>";
	$month--;
	if($month<=0)
	{
		$year--;
		$month=12;
	}
}
?>
</select>

<input type="button" id="print_button" value="Print"/>
<input type="button" id="download_slip_button" value="Download"/>
<?php if($_SESSION['user']!='payslipadmin'){
	echo "<input type='button' id='email_button' value='Email'/>";
	echo "<div id='email_box'>";
	echo "<input type='email' id='email_text' style='width:85%;margin:22px'/><br/>";
	echo "<input type='button' name='email_submit' id='email_submit' value='Send'/>";
	echo "<input type='button' name='email_close' id='email_close' value='Close'/>";
	echo "</div>";
}?>
</div>
</div>
<?php

{
	include 'db_conf.php';
	echo "<div id='sidebar'><ul id='emp_list'>";

	$query="Select `Employee Code`,`Name of the Employee` from `employee_info` where 1 ORDER BY `Name of the Employee`";
	$res = $mysqli->query($query) or die($mysqli->error);
	while($row=$res->fetch_Assoc())
	{
		$code=$row['Employee Code'];
		$name=$row['Name of the Employee'];
		$cbid=$code."_cb";
		echo "<li>";
		if($_SESSION['user']!='payslipadmin')
		{
			echo "<div class='squaredThree'><input type='checkbox' name='id_list[]' id='$cbid' value='$code'/><label for='$cbid'></label>";
		}
		echo "<span name='$name' class='empnames' id='$code'>$name</span>";
		if($_SESSION['user']!='payslipadmin')
		{
			echo "</div>";
		}
		echo "</li>";
	}
	if($_SESSION['user']!='payslipadmin')
	{
		echo "<li><div class='squaredThree'><input type='checkbox' id='all' name='all' value='all' onChange='select_all()'/><label for='all'></label>Select all</div></li>";
		echo "<li><input type='button' id='printall_button' name='printall_button' value='Print'/>";
		echo "<input type='button' id='downloadall_button' value='Download as Zip'/>";
		echo "<input type='button' id='emailall_div' name='emailall_button' value='Email'/></li>";
	}
	echo "</ul></div>";
}
?>
<div id="slip">
</div>
<div id="lightbox">
<div id="emailallbox">
<input type="radio" id="emailall_choice_user" name="emailall_choice" value="user" checked/>Employee Emails
<input type="radio" id="emailall_choice_custom" name="emailall_choice" value="custom"/>Custom Email<br/>
<input type="email" name="custom_email" id="custom_email"/><br/>
<input type="button" id="emailall_button" value="Send Email"/>
<input type="button" id="emailall_close_button" value="Close"/>
</div>
</div>
<div id='loading'><img src="images/ajax.gif" alt="loading"/></div>
</body>
<?php } else {header("Location:index.php");} ?>