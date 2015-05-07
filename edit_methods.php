<?php 
function days_in_month($date) 
{ 
	$year=date('Y',$date);
	$month=date('m',$date);
	// calculate number of days in a month 
	return $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31); 
}
function set_for_insertion($a)
{
	return explode(";",strrev($a));
} 
function update_db($db_entry)
{
	include 'db_conf.php';
	if (!($stmt = $mysqli->prepare("Insert into `salary_structure` (`earn`,`ded`,`leaves`,`default_earn`,`default_ded`) values(?,?,?,?,?)"))) 
	{
   		echo( "Salary Structure statement not prepared: (" . $mysqli->errno . ") " . $mysqli->error."<br/>");
		return false;
	}
		$stmt->bind_param("sssss",$db_entry['earn'],$db_entry['ded'],$db_entry['leaves'],$db_entry['default_earn'],$db_entry['default_ded']);
		if (!$stmt->execute()) 
		{
    		echo("Could not insert new salary structure into database: (" . $stmt->errno . ") " . $stmt->error."<br/>");
			return false;
		}
	$sal_str_code=$mysqli->insert_id;
	$query="Update `employee_info` set `sal_str_code`='$sal_str_code' where 1";
	$res = $mysqli->query($query) or die($mysqli->error);
	return $sal_str_code;
}
function generate_excel($sal_str_code,&$earn,&$ded,&$leaves,&$default_earn,&$default_ded)
{
	include 'db_conf.php';
	$formula_flag=0;
	set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . '/Classes/');
	include 'PHPExcel/IOFactory.php';
	
	$objReader = PHPExcel_IOFactory::createReaderForFile('images/MSS.xlsx');
	$objPHPExcel = $objReader->load('images/MSS.xlsx');
	$worksheet=$objPHPExcel->getActiveSheet(); 
	
	
	//insert month
	$month_line="SALARY STATEMENT FOR ".strtoupper(date('F Y',time()));
	$days_in_month=days_in_month(time());
	$worksheet->setCellValue('A4',$month_line);
	
	//insert leave columns
	$leaves=set_for_insertion($leaves);
	$leaves_no=0;
	foreach($leaves as $v)
	{
		$worksheet->insertNewColumnBefore('G',1);
		for($i=6;$i<9;$i++)
		{
			$worksheet->duplicateStyle($worksheet->getStyle('F'.$i), 'G'.$i);	 	
		}
		$worksheet->setCellValue('G6',"LEAVES");
		$worksheet->setCellValue('G7',strrev($v));
		$worksheet->setCellValue('G8',0);
		$worksheet->getStyle('G6')->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_NONE);
		$worksheet->getColumnDimension('G')->setAutoSize(true);
		$worksheet->getStyle('G6')->getFont()->getColor()->setRGB('ffffff');
		$leaves_no++;
	}
	$worksheet->getStyle('G6')->getFont()->getColor()->setRGB('000000');
	$worksheet->removeColumn('F',1);
	
	$start_col_earn=PHPExcel_Cell::stringFromColumnIndex(PHPExcel_Cell::columnIndexFromString('F')+$leaves_no);
	$new_col_earn=PHPExcel_Cell::stringFromColumnIndex(PHPExcel_Cell::columnIndexFromString('F')+$leaves_no-1);
	
	//insert allowances
	$default_earn=array_combine(explode(";",$earn),explode(";",$default_earn));
	$earn=set_for_insertion($earn);
	$earn_no=0;
	foreach($earn as $v)
	{
		$val=strrev($v);
		$worksheet->insertNewColumnBefore($start_col_earn,1);
		for($i=6;$i<9;$i++)
		{
			$worksheet->duplicateStyle($worksheet->getStyle($new_col_earn.$i), $start_col_earn.$i);	 	
		}
		$worksheet->setCellValue($start_col_earn.'6',"EARNINGS");
		$worksheet->setCellValue($start_col_earn.'7',$val);
		if($default_earn[$val][strlen($default_earn[$val])-1]=='!')
		{
			$not_in_total[]=$val;
			$default_val=substr($default_earn[$val],0, -1);
		}
		else
		{
			$default_val=$default_earn[$val];
		}
		if($default_val[0]!='@')
		{
			$worksheet->setCellValue($start_col_earn.'8',$default_val);	
		}
		else
		{
			//keep formula in array, insert after all earn,ded have been inserted
			parse_str($default_val);
			$formula_flag=1;
			$formulas[$val]['p']=$p/100;
			$formulas[$val]['of']=$of;
		}
		$worksheet->getColumnDimension($start_col_earn)->setAutoSize(true);
		$worksheet->getStyle($start_col_earn.'6')->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_NONE);
		$worksheet->getStyle($start_col_earn.'6')->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_NONE);
		$worksheet->getStyle($start_col_earn.'7')->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
		$worksheet->getStyle($start_col_earn.'7')->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
		$worksheet->getStyle($start_col_earn.'6')->getFont()->getColor()->setRGB('D99595');
		$earn_no++;
	}
	$worksheet->getStyle($start_col_earn.'6')->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
	$worksheet->getStyle($start_col_earn.'7')->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
	$worksheet->getStyle($start_col_earn.'6')->getFont()->getColor()->setRGB('000000');
	$worksheet->removeColumn($new_col_earn,1);
	
	//insert earnings_payable
	$start_col_earn_pay=PHPExcel_Cell::stringFromColumnIndex(PHPExcel_Cell::columnIndexFromString($start_col_earn)+$earn_no);
	$new_col_earn_pay=PHPExcel_Cell::stringFromColumnIndex(PHPExcel_Cell::columnIndexFromString($start_col_earn)+$earn_no-1);
	$earn_pay_no=0;
	foreach($earn as $v)
	{
		$val=strrev($v);
		$worksheet->insertNewColumnBefore($start_col_earn_pay,1);
		for($i=6;$i<9;$i++)
		{
			$worksheet->duplicateStyle($worksheet->getStyle($new_col_earn_pay.$i), $start_col_earn_pay.$i);	 	
		}
		$worksheet->setCellValue($start_col_earn_pay.'6',"EARNINGS PAYABLE");
		$worksheet->setCellValue($start_col_earn_pay.'7',$val);
		$worksheet->getColumnDimension($start_col_earn_pay)->setAutoSize(true);

		if($default_earn[$val][strlen($default_earn[$val])-1]!='!')
		{
			$earn_pay_formula[$val]=$val;
		}
		else
		{
			$default_val=0;
			$worksheet->setCellValue($start_col_earn_pay.'8',$default_val);	
		}
		$worksheet->getStyle($start_col_earn_pay.'6')->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_NONE);
		$worksheet->getStyle($start_col_earn_pay.'6')->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_NONE);
		$worksheet->getStyle($start_col_earn_pay.'7')->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
		$worksheet->getStyle($start_col_earn_pay.'7')->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
		$worksheet->getStyle($start_col_earn_pay.'6')->getFont()->getColor()->setRGB('D99595');
		$earn_pay_no++;
	}
	$worksheet->getStyle($start_col_earn_pay.'6')->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
	$worksheet->getStyle($start_col_earn_pay.'7')->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
	$worksheet->getStyle($start_col_earn_pay.'6')->getFont()->getColor()->setRGB('000000');
	$worksheet->removeColumn($new_col_earn_pay,1);
	
	$start_col_ded=PHPExcel_Cell::stringFromColumnIndex(PHPExcel_Cell::columnIndexFromString($start_col_earn_pay)+$earn_pay_no);
	$new_col_ded=PHPExcel_Cell::stringFromColumnIndex(PHPExcel_Cell::columnIndexFromString($start_col_earn_pay)+$earn_pay_no-1);
	
	//insert deductions
	$default_ded=array_combine(explode(";",$ded),explode(";",$default_ded));
	$ded=set_for_insertion($ded);
	$ded_no=0;
	foreach($ded as $v)
	{
		$val=strrev($v);
		$worksheet->insertNewColumnBefore($start_col_ded,1);
		for($i=6;$i<9;$i++)
		{
			$worksheet->duplicateStyle($worksheet->getStyle($new_col_ded.$i), $start_col_ded.$i);	 	
		}
		$worksheet->setCellValue($start_col_ded.'6',"DEDUCTIONS");
		$worksheet->setCellValue($start_col_ded.'7',$val);
		if($default_ded[$val][strlen($default_ded[$val])-1]=='!')
		{
			$default_val=substr($default_ded[$val],0, -1);
		}
		else
		{
			$default_val=$default_ded[$val];
		}
		if($default_val[0]!='@')
		{
			$worksheet->setCellValue($start_col_ded.'8',$default_val);	
		}
		else
		{
			//keep formula in array, insert after all earn, ded have been inserted into sheet
			parse_str($default_val);
			$formula_flag=1;
			$formulas[$val]['p']=$p/100;
			$formulas[$val]['of']=$of;
		}
		$worksheet->getColumnDimension($start_col_ded)->setAutoSize(true);
		$worksheet->getStyle($start_col_ded.'6')->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_NONE);
		$worksheet->getStyle($start_col_ded.'6')->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_NONE);
		$worksheet->getStyle($start_col_ded.'7')->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
		$worksheet->getStyle($start_col_ded.'7')->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
		$worksheet->getStyle($start_col_ded.'6')->getFont()->getColor()->setRGB('C4D7A2');
		$ded_no++;
	}
	$worksheet->getStyle($start_col_ded.'6')->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
	$worksheet->getStyle($start_col_ded.'7')->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
	$worksheet->getStyle($start_col_ded.'6')->getFont()->getColor()->setRGB('000000');
	$worksheet->removeColumn($new_col_ded,1);
	
	
	//insert formulas and values
	$worksheet->setCellValue('E8',1);
	$worksheet->setCellValue('D8',$days_in_month);
	//get cells for earn columns
	$col=PHPExcel_Cell::columnIndexFromString('F');
	$cell=PHPExcel_Cell::stringFromColumnIndex($col).'6';

	while(($head=$worksheet->getCell($cell)->getValue())!='Net Pay')
	{
		if($head=='EARNINGS')
		{
			$earn_excel_arr[$worksheet->getCell(PHPExcel_Cell::stringFromColumnIndex($col).'7')->getValue()]=$col;
		}
		else if($head=='DEDUCTIONS')
		{
			$ded_excel_arr[$worksheet->getCell(PHPExcel_Cell::stringFromColumnIndex($col).'7')->getValue()]=$col;
		}
		else if($head=='EARNINGS PAYABLE')
		{
			$earn_pay_excel_arr[$worksheet->getCell(PHPExcel_Cell::stringFromColumnIndex($col).'7')->getValue()]=$col;
		}
		$col++;
		$cell=PHPExcel_Cell::stringFromColumnIndex($col).'6';
	}

	//insert employee names, etc.
	$query="Select `Name of the Employee`,`Employee Code`,`sal_str_code`,`earn`,`ded` from `employee_info` ORDER BY `Name of the Employee` DESC";
	$res=$mysqli->query($query);
	$sno=$res->num_rows;
	$columns=PHPExcel_Cell::columnIndexFromString($worksheet->getHighestColumn());
	while($row=$res->fetch_assoc())
	{
		$worksheet->insertNewRowBefore(8,1);
		$worksheet->setCellValue('A8',$sno--);
		$worksheet->setCellValue('B8',$row['Name of the Employee']);
		$worksheet->setCellValue('C8',$row['Employee Code']);
		for($i=0;$i<$columns;$i++)
		{
			$col=PHPExcel_Cell::stringFromColumnIndex($i);
			$worksheet->duplicateStyle($worksheet->getStyle($col.'9'), $col.'8');
			if($i>3)
			{
				$worksheet->setCellValue($col.'8',$worksheet->getCell($col.'9')->getValue());
			}
		}
		
		//insert default values
		if($sal_str_code==$row['sal_str_code'])
		{
			$emp_earn=array_combine(array_flip($earn_excel_arr),explode(";",$row['earn']));
			$emp_ded=array_combine(array_flip($ded_excel_arr),explode(";",$row['ded']));	
			foreach($earn_excel_arr as $k=>$v)
			{
				$worksheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($v)."8",$emp_earn[$k]);
			}
			foreach($ded_excel_arr as $k=>$v)
			{
				$worksheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($v)."8",$emp_ded[$k]);
			}		
		}
		$worksheet->getRowDimension(8)->setRowHeight(-1);
	}
	$worksheet->removeRow($res->num_rows+8,1);
	$last_emp_row=$res->num_rows+8;
	
	//get start and end col of earn and ded
	$start_earn=reset($earn_excel_arr);
	$end_earn=end($earn_excel_arr);
	$start_ded=reset($ded_excel_arr);
	$end_ded=end($ded_excel_arr);
	$start_earn_pay=reset($earn_pay_excel_arr);
	$end_earn_pay=end($earn_pay_excel_arr);
	
	//get col for total
	$total_earn_col=$end_earn+1;
	$total_ded_col=$end_ded+1;
	$total_earn_pay_col=$end_earn_pay+1;
	$net_pay_col=$total_ded_col+1;
	$salary_in_hand_col=$net_pay_col+1;
	
	//get lwp column
	for($lwp_col=5;$worksheet->getCellByColumnAndRow($lwp_col,7)->getValue()!="LWP"&&$worksheet->getCellByColumnAndRow($lwp_col,6)->getValue()!="EARNINGS";$lwp_col++)
	{echo "";}
	if($worksheet->getCellByColumnAndRow($lwp_col,6)->getValue()=="EARNINGS")
	$lwp_flag=0;
	else
	$lwp_flag=1;
	$lwp_col=PHPExcel_Cell::stringFromColumnIndex($lwp_col);
	
	
	//insert formulas in each employee row
	for($i=8;$i<$last_emp_row;$i++)
	{
		if($formula_flag==1)
		{
			foreach($formulas as $k=>$v)
			{
				if(array_key_exists($k,$earn_excel_arr))
				{
					$cell=PHPExcel_Cell::stringFromColumnIndex($earn_excel_arr[$k]).$i;
				}
				else if(array_key_exists($k,$ded_excel_arr))
				{
					$cell=PHPExcel_Cell::stringFromColumnIndex($ded_excel_arr[$k]).$i;
				}
				$formula="=(".$v['p'].")*".PHPExcel_Cell::stringFromColumnIndex($earn_excel_arr[$v['of']]).$i;
				$worksheet->setCellValue($cell,$formula);
			}	
		}
		foreach($earn_pay_formula as $k=>$v)
		{
			$formula="=ROUND((".PHPExcel_Cell::stringFromColumnIndex($earn_excel_arr[$k]).$i."/D".$i.")*E".$i.",0)";
			$worksheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($earn_pay_excel_arr[$k]).$i,$formula);	
		}
		
		$formula_ded_total="=SUM(".PHPExcel_Cell::stringFromColumnIndex($start_ded).$i.":".PHPExcel_Cell::stringFromColumnIndex($end_ded).$i.")";
		$worksheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($total_ded_col).$i,$formula_ded_total);
		
		$formula_earn_pay_total="=SUM(".PHPExcel_Cell::stringFromColumnIndex($start_earn_pay).$i.":".PHPExcel_Cell::stringFromColumnIndex($end_earn_pay).$i.")";
		$worksheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($total_earn_pay_col).$i,$formula_earn_pay_total);
		
		$formula_earn_total="=0";
		foreach($earn_excel_arr as $k_total=>$v_total)
		{
			if(!in_array($k_total,$not_in_total))
			{
				$formula_earn_total.="+".PHPExcel_Cell::stringFromColumnIndex($v_total).$i;
			}
		}
		$formula_net_pay="=ROUND(".PHPExcel_Cell::stringFromColumnIndex($total_earn_pay_col).$i."-".PHPExcel_Cell::stringFromColumnIndex($total_ded_col).$i.",0)";
		$worksheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($total_earn_col).$i,$formula_earn_total);
		$worksheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($net_pay_col).$i,$formula_net_pay);
		$worksheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($salary_in_hand_col).$i,"=ROUND(".PHPExcel_Cell::stringFromColumnIndex($net_pay_col)."$i,0)");	
		
		//insert workingdays=totaldays-lwp
		if($lwp_flag==1)
		{
			$formula_working_days="=D$i"."-".$lwp_col.$i;
			$worksheet->setCellValue("E".$i,$formula_working_days);
		}
	}
	
	//insert total formulas in last row
	$total_row=$last_emp_row;
	$last_emp_row--;
	for($v_each=$start_earn;$v_each<=$salary_in_hand_col;$v_each++)
	{
		$col_each=PHPExcel_Cell::stringFromColumnIndex($v_each);
		$formula_each="=SUM($col_each"."8:$col_each$last_emp_row)";
		$worksheet->setCellValue($col_each.$total_row,$formula_each);
	}
	
	//insert employee default values
	
	
	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
	$objWriter->save('images/MSS1.xlsx');
}

