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
require_once('HelperFunctionsAjaxRevamp.php');
// connect to the database
$db_server = mysql_connect($db_hostname, $db_username, $db_password);
if (!$db_server) die("Unable to connect to MYSQL: " . mysql_error());

mysql_select_db($db_database)
or die("Unable to select database: " . mysql_error());

$fh=fopen("bmtcerrordata\\remainingErrorCode78.txt","r");
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
        if($depotErrorCode!="1") //7,8
        {
			/*$depotNameString=return_between($str,"<Depot>","</Depot>",EXCL);
			$busesBetweenStartStopAndDepot=return_between($str,"<BusesBetweenStopAndDepot>","</BusesBetweenStopAndDepot>",EXCL);
			$busesBetweenEndStopAndDepot=return_between($str,"<BusesBetweenStopAndDepot>","</BusesBetweenStopAndDepot>",EXCL);
			$distanceBetweenDepotAndStartStop=return_between($str,"<DistanceBetweenDepotAndStop>","</DistanceBetweenDepotAndStop>",EXCL);
			$distanceBetweenDepotAndEndStop=return_between($str,"<DistanceBetweenDepotAndStop>","</DistanceBetweenDepotAndStop>",EXCL);
			$totalRouteDistance=floatval($distanceBetweenDepotAndStartStop)+floatval($distanceBetweenDepotAndEndStop);
			*/
            $indirectRouteErrorCodeArray=array();
            $indirectStartStopsArray=array();
            $indirectStartBusesArray=array();
            $indirectDepotArray=array();
            $indirectDistanceBetweenDepotAndStartStopArray=array();
            $indirectDistanceBetweenDepotAndEndStopArray=array();
            $indirectEndBusesArray=array();
            $indirectEndStopsArray=array();
            $indirectTotalRouteDistanceArray=array();

            $routes=parse_array($str,"<Route>","</Route>");
            $numRouteCount=0;
            foreach($routes as $route)
            {
                $numRouteCount++;
                $indirectRouteErrorCodeArray=parse_array($str,"<ErrorCode>","</ErrorCode>");
                $indirectStartStopsArray=parse_array($str,"<StartStop>","</StartStop>");
                $indirectStartBusesArray=parse_array($str,"<BusesStartStopAndDepot>","</BusesStartStopAndDepot>");
                $indirectDepotArray=parse_array($str,"<Depot>","</Depot>");
                $indirectEndBusesArray=parse_array($str,"<BusesEndStopAndDepot>","</BusesEndStopAndDepot>");
                $indirectEndStopsArray=parse_array($str,"<EndStop>","</EndStop>");
            }

            for($i=0;$i<$numRouteCount;$i++)
            {
                $depotNameString=return_between($indirectDepotArray[$i],"<Depot>","</Depot>",EXCL);
                list($depotName,$lat,$lon)=explode(":",$depotNameString);
                $distanceBetweenDepotAndStartStop=distanceBetweenStops($startStop,$depotName);
                $distanceBetweenDepotAndEndStop=distanceBetweenStops($endStop,$depotName);
                $totalRouteDistance=floatval($distanceBetweenDepotAndStartStop)+floatval($distanceBetweenDepotAndEndStop);
                $sql="INSERT INTO directdepotbusroutes (DepotErrorCode,
												  StartStop,
												  EndStop,
												  DepotNameString,
												  BusesBetweenStartStopAndDepot,
												  BusesBetweenEndStopAndDepot,
												  DistanceBetweenDepotAndStartStop,
												  DistanceBetweenDepotAndEndStop,
												  TotalRouteDistance) Values('".
                    intval(return_between($indirectRouteErrorCodeArray[$i],"<ErrorCode>","</ErrorCode>",EXCL))."','".
                    $startStop."','".
                    $endStop."','".
                    $depotNameString."','".
                    return_between($indirectStartBusesArray[$i],"<BusesStartStopAndDepot>","</BusesStartStopAndDepot>",EXCL)."','".
                    return_between($indirectEndBusesArray[$i],"<BusesEndStopAndDepot>","</BusesEndStopAndDepot>",EXCL)."','".
                    floatval($distanceBetweenDepotAndStartStop)."','".
                    floatval($distanceBetweenDepotAndEndStop)."','".
                    floatval($totalRouteDistance)."')";
                echo $sql;
                $result=mysql_query($sql);
                if($result)
                    echo "insert is successful";
                else
                    echo "<b>INSERT ERROR: ".mysql_error()."</b>". $sql."<br/>";


            }



        }
		else
		{
			$depotErrorCode="6";
			/*$depotNameString=return_between($str,"<FirstJunction>","</FirstJunction>",EXCL);
			$busesBetweenStartStopAndDepot=return_between($str,"<StartBuses>","</StartBuses>",EXCL);
			$busesBetweenEndStopAndDepot=return_between($str,"<EndBuses>","</EndBuses>",EXCL);
			list($depotName,$lat,$lon)=explode(":",$depotNameString);
			$distanceBetweenDepotAndStartStop=distanceBetweenStops($startStop,$depotName);
			$distanceBetweenDepotAndEndStop=distanceBetweenStops($endStop,$depotName);
			$totalRouteDistance=floatval($distanceBetweenDepotAndStartStop)+floatval($distanceBetweenDepotAndEndStop);
			*/
			
			$indirectRouteErrorCodeArray=array();
			$indirectStartStopsArray=array();
			$indirectStartBusesArray=array();
			$indirectDepotArray=array();
			$indirectDistanceBetweenDepotAndStartStopArray=array();
			$indirectDistanceBetweenDepotAndEndStopArray=array();
			$indirectEndBusesArray=array();
			$indirectEndStopsArray=array();
			$indirectTotalRouteDistanceArray=array();
			
			$routes=parse_array($str,"<Route>","</Route>");
			$numRouteCount=0;
			foreach($routes as $route)
			{
				$numRouteCount++;
				$indirectRouteErrorCodeArray=parse_array($str,"<ErrorCode>","</ErrorCode>");
				$indirectStartStopsArray=parse_array($str,"<StartStop>","</StartStop>");
				$indirectStartBusesArray=parse_array($str,"<StartBuses>","</StartBuses>");
				$indirectDepotArray=parse_array($str,"<FirstJunction>","</FirstJunction>");
				$indirectEndBusesArray=parse_array($str,"<EndBuses>","</EndBuses>");
				$indirectEndStopsArray=parse_array($str,"<EndStop>","</EndStop>");			
			}
			
			
			for($i=0;$i<$numRouteCount;$i++)
			{
				$depotNameString=return_between($indirectDepotArray[$i],"<FirstJunction>","</FirstJunction>",EXCL);
				list($depotName,$lat,$lon)=explode(":",$depotNameString);
				$distanceBetweenDepotAndStartStop=distanceBetweenStops($startStop,$depotName);
				$distanceBetweenDepotAndEndStop=distanceBetweenStops($endStop,$depotName);
				$totalRouteDistance=floatval($distanceBetweenDepotAndStartStop)+floatval($distanceBetweenDepotAndEndStop);
				$sql="INSERT INTO directdepotbusroutes (DepotErrorCode,
												  StartStop,
												  EndStop,
												  DepotNameString,
												  BusesBetweenStartStopAndDepot,
												  BusesBetweenEndStopAndDepot,
												  DistanceBetweenDepotAndStartStop,
												  DistanceBetweenDepotAndEndStop,
												  TotalRouteDistance) Values('".
												  intval(return_between($indirectRouteErrorCodeArray[$i],"<ErrorCode>","</ErrorCode>",EXCL))."','".
														  $startStop."','".
														  $endStop."','".
														  $depotNameString."','".
														  return_between($indirectStartBusesArray[$i],"<StartBuses>","</StartBuses>",EXCL)."','".
														  return_between($indirectEndBusesArray[$i],"<EndBuses>","</EndBuses>",EXCL)."','".
														  floatval($distanceBetweenDepotAndStartStop)."','".
														  floatval($distanceBetweenDepotAndEndStop)."','".
														  floatval($totalRouteDistance)."')";
				echo $sql;
				/*$result=mysql_query($sql);
				if($result)
					echo "insert is successful";
				else
					echo "<b>INSERT ERROR: ".mysql_error()."</b>". $sql."<br/>";
				*/
				
			}		
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
	
	//echo "<hr/>";
    $count++;
	//if($count==10)
		//exit();
}


