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

This script is used to get the length of the Direct bus route

********************************************************************************/
//print_r($_GET);
if (isset($_GET['sourceStopName'])&&isset($_GET['destStopName'])&&isset($_GET['sourceOffset'])&&isset($_GET['destOffset']))
{
	
	$startStop=$_GET['sourceStopName'];
	$endStop=$_GET['destStopName'];
	$startOffsetDistance=$_GET['sourceOffset'];
	$endOffsetDistance=$_GET['destOffset'];
	echo getDirectBusDistance($startStop,$endStop,$startOffsetDistance,$endOffsetDistance,true);	
}

?>

