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
if (isset($_GET['BusNumber']))
{
	$busNumber=$_GET['BusNumber'];

	$sql="Select `StopName` from `newbusdetails` where BusNumber='".$busNumber."' order by StopNumber ASC";
	//echo $sql;
	$result=mysql_query($sql);
	$rownum=mysql_num_rows($result);
	$strRoute='';
	$strRoute='<Routes>';
	for($i=0;$i<$rownum;$i++)
	{
		$strRoute=$strRoute.'<Route>';
		$row=mysql_fetch_row($result);
		//echo $row[0]."<br/>";
		$sql1="Select * from `stops` where StopName='".$row[0]."'";
		$result1=mysql_query($sql1);
		$row1=mysql_fetch_row($result1);
		$routeDetails=htmlentities(trim($row[0])).":".$row1[2].":".$row1[3];
		//echo $routeDetails."<br/>";
		$strRoute=$strRoute.'<RouteDetails>'.$routeDetails.'</RouteDetails>';
		$strRoute=$strRoute.'</Route>';
	}
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

