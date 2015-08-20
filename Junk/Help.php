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

<?

	

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



<div id="maincontainer">

<div id="topsection">
<div class="innertube">
	<h1>In case you need any help...</h1>
	
</div>
</div>

<!--<div id="contentwrapper">
<div id="contentcolumn">-->
<div class="innertube">
<br/>
<br/>
	Welcome to <a href="">bmtcroutes.in</a>!!! <br/><br/>

<a href="">bmtcroutes.in</a> provides you a platform to plan your bus routes in Bangalore City.<br/><br/>

Just start typing the first few characters of your source and destination address in the boxes at the top, <a href="">bmtcroutes.in</a> will complete the addresses for you. Click on the Search Buses and bmtcroutes.in will find out the various options between your source and destination.
Currently the buses are restricted to BMTC Volvos, Big Circle and Airport Volvos. <br/><br/>

<a href="">bmtcroutes.in</a> tries to first find the direct buses( volvo only) between the source and destination, but
in case the direct buses are not available, it will find the <i>indirect routes</i> ( which requires change in bus to reach the destination). This is the first site of its kind to predict the indirect routes with high degree of precision.<br/>
For e.g. Try Bellandur Gate (ORR) and Forum Mall (Hosur Road) <br/><br/>

In case you need to go to a place which is not getting listed while you type the address, enter the address anyways, <a href="">bmtcroutes.in</a> will try to find out the buses for this new address too!!! <br/><br/>

A suggestive route for first route option is drawn on the map for better understanding of the route.

</div>
<!--</div>
</div>-->

<!--<div id="leftcolumn">-->
<span id="contactStatus">		
		
		
	
</span>

<!--</div>-->
<? include "footer.php" ?>

</div>
</body>
</html>
