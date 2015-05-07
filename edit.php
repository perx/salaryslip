<?php
include_once 'session.php';

if(($_SESSION['user'])=='admin') { ?>
<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<title>Edit Salary Structure</title>
<?php include_once "favicon.html"; ?>
<link rel="stylesheet" href="bodystyle.css" type="text/css"/>
<link rel="stylesheet" href="editstyle.css" type="text/css"/>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
<script>
$(document).ready(function() {
    var tr=document.getElementById('earn_names');
	var earn_arr=[];
	for(var i=0;i<tr.children.length;i++)
	{
		earn_arr[i]=tr.children[i].id;
	}
	var earn=earn_arr.join(";");
	sessionStorage.earn=earn;
});

function delete_col(column,name)
{
	if(document.getElementById(column+'_names').children.length>1)
	{
		if(confirm("Are you sure you want to delete "+name+" ?"))
		{
			if(column=='earn')
			{
				var str=sessionStorage.earn;
			}
			else if(column=='leaves'||column=='ded')
			{
				var str=";";
			}
			var arr=str.split(";");
			arr = jQuery.grep(arr, function(value) {
  				return value != name;
			});
			str=arr.join(";");
			if(column=='earn')
			{
				sessionStorage.earn=str;
			}
			var td=document.getElementById(name);
			$(td).remove();
			td=document.getElementById(name+'_val');
			$(td).remove();
			if(column=='earn')
			{
				update_comboboxes('earn');
				update_comboboxes('ded');
			}
		}
	}
	else
	{
		alert("You cannot delete all fields");
	}
}
function add_col(column)
{
	var name=prompt("Please enter a name for the new column",column);
	if(name!=null)
	{
		if(column=='earn')
		{
			sessionStorage.earn+=";"+name;
		}
		var row=column+"_names";
		row=document.getElementById(row);
		var td="<td id='"+name+"'>";
		if(column!='earn')
		td=td+name;
		else
		{
			var cb="cb_"+name;
			td=td+"<input type='checkbox' name='sel_earn[]' value=\'"+name+"\' class='cb' id=\'"+cb+"\' style='display:none' checked/>";
			td=td+"<label for=\'"+cb+"\' class='cblabel'>"+name+"</label>";
		}
		td=td+"<div class='icons'><span class='icon-chevron-left' onclick=\"shift_left(\'"+name+"\')\"></span>";
		td=td+"<span class='icon-chevron-right' onclick=\"shift_right(\'"+name+"\')\"></span>";
		td=td+"<span class='icon-minus' onclick=\"delete_col(\'"+column+"\',\'"+name+"\')\"></span></div><br/></td>";
		$(row).append(td);
		row=column+"_values";
		row=document.getElementById(row);
		var td_val=row.children[0];
		var td_name=$(td_val).attr('id');
		td_name=td_name.substr(0, td_name.lastIndexOf("val")-1);
		var my_td_val=$(td_val).clone();
		$(my_td_val).attr('id',name+"_val");
		$(my_td_val).children().each(function(){
			if($(this).attr('name'))
			{
				$(this).attr('name',$(this).attr('name').replace(td_name,name));
				this.outerHTML=this.outerHTML.replace(td_name,name);
			}
		});
		$(row).append(my_td_val);
		if(column=='earn')
		{
			update_comboboxes('earn');
			update_comboboxes('ded');
		}
	}
}
function update_comboboxes(column)
{
	$('#'+column+'_values :radio[value="formula"]').each(function(){
					if ($(this).is(':checked'))
					{
						var cbname=$(this).attr('name').replace('choice_'+column+'[','');
						cbname=cbname.replace(']','');
						var combobox=document.getElementsByName('formula_'+column+'['+cbname+']')[0];
						combobox.innerHTML=get_options(cbname);
					}
				});
}
function shift_right(name)
{
	var td=document.getElementById(name);
	if(td.nextSibling)
	{
		var td_name=td.cloneNode(true);
		td.nextSibling.insertAdjacentElement('afterEnd',td_name)
		$(td).remove();
		td=document.getElementById(name+'_val');
		var td_val=td.cloneNode(true);
		td.nextSibling.insertAdjacentElement('afterEnd',td_val)
		$(td).remove();
	}
}
function shift_left(name)
{
	var td=document.getElementById(name);
	if(td.previousSibling)
	{
		var td_name=td.cloneNode(true);
		td.previousSibling.insertAdjacentElement('beforeBegin',td_name)
		$(td).remove();
		td=document.getElementById(name+'_val');
		var td_val=td.cloneNode(true);
		td.previousSibling.insertAdjacentElement('beforeBegin',td_val)
		$(td).remove();
	}
}
function get_options(name)
{
	var str=sessionStorage.earn;
	var arr=str.split(";");
	var optionlist="";
	for(var i=0;i<arr.length;i++)
	{
		if(arr[i]!=name)
		optionlist+="<option value='"+arr[i]+"'>"+arr[i]+"</option>";
	}
	return optionlist;
}
function show_formula(column,name)
{
	var textbox=document.getElementsByName('default_'+column+'['+name+']')[0];
	textbox.insertAdjacentHTML('afterEnd','<span> % of </span>');
	var combobox=document.getElementsByName('formula_'+column+'['+name+']')[0];
	combobox.innerHTML=get_options(name);
	$(textbox).addClass('small');
	$(combobox).show();
}
function hide_formula(column,name)
{
	var span=document.getElementsByName('default_'+column+'['+name+']')[0].nextSibling;
	if(span.nodeName=="SPAN")
	{	
		$(span).prev().removeClass('small');
		$(span).remove();
		var combobox=document.getElementsByName('formula_'+column+'['+name+']')[0];
		$(combobox).hide();
	}
}
function validate()
{
	var flag=true;
	$(':input[type=text]').each(function(){
		if(isNaN($(this).val()))
		{
			$(this).addClass('error');
			flag=false;
		}
		else
		{
			$(this).removeClass('error');
		}
	});
	return flag;
}
</script>
</head>
<body>
<div id="panel_header"><a href="generate.php"><h2>Salary Slip Generator</h2></a>
<a href="signout.php"><img id="signout" src="images/exit.png" alt="sign out"/></a>
<a href="generate.php"><img id="home" src="images/home.png" alt="go home"/></a>
</div>
<?php 

	include 'db_conf.php';
	$query="SELECT * FROM `salary_structure` ORDER BY `sal_str_code` DESC LIMIT 1";
	$res = $mysqli->query($query) or die($mysqli->error);
	if($res->num_rows<=0)
	echo "Database error";
	else
	{
		$row=$res->fetch_Assoc();
		$ded=explode(";",$row['ded']);
		$earn=explode(";",$row['earn']);
		$leaves=explode(";",$row['leaves']);
		$default_ded=explode(";",$row['default_ded']);
		$default_earn=explode(";",$row['default_earn']);
		$earn=array_combine($earn,$default_earn);
		$ded=array_combine($ded,$default_ded);
		echo "<form action='edit_methods.php' onSubmit='return validate()' method='POST'>";
		
		echo "<legend>Allowances<span class='icon-plus'onClick=".'"'."add_col('earn')".'"'."></span></legend>";
        echo "<div class='table_content'><table id='earn_table' style='min-width: 800px;'><tbody>";
        $str="<tr id='earn_names'>";
		foreach($earn as $k=>$v)
		{
			$str.="<td id='$k'>";
			$cb="cb_".$k;
			$str.="<input type='checkbox' name='sel_earn[]' value='$k' class='cb' id='$cb' style='display:none' checked/>";
			$str.="<label for='$cb' class='cblabel'>$k</label>";
			$str.="<div class='icons'><span class='icon-chevron-left' onClick=".'"'."shift_left('$k')".'"'."></span>";
			$str.="<span class='icon-chevron-right' onClick=".'"'."shift_right('$k')".'"'."></span>";
			$str.="<span class='icon-minus' onClick=".'"'."delete_col('earn','$k')".'"'."></span></div><br/>";
			$str.="</td>";
		}
		$str.="</tr><tr id='earn_values'>";
		foreach($earn as $k=>$v)
		{
			$id=$k."_val";
			$str.="<td id='$id'>";
			$rk="default_earn[$k]";
			$ck="choice_earn[$k]";
			$fk="formula_earn[$k]";
			$str.="<input type='radio' name='$ck' value='val' checked onClick=".'"'."hide_formula('earn','$k')".'"'."/>Value";
			$str.="<input type='radio' name='$ck' value='formula' onClick=".'"'."show_formula('earn','$k')".'"'."/>Formula<br/>";
			$str.="<input type='text' name='$rk' class='editable_val' value='$default_earn[$i]'/>";
			$str.="<select name='$fk' style='display:none'></select>";
			$str.="</td>";
		}
		$str.="</tr>";
		echo $str;
		echo "</tbody></table></div>";
		
		echo "<legend>Deductions<span class='icon-plus'onClick=".'"'."add_col('ded')".'"'."></span></legend>";
        echo "<div class='table_content'><table id='ded_table' style='min-width: 800px;'><tbody>";
        $str="<tr id='ded_names'>";
		foreach($ded as $k=>$v)
		{
			$str.="<td id='$k'>";
			$str.=$k;
			$str.="<div class='icons'><span class='icon-chevron-left' onClick=".'"'."shift_left('$k')".'"'."></span>";
			$str.="<span class='icon-chevron-right' onClick=".'"'."shift_right('$k')".'"'."></span>";
			$str.="<span class='icon-minus' onClick=".'"'."delete_col('ded','$k')".'"'."></span></div><br/>";
			$str.="</td>";
		}
		$str.="</tr><tr id='ded_values'>";
		foreach($ded as $k=>$v)
		{
			$id=$k."_val";
			$str.="<td id='$id'>";
			$rk="default_ded[$k]";
			$ck="choice_ded[$k]";
			$fk="formula_ded[$k]";
			$str.="<input type='radio' name='$ck' value='val' checked onClick=".'"'."hide_formula('ded','$k')".'"'."/>Value";
			$str.="<input type='radio' name='$ck' value='formula' onClick=".'"'."show_formula('ded','$k')".'"'."/>Formula<br/>";
			$str.="<input type='text' name='$rk' class='editable_val' value='$default_earn[$i]'/>";
			$str.="<select name='$fk' style='display:none'></select>";
			$str.="</td>";
		}
		$str.="</tr>";
		echo $str;
		echo "</tbody></table></div>";
		
		echo "<legend>Leaves<span class='icon-plus'onClick=".'"'."add_col('leaves')".'"'."></span></legend>";
        echo "<div class='table_content'><table id='leaves_table' style='min-width: 800px;'><tbody>";
        $str="<tr id='leaves_names'>";
		foreach($leaves as $v=>$k)
		{
			$str.="<td id='$k'>";
			$str.=$k;
			$str.="<div class='icons'><span class='icon-chevron-left' onClick=".'"'."shift_left('$k')".'"'."></span>";
			$str.="<span class='icon-chevron-right' onClick=".'"'."shift_right('$k')".'"'."></span>";
			$str.="<span class='icon-minus' onClick=".'"'."delete_col('leaves','$k')".'"'."></span></div><br/>";
			$str.="</td>";
		}
		$str.="</tr><tr id='leaves_values'>";
		foreach($leaves as $k)
		{
			$id=$k."_val";
			$str.="<td id='$id' style='display:none'>";
			$rk="default_leaves[$k]";
			$str.="<input type='hidden' name='$rk' value='0'/>";
			$str.="</td>";
		}
		$str.="</tr>";
		echo $str;
		echo "</tbody></table></div>";

		echo "<div class='submit_div'><input type='submit' class='submit' name='submit' value='Submit'></div>";
		echo "</form>";
	}
?>

</body>
</html>

<?php } else header("Location:index.php"); ?>