if($_POST['submit']=='Submit')
{
	foreach($_POST['choice_earn'] as $k=>$v)
	{
		$earn[]=$k;
		if($v=='val')
		{
			if(!in_array($k,$_POST['sel_earn']))
			{
				$default_earn[]=$_POST['default_earn'][$k]."!";
			}
			else
			$default_earn[]=$_POST['default_earn'][$k];
		}
		else if($v=='formula')
		{
			if(!in_array($k,$_POST['sel_earn']))
			{
				$default_earn[]="@&p=".$_POST['default_earn'][$k]."&of=".$_POST['formula_earn'][$k]."!";
			}
			else
			$default_earn[]="@&p=".$_POST['default_earn'][$k]."&of=".$_POST['formula_earn'][$k];
		}
	}
	foreach($_POST['choice_ded'] as $k=>$v)
	{
		$ded[]=$k;
		if($v=='val')
		{
			$default_ded[]=$_POST['default_ded'][$k];
		}
		else if($v=='formula')
		{
			$default_ded[]="@&p=".$_POST['default_ded'][$k]."&of=".$_POST['formula_ded'][$k];
		}
	}
	foreach($_POST['default_leaves'] as $k=>$v)
	{
		$leaves[]=$k;
	}
	$db_entry['earn']=implode(";",$earn);
	$db_entry['ded']=implode(";",$ded);
	$db_entry['leaves']=implode(";",$leaves);
	$db_entry['default_earn']=implode(";",$default_earn);
	$db_entry['default_ded']=implode(";",$default_ded);
	//echo "<pre>";
	//print_r($db_entry);
	//print_r($_POST);
	//echo "</pre>";
	//$sal_str_code=update_db($db_entry);
	$sal_str_code=1;
	
	if(generate_excel($sal_str_code,$db_entry['earn'],$db_entry['ded'],$db_entry['leaves'],$db_entry['default_earn'],$db_entry['default_ded']))
	{
		echo "Successfully updated";
	}
}
else
{header("Location:index.php");}
?>
