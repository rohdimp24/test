<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>bmtcroutes.in</title>
<link rel="stylesheet" type="text/css" href="Scripts/bmtc.css" />
<script type="text/javascript" src="Scripts/validator.js"></script>
<script src="Scripts/mootools.js" type="text/javascript"></script>
<script src="Scripts/sexyalertbox.v1.1.js" type="text/javascript"></script>
<link rel="stylesheet" href="Scripts/sexyalertbox.css" type="text/css" media="all" />
</head>
<body>
<script type="text/javascript">
window.addEvent('domready', function() {
Sexy = new SexyAlertBox();
});
</script>

<?php

	

/*	if($_POST["email"]<>'' && $_POST["comment"]<>'')
	{ 
		//echo "hhh";
		$ToEmail = 'rohitagarwal24@gmail.com'; 
		$EmailSubject = 'Feedback form'; 
		$mailheader = "From: ".$_POST["email"]."\r\n"; 
		$mailheader .= "Reply-To: ".$_POST["email"]."\r\n"; 
		$mailheader .= "Content-type: text/html; charset=iso-8859-1\r\n"; 
		$MESSAGE_BODY = "Name: ".$_POST["name"]."<br>"; 
		$MESSAGE_BODY .= "Email: ".$_POST["email"]."<br>"; 
		$MESSAGE_BODY .= "Comment: ".nl2br($_POST["comment"])."<br>"; 
		//mail($ToEmail, $EmailSubject, $MESSAGE_BODY, $mailheader) or die ("Failure"); 
		//echo "<script language=javascript>document.getElementById(\"contactStatus\").innerHTML = \"Your comments have been sent\" </script>";
		echo "<script language=javascript>alert(\"Your comments have been registered\") </script>";
		
		//echo "<script src=\"Scripts/sexyalertbox.v1.1.js\" language=javascript>Sexy.alert(\"Your comments have been sent successfully!!\")</script>";
	}*/

?>

<?php
	//print_r($_GET);
	if(strcmp($_GET['response'],"OK")==0)
	{
		$servertime=GetTimeStamp();
		$original=$_GET['time'];
		$timediff= getMyTimeDiff($servertime,$original);
		if($timediff<30)
		{ //check that the user cannot refresh the page submit again and again
			echo "<script language=javascript>alert(\"Your comments have been sent\") </script>";
		}
		//unset($_GET);
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

function getMyTimeDiff($t1,$t2)
{
$a1 = explode(":",$t1);
$a2 = explode(":",$t2);
$time1 = (($a1[0]*60*60)+($a1[1]*60)+($a1[2]));
$time2 = (($a2[0]*60*60)+($a2[1]*60)+($a2[2]));
$diff = abs($time1-$time2);
/*$hours = floor($diff/(60*60));
$mins = floor(($diff-($hours*60*60))/(60));
$secs = floor(($diff-(($hours*60*60)+($mins*60))));
$result = $hours.":".$mins.":".$secs;*/
return $diff;
}


?>
<script type="text/javascript">
// function to perform the validation of the form
function doSubmit(inForm)
{
	
	var error_string="";
	// perform the validation for the email and the comment
	var emailFilled=inForm.email.value;
	var name=inForm.name.value;
	var comment=inForm.comment.value;
	//var emailCheck='';
	if(emailFilled.length==0)
		error_string+="Kindly provide your email id <br/>";
	else
	{	
		//var emailCheck=checkEmail(inForm.txtEmail.value);		
		if(!checkEmail(emailFilled))
			error_string+="Kindly provide a valid  email id <br/>";
	}

	var commentsFilled=inForm.comment.value;
	if(commentsFilled.length==0)
		error_string+="Kindly provide your comments <br/>";
	
	////need to write the check for the date of controllership. Also check if yes then the date should be provided


	if(error_string=="")
	{
		//inForm.submit();
		//alert("sdsd");
		/*var status=sendMail(emailFilled,name,comment);
		if(status=="OK")
			alert("Your comments have been submitted");
		else
			alert("There was some problem. Your comments could not be submitted");
		//document.getElementById("contactStatus").innerHTML = "<b>dskjdjs</b>";
		// call the mail.php

	*/

		return true;

	}
	else
	{
		//alert(error_string);
		Sexy.error(error_string);
		return false;
	}
	
}


function sendMail(email,name,comment)
{
	
    // instantiate XMLHttpRequest object
		try
		{
			xhr = new XMLHttpRequest();
		}
		catch (e)
		{
			xhr = new ActiveXObject("Microsoft.XMLHTTP");
		}

		// handle old browsers
		if (xhr == null)
		{
			alert("Ajax not supported by your browser!");
			return;
		}

		// construct URL
		var url = "mail.php?email=" + email+"&name="+name+"&comment="+comment;
		//alert(url);
		// get quote
		/*xhr.onreadystatechange =
		function()
		{
			// only handle loaded requests
			if (xhr.readyState == 4)
			{
				if (xhr.status == 200)
				{
					return xhr.responseText;
					




				}
				else
					alert("Error with Ajax call!");
			}
		}*/

		// since we want to return some values...it has to be done in an synchromous manner. This is the correct way of doing it 
		// chekc http://www.webmasterworld.com/javascript/3483187.htm
		xhr.open("GET", url, false);		
		xhr.send(null);
		return xhr.responseText;
      
}



</script>

<div id="maincontainer">

<div id="topsection"><div class="innertube">
<h1>Do you have something to say?</h1>
</div></div>

<div id="contentwrapper">
<div id="contentcolumn">
<div class="innertube">
<div class="social">
	<br/>
	<br/>
		<a href="https://twitter.com/bmtcroutes" class="twitter-follow-button" data-show-count="false">Follow @bmtcroutes</a>
<script src="//platform.twitter.com/widgets.js" type="text/javascript"></script>
	<br/>
	<br/>
	<iframe src="//www.facebook.com/plugins/like.php?href=http%3A%2F%2Fbmtcroutes.in&amp;send=false&amp;layout=standard&amp;width=450&amp;show_faces=true&amp;action=like&amp;colorscheme=light&amp;font&amp;height=80&amp;appId=117857378301477" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:450px; height:80px;" allowTransparency="true"></iframe>
	<br/>
	<!--<img src="images/twitter3.jpg" width=50 height=50></img>-->
	<br/>			

	
	

</div>

</div>
</div>
</div>

<div id="leftcolumn">
<div class="innertube">

	<div class="info">In order to help us in improving <a href="bmtcroutes.in">bmtcroutes.in</a> kindly send us your feedback
		</div>
	

	<br/>

	<form onsubmit="return doSubmit(this);" action="mail.php" method="post">
	<table width="600" border="0" cellspacing="2" cellpadding="0">
	<tr>
	<td width="15%" class="bodytext">Your name:</td>
	<td width="71%"><input name="name" type="text" id="name" size="32"></td>
	</tr>
	<tr>
	<td class="bodytext">Email address:</td>
	<td><input name="email" type="text" id="email" size="32"></td>
	</tr>
	<tr>
	<td class="bodytext">Comment:</td>
	<td><textarea name="comment" cols="45" rows="10" id="comment" class="bodytext"></textarea></td>
	</tr>
	<tr>
	<td class="bodytext">&nbsp;</td>
	<td align="left" valign="top">
	<input type="submit" value="Send" class="button"></td>
	</tr>
	</table>
	</form> 
		
	<br/>
	<br/>


</div>
</div>

<? include "footer.php" ?>

</div>
</body>
</html>
