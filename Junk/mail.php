<?php
if (isset($_POST['email'])&&isset($_POST['comment']))
{
	$ToEmail = 'admin@bmtcroutes.in'; 
		$EmailSubject = 'Feedback form'; 
		$mailheader = "From: ".$_POST["email"]."\r\n"; 
		$mailheader .= "Reply-To: ".$_POST["email"]."\r\n"; 
		$mailheader .= "Content-type: text/html; charset=iso-8859-1\r\n"; 
		$MESSAGE_BODY = "Name: ".$_POST["name"]."<br>"; 
		$MESSAGE_BODY .= "Email: ".$_POST["email"]."<br>"; 
		$MESSAGE_BODY .= "Comment: ".nl2br($_POST["comment"])."<br>"; 
	mail($ToEmail, $EmailSubject, $MESSAGE_BODY, $mailheader) or die ("Failure");	
	header("Location:ContactUs.php?response=OK&time=".GetTimeStamp());
	

}

function GetTimeStamp()
{
	$accessDate=date("Y-m-d");
	$timezone='Asia/Calcutta';
	date_default_timezone_set($timezone);
	$tz = date_default_timezone_get();
	$accessTime=date("H:i:s");
	$timeStamp=$accessTime;
	return $timeStamp;
}


?>

