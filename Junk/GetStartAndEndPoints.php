<?php
header("Content-type: text/xml");
# Include http library
//include("LIB_http.php");
#include parse library
include("LIB_parse.php");
$db_hostname='HCA-5QC28BS';
$db_database='BMTC';
$db_username='root';
$db_password='root123';
//require_once'ProductDetails.php';
$db_server = mysql_connect($db_hostname, $db_username, $db_password);
if (!$db_server) die("Unable to connect to MYSQL: " . mysql_error());

mysql_select_db($db_database)
or die("Unable to select database: " . mysql_error());

?>


<?php

/********************************************************************************

This script is used to fecth the details of teh indirect routes.
The script will first find out if the given address is walkable. The walkable is 
any distance that is lee than 500m.

********************************************************************************/
//print_r($_GET);
if (isset($_GET['StartBusNumber'])&&isset($_GET['EndBusNumber']))
{
	$startBusNumber=$_GET['StartBusNumber'];
	$endBusNumber=$_GET['EndBusNumber'];

	$queryRoute1="Select BusNumber,FirstStop,LastStop FROM newBusRouteNumbers Where BusNumber='".$startBusNumber."'";
    $resultRoute1= mysql_query($queryRoute1);
    $rowRoute1=mysql_fetch_row($resultRoute1);    
		
	$queryRoute2="Select BusNumber,FirstStop,LastStop FROM newBusRouteNumbers Where BusNumber='".$endBusNumber."'";
    $resultRoute2= mysql_query($queryRoute2);
    $rowRoute2=mysql_fetch_row($resultRoute2);
	
	$strRoute='<Routes>';
	$strRoute=$strRoute.'<Route>';
	$strRoute=$strRoute.'<BusNumber>'.$rowRoute1[0].'</BusNumber>';
	$strRoute=$strRoute.'<StartStop>'.$rowRoute1[1].'</StartStop>';
	$strRoute=$strRoute.'<EndStop>'.$rowRoute1[2].'</EndStop>';
	$strRoute=$strRoute.'</Route>';
	$strRoute=$strRoute.'<Route>';
	$strRoute=$strRoute.'<BusNumber>'.$rowRoute2[0].'</BusNumber>';
	$strRoute=$strRoute.'<StartStop>'.$rowRoute2[1].'</StartStop>';
	$strRoute=$strRoute.'<EndStop>'.$rowRoute2[2].'</EndStop>';
	$strRoute=$strRoute.'</Route>';	
	$strRoute=$strRoute.'</Routes>';
	echo $strRoute;
	/*
	$routeDetails=htmlentities($startStop).":".$arr[0].":".$arr[1].":".htmlentities($endStop).":".$arr[2].":".$arr[3].":".$arr[4];
	//$st='sdsd';
	$strRoute='<Routes>';
	$strRoute=$strRoute.'<Route>';
	$strRoute=$strRoute.'<IsDirectRoute>N</IsDirectRoute>';
	$strRoute=$strRoute.'<ErrorCode>5</ErrorCode>';		
	$strRoute=$strRoute.'<RouteDetails>'.$routeDetails.'</RouteDetails>';
	$strRoute=$strRoute.'</Route>';
	$strRoute=$strRoute.'</Routes>';
	echo $strRoute;
	*/	
		//echo "The distance is walkable";
	
}

?>

