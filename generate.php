<?php include_once 'session.php';
if($_SESSION['user']=='admin'){
?>
<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<title>Generate Salary Slips</title>
<?php include_once "favicon.html"; ?>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
<link rel="stylesheet" href="generate_style.css" type="text/css"/>
<link rel="stylesheet" href="bodystyle.css" type="text/css"/>
<link rel="stylesheet" href="checkbox_style.css" type="text/css"/>
<link rel="stylesheet" href="widget.css" type="text/css"/>
<script type="text/javascript" src="datepicker/zebra_datepicker.js"></script>
<link rel="stylesheet" href="datepicker/css/bootstrap.css" type="text/css">
</head>
<script>
//set up names of employees
function setupemployees()
{
	if(document.getElementById('date').value=="")
	{
		alert("Please select a valid date");
		return false;
	}
	var xmlhttp;
	/*document.getElementById('manual').style.display="none";
	document.getElementById('date').style.display="none";
	document.getElementById('sel_date').style.display="none";
	document.getElementsByClassName('Zebra_DatePicker_Icon_Inside')[0].style.display="none";*/
	$(".right_div").hide();
	if (window.XMLHttpRequest)
	  {// code for IE7+, Firefox, Chrome, Opera, Safari
	  	xmlhttp=new XMLHttpRequest();
	  }
	else
	  {// code for IE6, IE5
	  	xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	  }
	  xmlhttp.open("GET","manual.php?emp=yes",true);
	  //xmlhttp.setRequestHeader("Content-type","multipart/form-data");
	  xmlhttp.send();
	  xmlhttp.onreadystatechange=
	  function()
	  {
  		if (xmlhttp.readyState==4 && xmlhttp.status==200)
    	{
    		document.getElementById('table_names').innerHTML=xmlhttp.responseText;
			$('#manual_form').show();
    	}
  	  };
}
function validate()
{
	var flag=false;
	var x=document.getElementsByName('id_list[]');
	for(var i=0;i<x.length;i++)
	{
		if(x[i].checked==true)
		{
			flag=true;
		}
	}
	if(flag==false)
	{
		alert("Please select at least one name.");
	}
	return flag;
}
function setupleaves()
{
	$.get("manual.php",
	{
		func:'get_emp_names'
	},
	function(data){
		document.getElementById('leave_names').innerHTML=data;
	});
}
function validate_upload()
{
	var image=document.getElementById('excelfile');
	var flag=true;
	var x=image.value;
	if(x!="")
	{
		if(x.toLowerCase().match(/^[\w\W]+\.+['xls','xlsx']+$/))
		{
			if(document.getElementById('date2').value!="")
			{
				return true;
			}
			else
			alert("Please select a valid date");
		}
	}
	else
	{
		alert("Please select a valid file");
	}
	return false;
}
function select_all()
{
	if(document.getElementById('all').checked==true)
    $(":checkbox").prop('checked',true);
	else
	$(":checkbox").prop('checked',false);
}
$(document).ready(function(){
	$(document).ajaxStart(function () {
        $("#loading").show();
    }).ajaxStop(function () {
        $("#loading").hide();
    });
	$("#zero0").click(function(){
		window.location.href = "signout.php";
	});
	$("#zero1").click(function(){
		window.location.href = "edit_info.php";
	});
	$("#zero2").click(function(){
		$(".right_div").hide();
        $("#edit_emp").show();
		toggle(this);
	});
    $("#one_bk").click(function(){
		$(".right_div").hide();
		$.get("manual.php",
				{
					func:"get_info"
				},
				function(data){
					document.getElementById('employee_info_table').innerHTML=data;
				});
        //$("#manual_select").show();
		$("#employee_info").show();
		toggle(this);
    });
	$("#one").click(function(){
		$(".right_div").hide();
        $("#approve").show();
		toggle(this);
    });
   $("#two").click(function(){
	    $(".right_div").hide();
		$("#excel_form").show();
		toggle(this);
    });
	$("#three").click(function(){
		window.location.href = "home.php";
	});
	$("#four").click(function(){
		window.location.href = "edit.php";
	});
	$("#five").click(function(){
		window.location.href = "current.php";
	});
	$("#six").click(function(){
		$(".right_div").hide();
        $("#mss").show();
		toggle(this);
    });
	$("#seven").click(function(){
		$(".right_div").hide();
		$("#leaves_select").show();
		toggle(this);
    });
	$("#leaves_submit").click(function(){
		$(".right_div").hide();
		var code,date;
		if(code=$('input[name=leaves_code]:checked').val()){}
		else code="all";
		if(date=$('#leaves_date').val()){}
		else date="all";
		$.get("manual.php",
				{
					func:"get_leaves",
					code:code,
					month:date
				},
				function(data){
					document.getElementById('employee_info_table').innerHTML=data;
				});
		$("#employee_info").show();
    });
	$("#excelfile_button").click(function () {
        $('#excelfile').click();
    });
	$('#leaves_date').Zebra_DatePicker({format:'m/Y'});
	$('#date2').Zebra_DatePicker({format:'d-m-Y'});
	$('#mss_date').Zebra_DatePicker({format:'d-m-Y'});
	$('.delete_slips_button').click(function(e) {
        if(confirm("Do you really want to delete this file?"))
		{
			var filecode=$(this).parent().parent().attr('id');
			$.post("setactive.php",
				{
					filecode:filecode,
					func:'delete'
				},
				function(data)
				{
					if(data=='success')
					{
						$('#'+filecode).remove();
					}
					else
					alert(data);
				}
			);
		}
    });
	$('.send_approval_button').click(function(e) {
        if(confirm("Do you really want to send this file for approval?"))
		{
			var filecode=$(this).parent().parent().attr('id');
			$.post("setactive.php",
				{
					filecode:filecode,
					func:'sendapproval'
				},
				function(data)
				{
					if(data=='success')
					{
						location.href="generate.php";
					}
					else
					alert(data);
				}
			);
		}
    });
	$('.generate_slips_button').click(function(e) {
        if(confirm("Do you really want to permanently save these slips?"))
		{
			var filecode=$(this).parent().parent().attr('id');
			$.post("setactive.php",
				{
					filecode:filecode,
					func:'generate'
				},
				function(data)
				{
					if(data=='success')
					{
						$('#'+filecode).remove();
						alert("Successfully generated salary slips.");
					}
					else
					alert(data);
				}
			);
		}
    });
	$('#download_button').click(function() {
		if(document.getElementById('date2').value!="")
			{
				location.href="get_excel.php?&date="+document.getElementById('date2').value;
			}
			else
			alert("Please select a valid date");
    });
	$('#mss_download_button').click(function() {
		if(document.getElementById('mss_date').value!="")
			{
				location.href="get_mss.php?&date="+document.getElementById('mss_date').value;
			}
			else
			alert("Please select a valid date");
    });
	$('#one').trigger('click');
});
function deactivate_user(user)
{
	if(confirm("Do you really want to deactivate this user/employee?"))
	{
		$.post("setactive.php",{code:user,func:"deactivate"},function(data){
			if(data!="success")
			alert(data);
			else
			{
				$('#edit_button_'+user).removeClass('download_button_class');
				$('#edit_button_'+user).addClass('green_button');
				$('#edit_button_'+user).val('Activate');
				$('#edit_button_'+user).attr('onclick',"activate_user(\'"+user+"\')");
				alert("Successfully Deactivated "+user);
			}
		});
	}
}
function activate_user(user)
{
	if(confirm("Do you really want to activate this user/employee?"))
	{
		$.post("setactive.php",{code:user,func:"activate"},function(data){
			if(data!="success")
			alert(data);
			else
			{
				$('#edit_button_'+user).removeClass('green_button');
				$('#edit_button_'+user).addClass('download_button_class');
				$('#edit_button_'+user).val('Deactivate');
				$('#edit_button_'+user).attr('onclick',"deactivate_user(\'"+user+"\')");
				alert("Successfully Activated "+user);
			}
		});
	}
}
function tabletoexcel(table_id)
{
	$.post("tabletoexcel.php?table=set",
			{
				tabledata:document.getElementById(table_id).innerHTML
			},
			function(data){
				location.href="tabletoexcel.php?table=get";
			});
}
function toggle(a) {
    $('.selected').removeClass('selected');
    $(a).addClass('selected');
};
</script>

<body>
<div id="panel_header">
<div id='left_header'>
<span>HR Admin</span>
</div>
<div id='center_header'>
<a href="generate.php"><span>Salary Slip Generator</span></a>
</div>
<div id='right_header'>
<a href="generate.php"><img id="home" src="images/home.png" alt="go home"/></a>
<a href="signout.php"><img id="signout" src="images/exit.png" alt="sign out"/></a>
</div>
</div>
<div id='sidebar'>
<ul id='sidebar_list'>
<li class="sidebar_menu" id="zero1"><span class="icon-user"></span>ADD NEW EMPLOYEE</li>
<li class="sidebar_menu" id="zero2"><span class="icon-address-book"></span>EDIT EMPLOYEE INFO</li>
<li class="sidebar_menu" id="one_bk"><span class="icon-pencil"></span>VIEW EMPLOYEE INFO</li>
<li class="sidebar_menu" id="two"><span class="icon-table2"></span>UPLOAD MASTER SHEET</li>
<li class="sidebar_menu" id="one"><span class="icon-spinner5"></span>GENERATE SALARY SLIPS</li>
<li class="sidebar_menu" id="six"><span class="icon-pencil"></span>GENERATE MASTER SHEET</li>
<li class="sidebar_menu" id="three"><span class="icon-folder-open"></span>VIEW SALARY SLIPS</li>
<li class="sidebar_menu" id="five"><span class="icon-stack"></span>MANAGE SALARY SLIPS</li>
<li class="sidebar_menu" id="four"><span class="icon-wrench"></span>EDIT STRUCTURE</li>
<li class="sidebar_menu" id="seven"><span class="icon-drawer"></span>VIEW LEAVES</li>
<li class="sidebar_menu" id="zero0"><span class="icon-key"></span>SIGN OUT</li>
</ul>
</div>
<div class="right_part">
<div id='manual_form' style="display:none" class="right_div">
	<div class="widget">
        <div class="widget-body" style="display: block;">
            <form action="generate_form.php" method="POST" onSubmit="return validate()">
    		<div id="table_names" style="display: block;">
            </div>
            <div class='submit_div'><input type='submit' id='manual_s' class='submit' name='submit' value='Submit' /></div>
    		</form>
        </div>
    </div>
</div>

<div id='employee_info' style="display:none;margin-left: 10%;" class="right_div">
	<div class="widget" style="overflow: auto;padding: 0;">
        <div id="employee_info_table" class="widget-body" style="display: block;"></div>
 	</div>
</div>

<div id='manual_select' style="display:none;" class="right_div">
	<div class="widget">
        <div class="widget-body" style="display: block;">
		<h2 id="sel_date">Please select a date</h2>
		<input type="text" name="month" id="date" required readonly/><br/>
		<input type="button" id="manual_old" style="margin-top:30px;" value="Select Employees" onClick="setupemployees()"/>
        </div>
 	</div>
</div>
<div id='leaves_select' style="display:none;" class="right_div">
	<div class="widget">
        <div class="widget-body" style="display: block;">
		<h2 id="sel_date">Please select a date</h2>
		<input type="text" name="month" id="leaves_date"/><br/><span style="font-size: 10px;">Leave blank to view all months</span><br>
		<input type="button" id="manual" style="margin-top:30px;" value="Select Employees" onClick="setupleaves()"/>
        <table id="leave_names"></table>
        <input type="button" id="leaves_submit" style="margin-top:30px;" class='submit' value="View Leaves"/>
        </div>
 	</div>
</div>
<div id='excel_form' style="display:none;" class="right_div">
	<div class="widget">
        <div class="widget-body" style="display: block;">
		<form action="generate_all.php" onsubmit="return validate_upload();" method="post" enctype="multipart/form-data">
		<h2 class="steps">Steps<br/></h2>
    	<h3 class="steps">1. Select a date</h3>
    	<input type="text" name="month" id="date2" required readonly/><br/>
		<h3 class="steps">2. Download Excel file</h3>
    	<input type='button' id="download_button" class="download_button_class" value='Download'/><br/>
    	<h3 class="steps">3. Upload the filled Excel file</h3>
    	<input type="button" id="excelfile_button" value="Choose file"/><br/>
		<input type="file" name="file" id="excelfile" style="display:none;"/>
    	<input type="submit" name="submit" id='excel_submit' class='submit' value="Upload" style="margin-top:20px"/>
		</form>
       </div>
    </div>
</div>
<div id='mss' style='display:none' class="right_div">
	<div class="widget">
        <div class="widget-body" style="display: block;">
		<h3>Select a date</h3>
		<input type="text" name="month" id="mss_date" required readonly/><br/>
		<input type='button' id="mss_download_button" class="download_button_class" value='Download'/><br/>
        </div>
     </div>
</div>
<div id='approve' style='display:none' class="right_div">
	<div class="widget">
        <div class="widget-body" style="display: block;">
		<h2>Pending Salary Slips:</h2>
        <table>
		<tr><th>Month</th><th>Status</th><th>Options</th></tr>
<?php
include "db_conf.php";
$file_query="Select * from excel_uploads";
$file_res=$mysqli->query($file_query);
if(!$file_res)
echo $mysqli->error;
else
{
	while($file_row=$file_res->fetch_assoc())
	{
		$file_location=$file_row['location'];
		$file_status=$file_row['status'];
		$file_id=$file_row['id'];
		echo "<tr id='$file_id'>";
		echo "<td>";
		$slip_query="Select month,year from excel_slips where id='$file_id' LIMIT 1";
		$slip_res=$mysqli->query($slip_query);
		if(!$slip_res)
		echo $mysqli->error;
		else
		{
			$slip_row=$slip_res->fetch_assoc();
			echo $slip_row['month']."/".$slip_row['year'];
		}
		echo "</td>";
		$view_button_str="<td><a href='$file_location'><input type='button' class='submit' value='View'/></a>";
		$delete_button_str="<input type='button' class='download_button_class delete_slips_button' value='Delete'/>";
		if($file_status==1)
		{
			echo "<td><span style='color:red'>Pending Approval</span></td>";
			echo $view_button_str;
			echo "&nbsp;";
			echo $delete_button_str;
			echo "</td>";
		}
		else if($file_status==0)
		{
			echo "<td><span>Uploaded</span></td>";
			echo $view_button_str;
			echo "<input type='button' style='margin:0 5px;'class='green_button send_approval_button' value='Submit'/>";
			echo $delete_button_str;
			echo "</td>";
		}
		else if($file_status==2)
		{
			echo "<td><span>Approved</span></td>";
			echo $view_button_str;
			echo "<input type='button' style='margin:0 5px;' class='green_button generate_slips_button' value='Generate Slips'/>";	
			echo "</td>";		
		}
		echo "</tr>";
	}
}
?>
		</table>
        </div>
     </div>
</div>
<div id='edit_emp' style='display:none' class="right_div">
	<div class="widget">
        <div class="widget-body" style="display: block;">
<?php
$query="Select `Employee Code`,`Name of the Employee`,`active` from employee_info where 1 ORDER BY `Name of the Employee` ASC";
$res=$mysqli->query($query);
if(!$res)
echo "Database connectivity error ".$mysqli->error;
else
{
	echo "<table>";
	while($row=$res->fetch_assoc())
	{
		$code=$row['Employee Code'];
		$name=$row['Name of the Employee'];
		echo "<tr><td><span class='icon-user'></span></td>";
		echo "<td style='text-align: left !important;'>";
		echo "<a style='text-decoration:none;color:black;padding:15px 70px 15px 20px;' href='edit_info.php?edit=$code'>";
		echo "$name</a></td><td><input type='button' id='edit_button_$code' class=";
		if($row['active']==1)
		echo "'download_button_class' onclick='deactivate_user(\"$code\");' value='Deactivate'";
		else if($row['active']==0)
		echo "'green_button' onclick='activate_user(\"$code\");' value='Activate'";
		echo "/></tr>";
	}
	echo "</table>";
}
mysqli_close($mysqli);
?>
		</div>
    </div>
</div>
</div>
<div id='loading'><img src="images/ajax.gif" alt="loading"/></div>
</body>
</html>
<?php } else {?>
<?php header("Location:index.php");} ?>