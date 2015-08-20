<?php
header("Content-type: text/xml");
include("LIB_parse.php");
require_once('HelperFunctionsAjaxRevamp.php');

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
	
	$startStop=trim($_GET['sourceStopName']);
	$endStop=trim($_GET['destStopName']);
    $startOffsetDistance=$_GET['sourceOffset'];
    $endOffsetDistance=$_GET['destOffset'];
	$showOnlyIndirectRoutes=$_GET['onlyIndirectRoutes'];
	// find out the buses that pass through these stops
	$checkString=findDistanceBetweenSourceDestination($startStop,$endStop);
	$arr=array();
	$arr=explode(":",$checkString);
	//print_r($arr);
	$minimalDistanceOption7=10000;
	$minimalDistanceOption8=10000;
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
        return;
		
		//echo "The distance is walkable";
	}
	else
	{


            echo getBusRoutesForRendering($log,$startStop,$endStop,$startOffsetDistance,$endOffsetDistance,$showOnlyIndirectRoutes);
            return;


			/**
			The tricky is the startstop and the enddepot.
		So you will make two calls
		for 7
		1. direct us between the start and start depot
		2. direct between the start depot and endpoint

		for start-depot-indirectto endpoint (new 9)
		1. get the direct bus between the start and depot
		2. find the indirect bus between the depot and the endpoint

		for 8
		1. direct bus between teh start stop and the end depoit
		2. direcrt bus betwen the end depot and the endpoint

		for the new 10 which is indirect bus between the start to the endpointdepot and the direct bus between the endpoint
		1. indirect bus between the start stoip and the endpoint deport
		2. diect bus betwene the endpoint depot and the endpoint
		**/

		 //echo "normal status".$status."<br/>";		 
		
        // this should not be the case now but just for the sake of eror handling




    }
		//echo "<sample>hhhh</sample>";
}

?>

