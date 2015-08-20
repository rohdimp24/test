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
	//print_r($_GET);
	if(strcmp($_GET['response'],"OK")==0)
	{
		$servertime=GetTimeStamp();
		$original=$_GET['time'];
		$timediff= getMyTimeDiff($servertime,$original);
		if($timediff<30)
		{ //check that the user cannot refresh the page submit again and again
			echo "<script language=javascript>alert(\"Thanks for reporting the problem\") </script>";
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
	var comboOption=inForm.combo.value;
	//var emailCheck='';
	if(comboOption=="NONE")
		error_string+="Kindly select the problem category <br/>";

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
		error_string+="Kindly describe the problem <br/>";
	
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



</script>

<div id="maincontainer">

<div id="topsection"><div class="innertube">
<h1>Are you disappointed with the search results ?</h1>
</div></div>




	<div class="info">We are sorry that <a href="bmtcroutes.in">bmtcroutes.in</a> did not provide you the correct route.
	We want to correct <a href="bmtcroutes.in">bmtcroutes.in</a> so that other people don't face the same problem.
	Kindly help us by describing the problem you faced. We would urge you to provide the email address so that we can keep you updated on the status of your problem.
		</div>
	<br/>

	<form onsubmit="return doSubmit(this);" action="reportErrorMail.php" method="post">
	<table width="800" border="0" cellspacing="2" cellpadding="0">
	<tr>
	<td width="35%" class="formHeadings">Categorize your problem:</td>
	<td width="75%">
	<select name="combo">
	  <option value="NONE">--------Select-----------------------</option>
	  <option value="DIRECTBUS">I know for sure that there is a direct bus but results gave me only indirect routes</option>
	  <option value="HOPS">The route having two junctions has been shown above single junction route</option>
	  <option value="MAP">The map shows some disconnected lines</option>
	  <option value="LENGHTHY">You showed a very lengthy route. I know there is a shorter route</option>
	  <option value="ADDRESSNOTMAP">I typed a place that is in Bangalore but the result says Address not Found/mapped      </option>
	  <option value="INCORRECTMAP">The address has not been mapped correctly</option>
	   <option value="OTHER">Other</option>
	</select>
	</td>
	<tr>
	<td class="formHeadings">Description of your problem:<br/>(It will help us to understand your problem better):</td>
	<td><textarea name="comment" cols="61" rows="10" id="comment" class="bodytext"></textarea></td>
	</tr>
	<tr>
	<td class="formHeadings">Kindly provide your Email address:</td>
	<td><input name="email" type="text" id="email" size="82"></td>
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





<? include "footer.php" ?>

</div>
</body>
</html>
