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

This script is used to fecth the details of teh indirect routes.
The script will first find out if the given address is walkable. The walkable is 
any distance that is lee than 500m.

********************************************************************************/
//print_r($_GET);
if (isset($_GET['sourceStopName'])&&isset($_GET['destStopName'])&&isset($_GET['sourceOffset'])&&isset($_GET['destOffset']))
{
	
	$startStop=$_GET['sourceStopName'];
	$endStop=$_GET['destStopName'];
	$startDistance=$_GET['sourceOffset'];
	$endDistance=$_GET['destOffset'];
	$showOnlyIndirectRoutes=$_GET['onlyIndirectRoutes'];
	// find out the buses that pass through these stops
	$checkString=findDistanceBetweenSourceDestination($startStop,$endStop);
	$arr=array();
	$arr=explode(":",$checkString);
	//print_r($arr);
	if($arr[4]<.7)
	{
		$strRoute='';
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
		
		//echo "The distance is walkable";
	}
	else
	{

		$startBuses=explode(",",getBusesForStop($startStop));
		$endBuses=explode(",",getBusesForStop($endStop));
		//print_r($startBuses);	
		//print_r($endBuses);	

		// for now pick the first entry as the bus number but we need to do some optimization on choosing the buses based on their frequency, the stop number etc
		
		 $arrCommonBuses=getCommonBuses($startBuses,$endBuses,$showOnlyIndirectRoutes);
		// print_r($arrCommonBuses);
			
		 echo getJunctionsForIndirectBuses($arrCommonBuses,$startStop,$endStop,$startDistance,$endDistance);
	}
		//echo "<sample>hhhh</sample>";
}

?>

