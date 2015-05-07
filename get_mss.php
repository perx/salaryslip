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
function get_mss($date)
{
	include 'db_conf.php';
	set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . '/Classes/');
	include 'PHPExcel/IOFactory.php';
	
	$objReader = PHPExcel_IOFactory::createReaderForFile('images/MSS.xlsx');
	$objPHPExcel = $objReader->load('images/MSS.xlsx');
	$worksheet=$objPHPExcel->getActiveSheet(); 
	
	
	//insert month
	$month_line="SALARY STATEMENT FOR ".strtoupper(date('F Y',$date));
	$days_in_month=days_in_month($date);
	$worksheet->setCellValue('A4',$month_line);
	
	$month=date("m",$date);
	$year=date("Y",$date);
	//get salary structure
	$query="Select * from salary_slips where month='$month' AND year='$year'";
	$res=$mysqli->query($query) or die($mysqli->error);

	if($res->num_rows<1)
	die("No salary slips found for the specified date");
	else
	{
		while($row=$res->fetch_assoc())
		{
			$slips[]=$row;
		}
	}
		
	$sal_str_code=$slips[0]['sal_str_code'];
	$query="Select * from salary_structure where sal_str_code='$sal_str_code'";
	$res=$mysqli->query($query) or die($mysqli->error);
	
	if($res->num_rows<1)
	die("No salary structure found");
	else
	{
		$salary_structure=$res->fetch_assoc();
	}
	$earn=$salary_structure['earn'];
	$ded=$salary_structure['ded'];
	$leaves=$salary_structure['leaves'];
	
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
	$default_earn=array_combine(explode(";",$salary_structure['earn']),explode(";",$salary_structure['default_earn']));
	$not_in_total[]="";
	foreach($earn as $v)
	{
		$val=strrev($v);
		if($default_earn[$val][strlen($default_earn[$val])-1]=='!')
		{
			$not_in_total[]=$val;
		}
		$worksheet->insertNewColumnBefore($start_col_earn_pay,1);
		for($i=6;$i<9;$i++)
		{
			$worksheet->duplicateStyle($worksheet->getStyle($new_col_earn_pay.$i), $start_col_earn_pay.$i);	 	
		}
		$worksheet->setCellValue($start_col_earn_pay.'6',"EARNINGS PAYABLE");
		$worksheet->setCellValue($start_col_earn_pay.'7',$val);
		$worksheet->getColumnDimension($start_col_earn_pay)->setAutoSize(true);

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
	$col=PHPExcel_Cell::columnIndexFromString('E');
	$cell=PHPExcel_Cell::stringFromColumnIndex($col).'6';

	while(($head=$worksheet->getCell($cell)->getValue())!='Release Date')
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
		else if($head=='LEAVES')
		{
			$leaves_excel_arr[$worksheet->getCell(PHPExcel_Cell::stringFromColumnIndex($col).'7')->getValue()]=$col;
		}
		$col++;
		$cell=PHPExcel_Cell::stringFromColumnIndex($col).'6';
	}
	$release_date_col=$col;
	$columns=PHPExcel_Cell::columnIndexFromString($worksheet->getHighestColumn());
	$emp_rows=count($slips);
	$sno=$emp_rows;
	//insert employee names, etc.
	foreach($slips as $v)
	{
		$code=$v['Employee Code'];
		$query="Select `Name of the Employee` from `employee_info` where `Employee Code`='$code'";
		$res=$mysqli->query($query) or die($mysqli->error);
		$row=$res->fetch_assoc();
		$name=$row['Name of the Employee'];
		$worksheet->insertNewRowBefore(8,1);
		
		for($i=0;$i<$columns;$i++)
		{
			$col=PHPExcel_Cell::stringFromColumnIndex($i);
			$worksheet->duplicateStyle($worksheet->getStyle($col.'9'), $col.'8');
			$worksheet->setCellValue($col.'8',$worksheet->getCell($col.'9')->getValue());
		}
		$worksheet->setCellValue('A8',$sno--);
		$worksheet->setCellValue('B8',$name);
		$worksheet->setCellValue('C8',$code);
		$worksheet->setCellValue('D8',$days_in_month);
		$worksheet->setCellValue('E8',$v['Working/Paid Days']);
		$worksheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($release_date_col).'8',$v['Release Date']);
		if($v['Release Date']!="ON HOLD")
		{
			$emp_list_for_total[]=$sno+8;
		}
		$emp_earn=array_combine(array_flip($earn_excel_arr),explode(";",$v['earn']));
		$emp_ded=array_combine(array_flip($ded_excel_arr),explode(";",$v['ded']));
		$emp_earn_pay=array_combine(array_flip($earn_excel_arr),explode(";",$v['earn_payable']));
		$emp_leaves=array_combine(array_flip($leaves_excel_arr),explode(";",$v['leaves']));
		foreach($earn_excel_arr as $k=>$v)
		{
			$worksheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($v)."8",$emp_earn[$k]);
		}
		foreach($ded_excel_arr as $k=>$v)
		{
			$worksheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($v)."8",$emp_ded[$k]);
		}
		foreach($earn_pay_excel_arr as $k=>$v)
		{
			$worksheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($v)."8",floatval(str_replace(",","",$emp_earn_pay[$k])));
		}
		foreach($leaves_excel_arr as $k=>$v)
		{
			$worksheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($v)."8",$emp_leaves[$k]);
		}
		$worksheet->getRowDimension(8)->setRowHeight(-1);
	}
	$worksheet->removeRow($emp_rows+8,1);
	$last_emp_row=$emp_rows+8;
	
	
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
	
	//insert formulas in each employee row
	for($i=8;$i<$last_emp_row;$i++)
	{
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
		
	}
	
	//insert total formulas in last row
	$total_row=$last_emp_row;
	$last_emp_row--;
	for($v_each=$start_earn;$v_each<=$salary_in_hand_col;$v_each++)
	{
		$col_each=PHPExcel_Cell::stringFromColumnIndex($v_each);
		$formula_each="=0";
		foreach($emp_list_for_total as $v)
		{
			$formula_each.="+".$col_each.$v;
		}
		$worksheet->setCellValue($col_each.$total_row,$formula_each);
	}
	

	header('Content-type: application/vnd.ms-excel');
	header("Content-Disposition: attachment;filename='MSS.xlsx'");
	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
	$objWriter->save('php://output');
	exit();
}
if(isset($_GET['date']))
{
	$date=strtotime($_GET['date']);
	get_mss($date);
}
else
{
	header("Location:generate.php");
}
?>
