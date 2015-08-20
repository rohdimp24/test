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
********************************************************************************/

//print_r($_GET);
if (isset($_GET['sourceLat'])&&isset($_GET['sourceLong']))
{
	
	 echo getBusStopsGivenLatLong($_GET['sourceLat'],$_GET['sourceLong']);
	
}

?>

