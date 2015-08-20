<?php
//header("Content-type: text/xml");
# Include http library
//include("LIB_http.php");
#include parse library
include("LIB_parse.php");
require_once('HelperFunctionsAjaxRevamp.php');
//include "LoginMySQL.php";
set_time_limit(0);
/*$db_server = mysql_connect($db_hostname, $db_username, $db_password);
if (!$db_server) die("Unable to connect to MYSQL: " . mysql_error());

mysql_select_db($db_database)
    or die("Unable to select database: " . mysql_error());

*/
$sql="Select * from Stops order by StopName";
$result=mysql_query($sql);
$rowsnum=mysql_num_rows($result);
$stopArray=array();
for($i=0;$i<$rowsnum;$i++)
{
	$row=mysql_fetch_row($result);
	array_push($stopArray,$row[1]);


}


$len=sizeof($stopArray);

$fh=fopen("testBusroutesForError78.txt",'w');
//$strRoute=getData("Girinagar Cross","JP Nagar Water Tank",0,0);
//$strRoute=getData("Adakamaranahalli","Agara Gate (Kanakapura RD)",0,0);
//echo $strRoute;
$fp=fopen("bmtcerrordata\ErrorCode78.txt","r");
while(($str=fgets($fp))!=null)
{

	list($startStop,$endStop)=explode('^',$str);
	$startStop=trim($startStop);
	$endStop=trim($endStop);
	$strRoute=getData($startStop,$endStop,0,0);
	echo $strRoute;
	fwrite($fh,$strRoute);
	fwrite($fh,"\n");

}

/*for($i=1;$i<2;$i++)
{
	$startStop=$stopArray[$i];
	$startDistance=0;
	$endDistance=0;
	for($j=0;$j<$len;$j++)
	{
		$endStop=$stopArray[$j];
        $strRoute=getData($startStop,$endStop,0,0);
        
		echo $strRoute;
        fwrite($fh,$strRoute);
        fwrite($fh,"\n");
	}
}*/

//read the error code specific files and then find out the new routes



