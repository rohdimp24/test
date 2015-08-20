<?php
 header("Content-type: text/xml");
echo '<?xml version="1.0" encoding="UTF-8"?>';
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

if (isset($_GET['source'])&&isset($_GET['destination']))
{
	 echo'<Addresses>';
	 echo getStopForAddress($_GET['source'],$log);
	 echo getStopForAddress($_GET['destination'],$log);
	 echo "</Addresses>";
	 //echo "</Data>";



}


if (isset($_GET['sourceStopName'])&&isset($_GET['destStopName']))
{
	echo "dkjasjdkljjalda";
}

?>

