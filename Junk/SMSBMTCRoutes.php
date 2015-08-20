<?php
// header("Content-type: text/xml");
include("LIB_parse.php");
require_once('HelperFunctionsAjax.php');

?>

<?

/********************************************************************************
* The script is used to find out the actual stops for the given addresses.
********************************************************************************/

if (isset($_GET['txtSourceAddress'])&&isset($_GET['txtDestinationAddress']))
{

	$log->LogDebug("---------------------------------------------------------------------------------------");
	$log->LogDebug("Search Request: From=>".$_GET['txtSourceAddress'].",To=>".$_GET['txtDestinationAddress']);
	// First the stops are checked in the stops database
	// Then in the road address database
	// finally go to the internet

	
	
	// basically you get the stopname, lat, long, also the offset distance between what you typed and what you got
	// i think we need to implement a logic in calculating the stop name. The stop that has more buses should be given priorityu among the sorted stops so
	// silk board orr should be given a priority over the silk board hosur road
	
	list($startStop,$startLatitude,$startLongitude,$startDistance)=split(":",getStopForAddress($_GET['txtSourceAddress'],$map,$log));
	list($endStop,$endLatitude,$endLongitude,$endDistance)=split(":",getStopForAddress($_GET['txtDestinationAddress'],$map,$log));
	
	if(strcmp($startStop,"0")==0)
	{
		echo $_GET['txtSourceAddress']." is not a valid address <br/>"; 

		$log->LogDebug("StopFoundResult:".$_GET['txtSourceAddress']." is not a valid address");
		//return;
	}
	else if(strcmp($endStop,"0")==0)
	{	
		echo $_GET['txtDestinationAddress']." is not a valid address <br/>"; 
		$log->LogDebug("StopFoundResult:".$_GET['txtDestinationAddress']." is not a valid address");
		//return;	
	}
	
	else if(strcmp($startStop,"m")==0)
	{
		echo $_GET['txtSourceAddress']." Need to define the starting address more precicely <br/>"; 
		$log->LogDebug("StopFoundResult:".$_GET['txtSourceAddress']." Need to define the starting address more precicely");
		//return;
	}
	else if(strcmp($endStop,"m")==0)
	{
			echo $_GET['txtDestinationAddress']." Need to define the destination address more precicely <br/>"; 
			$log->LogDebug("StopFoundResult:".$_GET['txtDestinationAddress']." Need to define the destination address more precicely");
		//return;
	}

	else
	{
		//echo "<H3>starting address details: StartStop->".$startStop."Lat->".$startLatitude."Long->".$startLongitude."Distance Offset->".$startDistance."</h3><br/>";
		//echo "<h3>destination address details: EndStop->".$endStop."Lat->".$endLatitude."Long->".$endLongitude."Distance Offset->".$endDistance."</h3><br/>";
		
		echo "<b>starting address details: StartStop->".$startStop." Distance Offset->".$startDistance."</b><br/>";
		echo "<b>destination address details: EndStop->".$endStop." Distance Offset->".$endDistance."</b><br/>";
		$log->LogDebug("StopFoundResult: Address is resolved successfully");
		$log->LogDebug("StopFoundResult: For Source ".$_GET['txtSourceAddress']." the stop is resolved to ".$startStop." with offset of ".$startDistance);
		$log->LogDebug("StopFoundResult: For Destination ".$_GET['txtDestinationAddress']." the stop is resolved to ".$endStop." with offset of ".$endDistance);


		// find out the buses that pass through these stops
		
		$startBuses=split(",",getBusesForStop($startStop));
		$endBuses=split(",",getBusesForStop($endStop));
		//print_r($startBuses);	
		//print_r($endBuses);	

		// for now pick the first entry as the bus number but we need to do some optimization on choosing the buses based on their frequency, the stop number etc
		 $arrCommonBuses=getCommonBuses($startBuses,$endBuses);

	
		// now need to filter them based on the junctions. In case the same bus has been found then there is no jusntion 
		// print_r($arrCommonBuses);
		getJunctionsForIndirectBuses($arrCommonBuses,$startStop,$endStop,$startDistance,$endDistance);
		
		// also calculate the distcnae of the orute 
		/*
		list($firstBus,$secondBus)=split(":",getCommonBuses($startBuses,$endBuses));
		echo $firstBus.",".$secondBus."<br/>";
		
		// find the routes of the bus
		echo getIntermediateStopsAndDistance(getRouteName($firstBus),getRouteName($secondBus));
	*/	
		
	




}


if (isset($_GET['sourceStopName'])&&isset($_GET['destStopName']))
{
	echo "dkjasjdkljjalda";
}

?>

