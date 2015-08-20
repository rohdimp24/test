<?php
header("Content-type: text/xml");
# Include http library
//include("LIB_http.php");
#include parse library
include("LIB_parse.php");
require_once('HelperFunctionsAjaxRevamp.php');

?>


<?php

/********************************************************************************
* The script is used to find out the details to put markers for the indirect route.
********************************************************************************/
//print_r($_GET);
if (isset($_GET['sourceStop'])&&isset($_GET['destinationStop'])&&isset($_GET['busNumber']))
{

	//for testing use http://localhost/BMTC/GetBusRouteMarkerDetails.php?sourceStop=Bellandur%20Gate%20(ORR)&destinationStop=KTPO&busNumber=V500W

	$startStop=$_GET['sourceStop'];
	$endStop=$_GET['destinationStop'];
	$busNumber=$_GET['busNumber'];
	//echo $startStop;
	// find out the buses that pass through these stops
	echo traceRouteForBus($startStop,$endStop,$busNumber);
		//echo "<sample>hhhh</sample>";
}

?>

