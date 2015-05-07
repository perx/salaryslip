<?php 
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . '/Classes/');
include 'PHPExcel/IOFactory.php';
	
function days_in_month($date) 
{ 
	$year=date('Y',$date);
	$month=date('m',$date);
	// calculate number of days in a month 
	return $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31); 
}

$date=strtotime($_GET['date']);
$objReader = PHPExcel_IOFactory::createReaderForFile('images/MSS.xlsx');
$objPHPExcel = $objReader->load('images/MSS1.xlsx');
$worksheet=$objPHPExcel->getActiveSheet(); 
$month_line="SALARY STATEMENT FOR ".strtoupper(date('F Y',$date));
$days_in_month=days_in_month($date);
$worksheet->setCellValue('A4',$month_line);

for($release_col=0;$worksheet->getCellByColumnAndRow($release_col,7)->getValue()!="Release Date";$release_col++)
{echo "";}
for($max_row=8;$worksheet->getCellByColumnAndRow(0,$max_row)->getValue()!="";$max_row++)
{echo "";}
$date=date('d/m/Y',time());
$release_col=PHPExcel_Cell::stringFromColumnIndex($release_col);

for($i=8;$i<$max_row;$i++)
{
	$worksheet->setCellValue('D'.$i,$days_in_month);
	$worksheet->setCellValue($release_col.$i,$date);
}

header('Content-type: application/vnd.ms-excel');
header("Content-Disposition: attachment;filename='MSS.xlsx'");
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');
exit();
?>