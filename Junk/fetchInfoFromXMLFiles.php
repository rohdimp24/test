<?php
/**
 * Created by PhpStorm.
 * User: fz015992
 * Date: 12/16/13
 * Time: 2:28 PM
 */
//need to define a table and then enter this data in the table for the retrieval.
 
require_once "LIB_parse.php";
require_once 'loginMySQL.php';
// connect to the database
$db_server = mysql_connect($db_hostname, $db_username, $db_password);
if (!$db_server) die("Unable to connect to MYSQL: " . mysql_error());

mysql_select_db($db_database)
or die("Unable to select database: " . mysql_error());

$fh=fopen("bmtcerrordata\\remainingErrorCode910.txt","r");
$count=0;
while(($str=fgets($fh))!=null)
{
	echo "<b>linenumber</b>".$count."<br/>";
  //  echo $str."<br/>";
    $startStop=return_between($str,"<StartStop>","</StartStop>",EXCL);
    $endStop=return_between($str,"<EndStop>","</EndStop>",EXCL);
    $depotErrorCode=return_between($str,"<ErrorCode>","</ErrorCode>",EXCL);

    if($depotErrorCode!="411")
    {
        if($depotErrorCode!="9"&&$depotErrorCode!="10")
        {
            
			echo "error77";
			$depotErrorCode="7";
			
        }
		$depotNameString=return_between($str,"<Depot>","</Depot>",EXCL);
		//need to further process the depot name string to find the depot name, buses and the lat/lon values
		$busesBetweenStopAndDepot=return_between($str,"<BusesBetweenStopAndDepot>","</BusesBetweenStopAndDepot>",EXCL);
		$distanceBetweenDepotAndStop=return_between($str,"<DistanceBetweenDepotAndStop>","</DistanceBetweenDepotAndStop>",EXCL);
		if($depotErrorCode!="9"&&$depotErrorCode!="10")
			$indirectRoute=$str;
		else
			$indirectRoute= return_between($str,"<indirectRoutes>","</indirectRoutes>",EXCL);
		$routes=parse_array($indirectRoute,"<Route>","</Route>");
		//print_r($routes);

		$indirectRouteErrorCodeArray=array();
		$indirectStartStopsArray=array();
		$indirectStartBusesArray=array();
		$indirectFirstJunctionsArray=array();
		$indirectDistanceBetweenJunctionArray=array();
		$indirectSecondJunctionsArray=array();
		$indirectEndBusesArray=array();
		$indirectEndStopsArray=array();
		$indirectTotalIndirectRouteDistanceArray=array();
		$indirectTotalRouteDistanceArray=array();
		$numRouteCount=0;
		//parse the routes
		foreach($routes as $route)
		{	
			$numRouteCount++;
			$indirectRouteErrorCodeArray=parse_array($indirectRoute,"<ErrorCode>","</ErrorCode>");
			$indirectStartStopsArray=parse_array($indirectRoute,"<StartStop>","</StartStop>");
			$indirectStartBusesArray=parse_array($indirectRoute,"<StartBuses>","</StartBuses>");
			$indirectFirstJunctionsArray=parse_array($indirectRoute,"<FirstJunction>","</FirstJunction>");
			$indirectDistanceBetweenJunctionArray=parse_array($indirectRoute,"<DistanceBetweenJunction>","</DistanceBetweenJunction>");
			$indirectSecondJunctionsArray=parse_array($indirectRoute,"<SecondJunction>","</SecondJunction>");
			$indirectEndBusesArray=parse_array($indirectRoute,"<EndBuses>","</EndBuses>");
			$indirectEndStopsArray=parse_array($indirectRoute,"<EndStop>","</EndStop>");
			$indirectTotalIndirectRouteDistanceArray=parse_array($indirectRoute,"<TotalIndirectRouteDistance>","</TotalIndirectRouteDistance>");
			$indirectTotalRouteDistanceArray=parse_array($indirectRoute,"<TotalRouteDistance>","</TotalRouteDistance>");
		}
		for($i=0;$i<$numRouteCount;$i++)
		{	
			if($i==0)
				echo "StartStop:".$startStop.","."EndStop:".$endStop.",depotErrorCode:".intval($depotErrorCode)."n";
			echo "<br/>";
			//echo "Route#".$i;
			echo "ErrorCode:".intval(return_between($indirectRouteErrorCodeArray[$i],"<ErrorCode>","</ErrorCode>",EXCL))."\t";
			//echo "StartStop:".$indirectStartStopsArray[$i]."\t";
			//echo "StartBuses:".$indirectStartBusesArray[$i]."\t";
			//echo "FirstJunction:".$indirectFirstJunctionsArray[$i]."\t";
			echo "DistanceBetweenJucntion:".$indirectDistanceBetweenJunctionArray[$i]."\t";
			//echo "SecondJunction:".$indirectSecondJunctionsArray[$i]."\t";
			//echo "EndBuses:".$indirectEndBusesArray[$i]."\t";
			//echo "EndStop:".$indirectEndStopsArray[$i]."\t";
			echo "TotalIndirectRouteDistance:".$indirectTotalIndirectRouteDistanceArray[$i]."\t";
			echo "TotalRouteDistance".$indirectTotalRouteDistanceArray[$i]."\t";
			
			$sql="INSERT INTO depotbusroutes (DepotErrorCode,
											  SourceStartStop,
											  DestinationEndStop,
											  DepotNameString,
											  BusesBetweenStopAndDepot,
											  DistanceBetweenDepotAndStop,
											  IndirectRouteErrorCode,
											  IndirectStartStop,
											  IndirectStartBuses,
											  IndirectFirstJunction,
											  IndirectSecondJunction,
											  IndirectDistanceBetweenJunction,
											  IndirectEndStop,
											  IndirectEndBuses,
											  IndirectTotalIndirectRouteDistance,
											  IndirectTotalRouteDistance) Values('".intval($depotErrorCode)."','".
													  $startStop."','".
													  $endStop."','".
													  $depotNameString."','".
													  $busesBetweenStopAndDepot."','".
													  floatval($distanceBetweenDepotAndStop)."','".
													  intval(return_between($indirectRouteErrorCodeArray[$i],"<ErrorCode>","</ErrorCode>",EXCL))."','".
													  return_between($indirectStartStopsArray[$i],"<StartStop>","</StartStop>",EXCL)."','".
													  return_between($indirectStartBusesArray[$i],"<StartBuses>","</StartBuses>",EXCL)."','".
													  return_between($indirectFirstJunctionsArray[$i],"<FirstJunction>","</FirstJunction>",EXCL)."','".
													  return_between($indirectSecondJunctionsArray[$i],"<SecondJunction>","</SecondJunction>",EXCL)."','".
													  floatval(return_between($indirectDistanceBetweenJunctionArray[$i],"<DistanceBetweenJunction>","</DistanceBetweenJunction>",EXCL))."','".
													  return_between($indirectEndStopsArray[$i],"<EndStop>","</EndStop>",EXCL)."','".
													  return_between($indirectEndBusesArray[$i],"<EndBuses>","</EndBuses>",EXCL)."','".
													  floatval(return_between($indirectTotalIndirectRouteDistanceArray[$i],"<TotalIndirectRouteDistance>","</TotalIndirectRouteDistance>",EXCL))."','".
													  floatval(return_between($indirectTotalRouteDistanceArray[$i],"<TotalRouteDistance>","</TotalRouteDistance>",EXCL))."')";
			echo $sql;
			/*$result=mysql_query($sql);	
			if($result)
				echo "insert is successful";
			else
				echo "<b>INSERT ERROR: ".mysql_error()."</b>". $sql."<br/>";
			*/							  
		}
       
    }
    else //411
    {
        echo "StartStop:".$startStop.","."EndStop:".$endStop.",depotErrorCode:".$depotErrorCode."n";
		$sql="INSERT INTO depotbusroutes (DepotErrorCode,SourceStartStop,DestinationEndStop)
													Values('".intval($depotErrorCode)."','".
													  $startStop."','".
													  $endStop."')";
		echo $sql;
		/*$result=mysql_query($sql);	
		if($result)
				echo "insert 411 is successful";
			else
				echo "<b>INSERT ERROR 411: ".mysql_error()."</b>". $sql."<br/>";
		*/
    }
	
	echo "<hr/>";
    $count++;
	//if($count==10)
		//exit();
}


