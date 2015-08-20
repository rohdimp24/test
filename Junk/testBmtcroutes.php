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

$fh=fopen("testBusroutesData3.txt",'w');


for($i=0;$i<2;$i++)
{
	$startStop=$stopArray[$i];
	$startDistance=0;
	$endDistance=0;
	for($j=0;$j<4;$j++)
	{
		$endStop=$stopArray[$j];
        $strRoute=getData($startStop,$endStop,0,0);
		echo "<br/>checing routes between".$startStop."---".$endStop."<br/>";
        echo $strRoute;
        fwrite($fh,$strRoute);
        fwrite($fh,"\n");
	}
}


function getData($startStop,$endStop,$startDistance,$endDistance)
{
    //$showOnlyIndirectRoutes=$_GET['onlyIndirectRoutes'];
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
        return $strRoute;

        //echo "The distance is walkable";
    }
    else
    {

        $startBuses=explode(",",getBusesForStopWithFrequency($startStop));
        $endBuses=explode(",",getBusesForStopWithFrequency($endStop));

          //removed the use of the showdirect buses form the helper
        $status= getJunctionsForIndirectBusesRevamp2($startStop,$endStop,$startBuses,$endBuses,$startDistance,$endDistance,0);
        //echo "normal status".$status."<br/>";

        // this should not be the case now but just for the sake of eror handling
        if($status=="404" || $status=="405")
        {
            list($endStopDepotName,$endStopDepotDistance)=explode(":",getDepotName($endStop));
            list($startStopDepotName,$startStopDepotDistance)=explode(":",getDepotName($startStop));
            $totalDistanceForCase1=10000;
            $totalDistanceForCase2=10000;

            if($startStopDepotDistance < (float)7)
            {
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
                        $routeDetail=$routeDetail."<Depot>".htmlentities($startStopDepotName).":".getLatitudeLongitude($startStopDepotName)."</Depot>";
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
                        $strRoute1=$strRoute1."<RouteInfo>".$routeInfoString."</RouteInfo>";
                        $routeDetail="<StartStop>".htmlentities($startStop)."</StartStop>";
                        $routeDetail=$routeDetail."<EndStop>".htmlentities($endStop)."</EndStop>";
                        $routeDetail=$routeDetail."<Depot>".htmlentities($endStopDepotName).":".getLatitudeLongitude($endStopDepotName)."</Depot>";
                        $routeDetail=$routeDetail."<BusesStartStopAndDepot>".implode(",",$startStopEndDepotCommonBuses)."</BusesStartStopAndDepot>";
                        $routeDetail=$routeDetail."<BusesEndStopAndDepot>".implode(",",$endDepotEndStopCommonBuses)."</BusesEndStopAndDepot>";
                        $routeDetail=$routeDetail."<DistanceBetweenDepotAndStartStop>".$startStopEndDepotDistance."</DistanceBetweenDepotAndStartStop>";
                        $routeDetail=$routeDetail."<DistanceBetweenDepotAndEndStop>".$endDepotEndStopDistance."</DistanceBetweenDepotAndEndStop>";
                        $routeDetail=$routeDetail."<TotalRouteDistance>".$totalDistanceForCase2."</TotalRouteDistance>";
                        $routeDetail=$routeDetail."<UseDepot>1</UseDepot>";
                        $strRoute2=$strRoute2.'<RouteDetails>'.$routeDetail.'</RouteDetails>';
                        $strRoute2=$strRoute2.'</Route>';
                        $strRoute2=$strRoute2.'</Routes>';
                        // echo $strRoute2;
                    }
                }
            }

            if($totalDistanceForCase1<10000 && $totalDistanceForCase2<10000)
            {
                if($totalDistanceForCase1>$totalDistanceForCase2 )
                {
                    return $strRoute2;
                }
                else
                {
                    return $strRoute1;
                }
            }
            // if both the start poit detour and the endpoint detour failed that means you dont have the buses either from the
            // detour to the endpoint or between the start point and the detour
           else if($totalDistanceForCase1==10000 && $totalDistanceForCase2 ==10000)
            {
                if($startStopDepotDistance < (float)7 && $endStopDepotDistance < (float) 7)
                {
                    //find the direct bus between start stop and end depot +distance
                    $startStopStartDepotCommonBuses=getBusesCommonBetweenTwoStops($startStop,$startStopDepotName);
                    //print_r($startStopStartDepotCommonBuses);
                    $endDepotEndStopCommonBuses=getBusesCommonBetweenTwoStops($endStopDepotName,$endStop);
                    //print_r($endDepotEndStopCommonBuses);
                    if(sizeof($startStopStartDepotCommonBuses)>0 && sizeof($endDepotEndStopCommonBuses) >0)
                    {
                        //echo "hhh";
                        //get the buses between the two depots
                        $busesForStartDepot=explode(",",getBusesForStopWithFrequency($startStopDepotName));
                        $busesForEndDepot=explode(",",getBusesForStopWithFrequency($endStopDepotName));
                        $startStopOffsetDistance=$startDistance;
                        $endStopOffsetDistance=$endDistance;
                        $startStopStartDepotCommonBusesString=implode(",",$startStopStartDepotCommonBuses);
                        $endDepotEndStopCommonBusesString=implode(",",$endDepotEndStopCommonBuses);

                        $interDepotStatus= getJunctionsForInterDepotTravel($startStop,$endStop,$startStopDepotName,$endStopDepotName,$busesForStartDepot,$busesForEndDepot,$startStopOffsetDistance,$endStopOffsetDistance,$startStopStartDepotCommonBusesString,$endDepotEndStopCommonBusesString,$startStopDepotDistance,$endStopDepotDistance,0);

                        //need to add the bus info between the startstop and depot+last depot and end point

                        if($interDepotStatus=="409"||$interDepotStatus=="410")
                        {
                            // buses not found between the depots using a single or no hop
                            $strRoute='<Routes>';
                            $strRoute=$strRoute.'<Route>';
                            $strRoute=$strRoute.'<IsDirectRoute>N</IsDirectRoute>';
                            $strRoute=$strRoute.'<ErrorCode>'.$interDepotStatus.'</ErrorCode>';
                            $strRoute=$strRoute.'</Route>';
                            $strRoute=$strRoute.'</Routes>';
                            return $strRoute;
                        }
                        else
                        {
                            $strRoute = $interDepotStatus;
                            return $strRoute;
                        }


                    }
                }
                else
                {
                    //the depots are too far away
                    $strRoute='<Routes>';
                    $strRoute=$strRoute.'<Route>';
                    $strRoute=$strRoute.'<IsDirectRoute>N</IsDirectRoute>';
                    $strRoute=$strRoute.'<ErrorCode>411</ErrorCode>';
                    $strRoute=$strRoute.'</Route>';
                    $strRoute=$strRoute.'</Routes>';
                    return $strRoute;
                }

            }

        }
        else
            return $status;
    }
    //echo "<sample>hhhh</sample>";
}









?>