function getData($startStop,$endStop,$startDistance,$endDistance)
{
	$showOnlyIndirectRoutes=1;
    // find out the buses that pass through these stops
	$checkString=findDistanceBetweenSourceDestination($startStop,$endStop);
	$arr=array();
	$arr=split(":",$checkString);
	$strRoute1='';
	$strRoute2='';
	$strRoute3='';
	$strRoute4='';
	$minimalDistanceOption7=10000;
	$minimalDistanceOption8=10000;
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
		return $strRoute;
		
		//echo "The distance is walkable";
	}
	else
	{

		$startBuses=explode(",",getBusesForStopWithFrequency($startStop));
		$endBuses=explode(",",getBusesForStopWithFrequency($endStop));

		//print_r($startBuses);
		//print_r($endBuses);	

		// for now pick the first entry as the bus number but we need to do some optimization on choosing the buses based on their frequency, the stop number etc
		
		 //$arrCommonBuses=getCommonBuses($startBuses,$endBuses,$showOnlyIndirectRoutes);
		// print_r($arrCommonBuses);
			
		    //echo getJunctionsForIndirectBuses($arrCommonBuses,$startStop,$endStop,$startDistance,$endDistance);
         $status= getJunctionsForIndirectBusesRevamp3($startStop,$endStop,$startBuses,$endBuses,$startDistance,$endDistance,0);
			

		 //echo "normal status".$status."<br/>";		 
		
        // this should not be the case now but just for the sake of eror handling
        if($status=="404" || $status=="405")
        {
          
               list($endStopDepotName,$endStopDepotDistance)=explode(":",getDepotName($endStop));
			   list($startStopDepotName,$startStopDepotDistance)=explode(":",getDepotName($startStop));
			   $startStopStartDepotDistance=$startStopDepotDistance;
			   $endDepotEndStopDistance=$endStopDepotDistance;
			  // echo "StartStop=>".$startStop.":".$startStopDepotName.",".$startStopDepotDistance."<br/>EndStop=>".$endStop.":".$endStopDepotName.",".$endStopDepotDistance;
			   $totalDistanceForCase1=10000;
			   $totalDistanceForCase2=10000;	  
				// intension is that it is possible to take only one detour, starting from the start depot
				if($startStopDepotDistance < (float)7)
				{
					
					//get the direct buses between the startStop and depot
					
									
					//echo "first case";
					
					//find direct bus between start stop and start depot + distance
					$startStopStartDepotCommonBuses=getBusesCommonBetweenTwoStops($startStop,$startStopDepotName);
					if(sizeof($startStopStartDepotCommonBuses)>0)
					{
			
						$startStopStartDepotDistance=$startStopDepotDistance;
						$startDepotEndStopCommonBuses=getBusesCommonBetweenTwoStops($startStopDepotName,$endStop);
						//print_r($startDepotEndStopCommonBuses);
						//echo sizeof($startDepotEndStopCommonBuses);	
						if(sizeof($startDepotEndStopCommonBuses)>0)
						{
							 $startDepotEndStopDistance=distanceBetweenStops($startStopDepotName,$endStop);
							 $totalDistanceForCase1=$startDistance+$startStopStartDepotDistance+$startDepotEndStopDistance+$endDistance;
							 $routeInfoString="There is no direct or indirect bus availalble between ".$startStop." and ".$endStop." We have tried to find a route that takes you to a stop where you can find a bus to your destination. However you might have to travel little offroute to reach to the intermediate stop";
							 $strRoute1='<Routes>';
							 $strRoute1=$strRoute1.'<Route>';
							 $strRoute1=$strRoute1.'<IsDirectRoute>N</IsDirectRoute>';
							 $strRoute1=$strRoute1.'<ErrorCode>7</ErrorCode>';
							 $strRoute1=$strRoute1."<RouteInfo>".$routeInfoString."</RouteInfo>";
							 $routeDetail="<StartStop>".htmlentities($startStop)."</StartStop>";
							 $routeDetail=$routeDetail."<EndStop>".htmlentities($endStop)."</EndStop>";							 
							 $routeDetail=$routeDetail."<Depot>".htmlentities($startStopDepotName).":".getLatitudeLongitude($startStopDepotName).":".getBusesForStop($startStopDepotName)."</Depot>";
							 $routeDetail=$routeDetail."<BusesStartStopAndDepot>".implode(",",$startStopStartDepotCommonBuses)."</BusesStartStopAndDepot>";
							 $routeDetail=$routeDetail."<BusesEndStopAndDepot>".implode(",",$startDepotEndStopCommonBuses)."</BusesEndStopAndDepot>";
							 $routeDetail=$routeDetail."<DistanceBetweenDepotAndStartStop>".$startStopStartDepotDistance."</DistanceBetweenDepotAndStartStop>";
							 $routeDetail=$routeDetail."<DistanceBetweenDepotAndEndStop>".$startDepotEndStopDistance."</DistanceBetweenDepotAndEndStop>";
							 $routeDetail=$routeDetail."<TotalRouteDistance>".$totalDistanceForCase1."</TotalRouteDistance>";
							 $routeDetail=$routeDetail."<UseDepot>1</UseDepot>";
							 $strRoute1=$strRoute1.'<RouteDetails>'.$routeDetail.'</RouteDetails>';
							 $strRoute1=$strRoute1.'</Route>';
							 $strRoute1=$strRoute1.'</Routes>';
							// echo $strRoute1;		 							
						}
						
					}

				}
				
				// check if the detour is from the endpoint side
				//2. possible to have startStop->EndDepot->endpoint using Bus1, Bus2
				if($endStopDepotDistance < (float) 7)
				{
					//echo "second case";
					//find the direct bus between start stop and end depot +distance
					$startStopEndDepotCommonBuses=getBusesCommonBetweenTwoStops($startStop,$endStopDepotName);
					//print_r($startStopEndDepotCommonBuses);
					if(sizeof($startStopEndDepotCommonBuses)>0)
					{	
						//echo "first condifition";
						$startStopEndDepotDistance=distanceBetweenStops($startStop,$endStopDepotName);
						$endDepotEndStopCommonBuses=getBusesCommonBetweenTwoStops($endStopDepotName,$endStop);
						
						if(sizeof($endDepotEndStopCommonBuses)>0)
						{
							//find the direct bus between end depot and the end stop + distnace
							$endDepotEndStopDistance=$endStopDepotDistance;
							$totalDistanceForCase2=$startDistance+$startStopEndDepotDistance+$endDepotEndStopDistance+$endDistance;
							$routeInfoString="There is no direct or indirect bus availalble between ".$startStop." and ".$endStop.". We have tried to find a route that takes you to a stop where you can find a bus to your destination. However you might have to travel little offroute to reach to the intermediate stop";
							
							$strRoute2='<Routes>';
							 $strRoute2=$strRoute2.'<Route>';
							 $strRoute2=$strRoute2.'<IsDirectRoute>N</IsDirectRoute>';
							 $strRoute2=$strRoute2.'<ErrorCode>8</ErrorCode>';
							 $strRoute2=$strRoute2."<RouteInfo>".$routeInfoString."</RouteInfo>";							
							 $routeDetail="<StartStop>".htmlentities($startStop)."</StartStop>";
							 $routeDetail=$routeDetail."<EndStop>".htmlentities($endStop)."</EndStop>";
							 $routeDetail=$routeDetail."<Depot>".htmlentities($endStopDepotName).":".getLatitudeLongitude($endStopDepotName).":".getBusesForStop($endStopDepotName)."</Depot>";
							 $routeDetail=$routeDetail."<BusesStartStopAndDepot>".implode(",",$startStopEndDepotCommonBuses)."</BusesStartStopAndDepot>";
							 $routeDetail=$routeDetail."<BusesEndStopAndDepot>".implode(",",$endDepotEndStopCommonBuses)."</BusesEndStopAndDepot>";							 
							 $routeDetail=$routeDetail."<DistanceBetweenDepotAndStartStop>".$startStopEndDepotDistance."</DistanceBetweenDepotAndStartStop>";
							 $routeDetail=$routeDetail."<DistanceBetweenDepotAndEndStop>".$endDepotEndStopDistance."</DistanceBetweenDepotAndEndStop>";
							 $routeDetail=$routeDetail."<TotalRouteDistance>".$totalDistanceForCase2."</TotalRouteDistance>";
							 $routeDetail=$routeDetail."<UseDepot>1</UseDepot>";
							 $strRoute2=$strRoute2.'<RouteDetails>'.$routeDetail.'</RouteDetails>';
							 $strRoute2=$strRoute2.'</Route>';
							 $strRoute2=$strRoute2.'</Routes>';
						}					
					}
				}

				//echo "tt".$totalDistanceForCase1.",".$totalDistanceForCase2;
				if($totalDistanceForCase1>$totalDistanceForCase2)
				{
					return $strRoute2;
				}
				else if($totalDistanceForCase1<$totalDistanceForCase2)
				{
					return $strRoute1;
				}

				// if both the start poit detour and the endpoint detour failed that means you dont have the buses either from the 
				// detour to the endpoint or between the start point and the detour
				if($totalDistanceForCase1==10000 && $totalDistanceForCase2 ==10000)
			  {
				if($startStopDepotDistance < (float)7)
				{
					//echo "hi";
					$routeInfoString="There is no direct or indirect bus availalble between ".$startStop." and ".$endStop.". We have tried to find a route that takes you to a stop where you can find a bus to your destination. However you might have to travel little offroute to reach to the intermediate stop";
							
					 $startStopStartDepotCommonBuses=getBusesCommonBetweenTwoStops($startStop,$startStopDepotName);
					 $strRoute3='<Routes>';					 
					 //$strRoute2=$strRoute2.'<Route>';
					 $strRoute3=$strRoute3.'<IsDirectRoute>N</IsDirectRoute>';
					 $strRoute3=$strRoute3.'<ErrorCode>9</ErrorCode>';
					 $strRoute3=$strRoute3."<RouteInfo>".$routeInfoString."</RouteInfo>";							
					 $routeDetail="<StartStop>".htmlentities($startStop)."</StartStop>";
					 $routeDetail=$routeDetail."<EndStop>".htmlentities($endStop)."</EndStop>";
					 $routeDetail=$routeDetail."<Depot>".htmlentities($startStopDepotName).":".getLatitudeLongitude($startStopDepotName).":".getBusesForStop($startStopDepotName)."</Depot>";
					 $routeDetail=$routeDetail."<BusesBetweenStopAndDepot>".implode(",",$startStopStartDepotCommonBuses)."</BusesBetweenStopAndDepot>";
					 $routeDetail=$routeDetail."<DistanceBetweenDepotAndStop>".$startStopStartDepotDistance."</DistanceBetweenDepotAndStop>";
	                  
					//  echo "==================";
					 $startDepotBuses=explode(",",getBusesForStopWithFrequency($startStopDepotName));
					 $totalDistanceBeforeReachingToDepot=$startDistance+$startStopStartDepotDistance;
							
					 $indirectRoutesDetails=getJunctionsForIndirectBusesRevamp3($startStopDepotName,$endStop,$startDepotBuses,$endBuses,0,$endDistance,$showOnlyIndirectRoutes,1,$totalDistanceBeforeReachingToDepot);

					 if($indirectRoutesDetails!="404" && $indirectRoutesDetails !="405")
					{	
						 list($indirectBusesBetweenDepotAndEndpoint,$minimalDistanceOption7)=explode("^",$indirectRoutesDetails);
						 $routeDetail=$routeDetail."<indirectRoutes>".$indirectBusesBetweenDepotAndEndpoint."</indirectRoutes>";
						 //$routeDetail=$routeDetail."<UseDepot>1</UseDepot>";
						 $strRoute3=$strRoute3.'<RouteDetails>'.$routeDetail.'</RouteDetails>';
						 //$strRoute2=$strRoute2.'</Route>';
						 $strRoute3=$strRoute3.'</Routes>';
						 //echo $strRoute3;
					}
					else
					{
						$minimalDistanceOption7=10000;
					}
				}
				if($endStopDepotDistance < (float) 7)
				  {
					  $routeInfoString="There is no direct or indirect bus availalble between ".$startStop." and ".$endStop.". We have tried to find a route that takes you to a stop where you can find a bus to your destination. However you might have to travel little offroute to reach to the intermediate stop";
							
					 $strRoute4='<Routes>';					 
					 $strRoute4=$strRoute4.'<IsDirectRoute>N</IsDirectRoute>';
					 $strRoute4=$strRoute4.'<ErrorCode>10</ErrorCode>';
					 $strRoute4=$strRoute4."<RouteInfo>".$routeInfoString."</RouteInfo>";							
					 $routeDetail="<StartStop>".htmlentities($startStop)."</StartStop>";
					 $routeDetail=$routeDetail."<EndStop>".htmlentities($endStop)."</EndStop>";
					$endDepotEndStopCommonBuses=getBusesCommonBetweenTwoStops($endStopDepotName,$endStop);
					
					  $routeDetail=$routeDetail."<Depot>".htmlentities($endStopDepotName).":".getLatitudeLongitude($endStopDepotName).":".getBusesForStop($endStopDepotName)."</Depot>";
					 $routeDetail=$routeDetail."<BusesBetweenStopAndDepot>".implode(",",$endDepotEndStopCommonBuses)."</BusesBetweenStopAndDepot>";							 
					 $routeDetail=$routeDetail."<DistanceBetweenDepotAndStop>".$endDepotEndStopDistance."</DistanceBetweenDepotAndStop>";
					  
					//  echo "==================";
					 $endDepotBuses=explode(",",getBusesForStopWithFrequency($endStopDepotName));
					 $totalDistanceBeforeReachingToDepot=$endDistance+$endDepotEndStopDistance;
					$indirectRoutesDetails=getJunctionsForIndirectBusesRevamp3($startStop,$endStopDepotName,$startBuses,$endDepotBuses,$startDistance,0,$showOnlyIndirectRoutes,1,$totalDistanceBeforeReachingToDepot);
					 if($indirectRoutesDetails!="404" && $indirectRoutesDetails !="405")
					{	
						 list($indirectBusesBetweenDepotAndEndpoint,$minimalDistanceOption8)=explode("^",$indirectRoutesDetails);
						 
						 $routeDetail=$routeDetail."<indirectRoutes>".$indirectBusesBetweenDepotAndEndpoint."</indirectRoutes>";
						 //$routeDetail=$routeDetail."<UseDepot>1</UseDepot>";
						 $strRoute4=$strRoute4.'<RouteDetails>'.$routeDetail.'</RouteDetails>';
						 //$strRoute2=$strRoute2.'</Route>';
						 $strRoute4=$strRoute4.'</Routes>';
						 //echo $strRoute3;
					} 
					else
					{
						$minimalDistanceOption8=10000;
					}
				  }
				else
				  {
					$minimalDistanceOption7=10000;
					$minimalDistanceOption8=10000;
				  }
			}
			//echo $minimalDistanceOption7.",".$minimalDistanceOption8;
			if($minimalDistanceOption7==10000 && $minimalDistanceOption8==10000)			
			{
									 //the depots are too far away
					$strRoute='<Routes>';
                    $strRoute=$strRoute.'<Route>';
                    $strRoute=$strRoute.'<IsDirectRoute>N</IsDirectRoute>';
                    $strRoute=$strRoute.'<ErrorCode>411</ErrorCode>';
                    $strRoute=$strRoute.'<StartStop>'.$startStop.'</StartStop>';
                    $strRoute=$strRoute.'<EndStop>'.$endStop.'</EndStop>';
                    $strRoute=$strRoute.'</Route>';
                    $strRoute=$strRoute.'</Routes>';
                    return $strRoute;

			}
			else if($minimalDistanceOption7 < $minimalDistanceOption8 )
			{
				return $strRoute3;
			}
			else
			{
				return $strRoute4;
			}
        }
        else
		{
         
		    $strRoute='<Routes>'.$status.'</Routes>';
			return $strRoute;
		}
    }

    //echo "<sample>hhhh</sample>";
}









?>

