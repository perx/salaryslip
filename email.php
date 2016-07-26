<?php
header("Access-Control-Allow-Origin: *");
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . '/PHPMailer-master/');
include "PHPMailerAutoload.php";
function getpdf($file)
{
	require_once("dompdf/dompdf_config.inc.php");
	$dompdf = new DOMPDF();
	$dompdf->set_paper("a3");
	$html_str=file_get_contents($file);
	$dompdf->load_html($html_str);
	$dompdf->render();
	$pdf=$dompdf->output();
	if ( $_dompdf_show_warnings ) {
	  global $_dompdf_warnings;
	  foreach ($_dompdf_warnings as $msg)
		echo $msg . "\n";
	  echo $dompdf->get_canvas()->get_cpdf()->messages;
	  flush();
	}
	$filename=$file.".pdf";
	$filehandler=fopen($filename,'w');
		if($filehandler==NULL)
		{
			echo ("Cannot create file <br/>");
			return NULL;
		}
		chmod($filename,0777);
	fwrite($filehandler,$pdf);
	fclose($filehandler);
	return $filename;
}
function send_email($from,$fromname,$to_email,$subject,$msg,$filename)
{
	//Create a new PHPMailer instance
	$mail = new PHPMailer();
	$mail->isSMTP();

	$mail->isSMTP();

//Enable SMTP debugging
// 0 = off (for production use)
// 1 = client messages
// 2 = client and server messages
$mail->SMTPDebug = 0;

//Set the hostname of the mail server
$mail->Host = 'smtp.gmail.com';

//Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
$mail->Port = 587;

//Set the encryption system to use - ssl (deprecated) or tls
$mail->SMTPSecure = 'tls';

//Whether to use SMTP authentication
$mail->SMTPAuth = true;

//Username to use for SMTP authentication - use full email address for gmail
$mail->Username = "jaspreet.cityinnovates@gmail.com";

//Password to use for SMTP authentication
$mail->Password = "2B/not2B?";

	//Set who the message is to be sent from
	$mail->setFrom($from,$fromname);
			
	//Set who the message is to be sent to
	$mail->addAddress($to_email);
	$mail->CharSet     = 'UTF-8';
	$mail->Encoding    = '8bit';
	$mail->ContentType = 'text/html; charset=utf-8\r\n';
	$mail->WordWrap    = 900;
	$mail->isHTML( TRUE );
	
	//Set the subject line
	$mail->Subject = $subject;
	
	//Read an HTML message body from an external file, convert referenced images to embedded,
	//convert HTML into a basic plain-text alternative body
	$mail->msgHTML($msg);
	if($filename!=false)
	{
		$pdf=getpdf($filename);
		$mail->addAttachment($pdf);
	}
	//send the message, check for errors
	if (!$mail->Send()) {
		return "Mailer Error: " . $mail->ErrorInfo;
	} else {
		if($filename!=false)
		unlink($pdf);
	 	
		return "true";
	}

}
if(isset($_POST['to_email']))
{
	if(isset($_POST['filename']))
		$filename=$_POST['filename'];
	else
		$filename=false;
	if(isset($_POST['from']))
	{
		$from=$_POST['from'];
		$fromname=$_POST['from'];
	}
	else
	{
		$from='hr.cityinnovates@gmail.com';
		$fromname='HR Admin';
	}
	if(isset($_POST['subject']))
		$subject=$_POST['subject'];
	else
		$subject='Important Email';
	if(isset($_POST['msg']))
		$msg=$_POST['msg'];
	else
		$msg='';
	if($_POST['total_emails']==1)
	{
		$to_email=$_POST['to_email'];
		$res=send_email($from,$fromname,$to_email,$subject,$msg,$filename);
		echo $res;
	}
	else
	{
		//loop for each email
	}
}
?>