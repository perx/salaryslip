<?php
session_start();
if($_GET['table']=="set")
{
	$_SESSION['table']=$_POST['tabledata'];
}
else if($_GET['table']=="get")
{
	set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . '/Classes/');
	include 'PHPExcel/IOFactory.php';
	$data="<!doctype html><html><body>";
	$data.=$_SESSION['table'];
	$data.="</body></html>";
	
	// Put the html into a temporary file
	$tmpfile = '11a.html';
	file_put_contents($tmpfile, $data) or die("err");
	
	// Read the contents of the file into PHPExcel Reader class
	$objReader = PHPExcel_IOFactory::createReaderForFile($tmpfile);
	$content = $objReader->load($tmpfile); 
	
	// Pass to writer and output as needed
	header('Content-type: application/vnd.ms-excel');
	header("Content-Disposition: attachment;filename='Data.xlsx'");
	$objWriter = PHPExcel_IOFactory::createWriter($content, 'Excel2007');
	$objWriter->save('php://output');
	//$objWriter->save("images/output.xlsx");
	
	// Delete temporary file
	unlink($tmpfile);
}
?>