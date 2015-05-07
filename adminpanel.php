<?php include_once 'session.php';
if($_SESSION['user']=='payslipadmin'){
?>
<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<title>Admin Panel</title>
<?php include_once "favicon.html";
include "db_conf.php"; ?>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<link rel="stylesheet" href="generate_style.css" type="text/css"/>
<link rel="stylesheet" href="checkbox_style.css" type="text/css"/>
<link rel="stylesheet" href="bodystyle.css" type="text/css"/>
<link rel="stylesheet" href="lightbox.css" type="text/css"/>
<link rel="stylesheet" href="widget.css" type="text/css"/>
<script type="text/javascript" src="datepicker/zebra_datepicker.js"></script>
<link rel="stylesheet" href="datepicker/css/bootstrap.css" type="text/css">
</head>
<script>
//charting
// Load the Visualization API and the piechart package.
google.load('visualization', '1.0', {'packages':['corechart']});

// Set a callback to run when the Google Visualization API is loaded.
//google.setOnLoadCallback(innitChart);
function innitChart(date,empcode)
{
	$.post("getrecords.php",
			{
				date:date,
				empcode:empcode
			},
			function(data){
				if(data=="false")
				alert("No records found for specified dates");
				else if(data=="database")
				alert("Database error");
				else
				{
					json=JSON.parse(data);
					json.headings.splice(0,0,'Month');
					var val_arr=[];
					for(var i in json.values)
					{
						json.values[i].splice(0,0,i);
						val_arr.push(json.values[i]);
					}
					console.log(json);
					drawChart(json.headings,val_arr);
				}
			});
}
function drawChart(headings,values) {

  // Create the data table.
  var data=google.visualization.arrayToDataTable([headings,values[0]]);
  for(var i=1;i<values.length;i++)
  data.addRow(values[i]);
  // Set chart options
  var options = {
	  			 'title':'Salary Slip Distribution',
				 'width':900,
				 'height':600,
				 'backgroundColor':'transparent',
				 'orientation':'vertical',
				 vAxis:{maxValue:500000},
				 animation: {startup:true,duration: 1000,easing:'linear'},
				 legend: { position: 'top', maxLines: 3 },
        		 bar: { groupWidth: '75%' },
        		 isStacked: true,
			    };

  // Instantiate and draw our chart, passing in some options.
  var chart = new google.visualization.ColumnChart(document.getElementById('chart_div'));
  chart.draw(data, options);
  view=new google.visualization.DataView(data);
  google.visualization.events.addListener(chart, 'select', function(){
	  		var selection=chart.getSelection();
			if(selection[0].row==null)
			{
				if(window.view.getViewColumns().length!=2)
				view.setColumns([0,selection[0].column]);
				else
				view=new google.visualization.DataView(data);
				chart.draw(view, options);
			}
	  });
}
</script>
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
	$('#manual_select').hide();
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
	$("#one").click(function(){
		$(".right_div").hide();
        $("#approve").show();
		toggle(this);
    });
	$("#zero0").click(function(){
		window.location.href = "signout.php";
	});
	$("#two").click(function(){
		$(".right_div").hide();
        $("#employee_info").show();
		toggle(this);
    });
	$("#five").click(function(){
		window.location.href = "current.php";
	});
	$("#six").click(function(){
		$(".right_div").hide();
        $("#mss").show();
		toggle(this);
    });
	$('#notify_display').click(function() {
        $(".right_div").hide();
        $("#welcome").show();
		toggle(this);
    });
	$('#mss_date').Zebra_DatePicker({format:'d-m-Y'});
	$('#chartdate').Zebra_DatePicker({format:'Y',default_position:'below',direction:false,show_icon:false});
	$('#mss_download_button').click(function() {
		if(document.getElementById('mss_date').value!="")
			{
				location.href="get_mss.php?&date="+document.getElementById('mss_date').value;
			}
			else
			alert("Please select a valid date");
    });
	$('#approve_button').click(function() {
        alert("Salary Slips approved.");
    });
	$('.correction_button').click(function() {
		document.getElementById('correction_text').innerHTML="Regarding MSS for "+$(this).parent().parent().attr('date')+",";
        $("#lightbox").show();
    });
	$('.approve_button').click(function(e) {
        if(confirm("Do you really want to approve this file?"))
		{
			var filecode=$(this).parent().parent().attr('id');
			$.post("setactive.php",
				{
					filecode:filecode,
					func:'approve'
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
	$('#email_button').click(function() {
		if($('#correction_email').val()=="")
		{
			alert("Please enter an email address");
		}
		else
		{
			var to_email=$('#correction_email').val();
			var msg=$('#correction_text').val();
			$.post("email.php",
   				{
					total_emails:1,
       				from:"hr.cityinnovates@gmail.com",
					fromname:"CEO",
					to_email:to_email,
					subject:"Salary slip corrections",
					msg:msg,
    			},
    			function(data){
					if(data=='true')
					{
						alert('Email Sent');
					}
        			else
					alert(data);
    			});
	    	$("#lightbox").hide();
		}
    });
	$('#email_close_button').click(function() {
	    $("#lightbox").hide();
    });
	$('#viewchart').click(function() {
        innitChart($('#chartdate').val(),$('#chartemp').val());
    });
	$('.delete_notification').click(function(e) {
		//var data=this.parentNode.parentNode.children[0].innerHTML;
		var id=this.parentNode.id;
        $.post("setactive.php",{notification:'notify',func:'delete',id:id},function(data){
			if(data!="success") alert(data); 
			else $("#"+id).remove();
			});
    });
});
function toggle(a) {
    $('.selected').removeClass('selected');
    $(a).addClass('selected');
};
</script>

<body>
<div id="panel_header">
<div id='left_header'>
<span>PS Admin</span>
</div>
<div id='center_header'>
<a href="adminpanel.php"><span>Salary Slip Generator</span></a>
</div>
<div id='right_header'>
<img id="notify_display" src="images/notifications.png" alt="notifications"/>
<a href="adminpanel.php"><img id="home" src="images/home.png" alt="go home"/></a>
<a href="signout.php"><img id="signout" src="images/exit.png" alt="sign out"/></a>
</div>
</div>
<div id='sidebar'>
<ul id='sidebar_list'>
<li class="sidebar_menu" id="one"><span class="icon-table2"></span>APPROVE SALARY SLIPS</li>
<li class="sidebar_menu" id="two"><span class="icon-user"></span>EMPLOYEE SALARIES</li>
<li class="sidebar_menu" id="five"><span class="icon-stack"></span>VIEW SALARY SLIPS</li>
<li class="sidebar_menu" id="six"><span class="icon-pencil"></span>VIEW MASTER SHEET</li>
<li class="sidebar_menu" id="zero0"><span class="icon-key"></span>SIGN OUT</li>
</ul>
</div>
<div class="right_part">
<div id="welcome" class="right_div" >
	<div class="widget_dis" id='alertbox'>
        <div class="widget-title">
            <h4>Alerts</h4>
        </div>
        <div class="widget-body" style="display: block;">
            <div class="alert">
<?php
$query="Select * from notifications";
$res=$mysqli->query($query);
if(!$res)
echo "Notifications could not be retrieved";
else
{
	while($row=$res->fetch_array())
	{
		echo "<div id='$row[0]' class='notification_object'><span>$row[1]</span>";
		echo "<span style='color:red;float: right;cursor:pointer' class='icon-minus delete_notification'></span>";
		echo "</div>";
	}
}
?>
            </div>
        </div>
    </div>
</div>
<div id="employee_info" style='display:none' class="right_div">
	<div class="widget_dis" id='chart'>
        <div class="widget-title">
            <h4>Statistics</h4>
        </div>
        <div class="widget-body" style="display: block;">
        	<div>
            <span>Year:</span>
            <input type='text' id='chartdate' readonly/>
            <span>Employee:</span>
            <select id='chartemp'>
            <option value='all' name='all'>All</option>
            <?php
			$query="Select `Employee Code`,`Name of the Employee` from employee_info where 1 ORDER BY `Name of the Employee` ASC";
			$res=$mysqli->query($query);
			if(!$res)
			echo "Database connectivity error ".$mysqli->error;
			else
			{
				while($row=$res->fetch_assoc())
				{
					$code=$row['Employee Code'];
					$name=$row['Name of the Employee'];
					echo "<option value='$code' name='$name'>$name</option>";
				}
			}
			mysqli_close($mysqli);
			?>
            </select>
            <input type='button' id='viewchart' value='View'/> 
            </div>
            <div id="chart_div"></div>
        </div>
    </div>
</div>
<div id='approve' style='display:none' class="right_div">
	<div class="widget">
        <div class="widget-body" style="display: block;">
		<h2>Pending Salary Slips:</h2>
		<table>
		<tr><th>File</th><th>Status</th><th>Comments</th></tr>
<?php
include "db_conf.php";
$file_query="Select * from excel_uploads where status='1'";
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
		$slip_query="Select month,year from excel_slips where id='$file_id' LIMIT 1";
		$slip_res=$mysqli->query($slip_query);
		if(!$slip_res)
		echo $mysqli->error;
		else
		{
			$slip_row=$slip_res->fetch_assoc();
			echo "<tr id='$file_id' date='".$slip_row['month']."/".$slip_row['year']."'>";
			echo "<td>";
			echo "<a href='$file_location' style='color:black'>".$slip_row['month']."/".$slip_row['year']."</a>";
		}
		echo "</td>";
		echo "<td><input type='button' class='green_button approve_button' value='Approve'/></td>";
		echo "<td><input type='button' class='download_button_class correction_button' value='Send for corrections'/></td>";	
	}
}
?>
		</tr>
		</table>
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
</div>
<div id="lightbox" >
<div id='correction_box' style='width:500px;height:300px;background:rgba(248, 83, 30, 0.8) !important;margin-top:12%; margin-left:35%'>
<textarea id='correction_text' style='width:90%;height:70%;margin-top:25px;resize:none;'></textarea>
<input type="email" name="correction_email" id="correction_email" value="hr@cityinnovates.com" style="width:55%"/><br/>
<input type="button" id="email_button" value="Send Email"/>
<input type="button" id="email_close_button" value="Close"/>
</div>
</div>
<div id='loading'><img src="images/ajax.gif" alt="loading"/></div>
</body>
</html>
<?php } else {?>
<?php header("Location:index.php");} ?>