<?php
 header("Content-type: text/xml");

# Include http library
//include("LIB_http.php");
#include parse library
include("LIB_parse.php");
require_once('HelperFunctionsAjax.php');

?>

<?php

/********************************************************************************
* The script is used to find out the actual stops for the given addresses.
* The media paramter will determine if the call came from iphone,android,website
********************************************************************************/

if (isset($_GET['source'])&&isset($_GET['destination']))
{
	$mediaType=$_GET['media'];
	if($mediaType=='')
		$mediaType="IPHONE"; //this is to support Bhau's thing for now
	//echo "source".$_GET['source'];
	//echo "<Data>";
	 echo "<Addresses>";
	 echo getStopForAddress($_GET['source'],$log,$mediaType);
	 echo getStopForAddress($_GET['destination'],$log,$mediaType);
	 echo "</Addresses>";
	 //echo "</Data>";



}


if (isset($_GET['sourceStopName'])&&isset($_GET['destStopName']))
{
	echo "dkjasjdkljjalda";
}

?>

