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
	$startDistance=$_GET['sourceOffset'];
	$endDistance=$_GET['destOffset'];
	$showOnlyIndirectRoutes=$_GET['onlyIndirectRoutes'];
	// find out the buses that pass through these stops
	$checkString=findDistanceBetweenSourceDestination($startStop,$endStop);
	$arr=array();
	$arr=split(":",$checkString);
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
		//echo $strRoute;
		
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
         $status= getJunctionsForIndirectBusesRevamp3($startStop,$endStop,$startBuses,$endBuses,$startDistance,$endDistance,$showOnlyIndirectRoutes);
			

		 /***
			need to add the xml extra tags like routes, route here. so if you need to add the depot information it could be just another section like
			Along with the status we should get the errorcode seperately. This will help in formign the xml
			if(errorcode==3)
			then
			else
			...


			 $strRoute1='<Routes>';
			 $strRoute1=$strRoute1.'<Route>';
			 $strRoute1=$strRoute1.'<IsDirectRoute>N</IsDirectRoute>';
			 $strRoute1=$strRoute1.'<ErrorCode>7</ErrorCode>';
			 
			 $strRoute1=$strRoute1."<RouteInfo>".$routeInfoString."</RouteInfo>";			 
			// this is the section that will be just replaced with what is returned from the status.. this can be a list of riutes
			 $strRoute1=$strRoute1."<Route>";
			 $routeDetail="<StartStop>".htmlentities($startStop)."</StartStop>";
			 $routeDetail=$routeDetail."<EndStop>".htmlentities($endStop)."</EndStop>";							 
			 $routeDetail=$routeDetail."<BusesStartStopAndDepot>".implode(",",$startStopStartDepotCommonBuses)."</BusesStartStopAndDepot>";
			 $routeDetail=$routeDetail."<BusesEndStopAndDepot>".implode(",",$startDepotEndStopCommonBuses)."</BusesEndStopAndDepot>";
			 $routeDetail=$routeDetail."<DistanceBetweenDepotAndStartStop>".$startStopStartDepotDistance."</DistanceBetweenDepotAndStartStop>";
			 $routeDetail=$routeDetail."<DistanceBetweenDepotAndEndStop>".$startDepotEndStopDistance."</DistanceBetweenDepotAndEndStop>";
			 $routeDetail=$routeDetail."<TotalRouteDistance>".$totalDistanceForCase1."</TotalRouteDistance>";
			 $routeDetail=$routeDetail."<UseDepot>1</UseDepot>";
			
			 $strRoute1=$strRoute1.'<RouteDetails>'.$routeDetail.'</RouteDetails>';
			 $strRoute1=$strRoute1.'</Route>';
			 
			 /////
			 $strRoute1=$strRoute1.'</Routes>';

		 **/
		
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
        if($status=="404" || $status=="405")
        {
          
               list($endStopDepotName,$endStopDepotDistance)=explode(":",getDepotName($endStop));
			   list($startStopDepotName,$startStopDepotDistance)=explode(":",getDepotName($startStop));
			  // echo "StartStop=>".$startStop.":".$startStopDepotName.",".$startStopDepotDistance."<br/>EndStop=>".$endStop.":".$endStopDepotName.",".$endStopDepotDistance;
			   $totalDistanceForCase1=10000;
			   $totalDistanceForCase2=10000;	  
			   			
				

				//i think first find out in which combination the common buses exist
				
				//there are few conditions that need to be tested for the depot
				//1. possible to have startStop->StartDepot->endpoint using Bus1, Bus2.
				// intension is that it is possible to take only one detour, starting from the start depot
				if($startStopDepotDistance < (float)7)
				{
					
					//get the direct buses between the startStop and depot
					
									
					//echo "first case";
					
					//find direct bus between start stop and start depot + distance
					$startStopStartDepotCommonBuses=getBusesCommonBetweenTwoStops($startStop,$startStopDepotName);
					if(sizeof($startStopStartDepotCommonBuses)>0)
					{
						//$directBusesBetweenStartStopAndStartDepot=getJunctionsForIndirectBusesRevamp3($startStop,$endStop,$startBuses,$endBuses,$startDistance,$endDistance//,0,1);
						 
						//$startDepotEndStopCommonBuses=getBusesCommonBetweenTwoStops($startStopDepotName,$endStop);
						

					
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

				
				if($totalDistanceForCase1>$totalDistanceForCase2)
				{
					echo $strRoute2;
				}
				else
				{
					echo $strRoute1;
				}

				// if both the start poit detour and the endpoint detour failed that means you dont have the buses either from the 
				// detour to the endpoint or between the start point and the detour
				if($totalDistanceForCase1==10000 && $totalDistanceForCase2 ==10000)
			  {
					//check for the multiple depot case
				
				//the thrid case will take into account other 2 cases 
				//. Possible to have startStop->StartDepot->EndDepot->endpoint using Bus1, Bus2, Bus3
				// or with the indirection startStop->StartDepot->JUNCTION->EndDepot->endpoint
				if($startStopDepotDistance < (float)7)
				{
				
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
					// echo "hi";
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
						// bring in the case 10 inside this which means do that in case the indirect distance could not be found.  and if not then error
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
						// bring in the case 10 inside this which means do that in case the indirect distance could not be found.  and if not then error
					}
					
				  }
				else
				  {
					$minimalDistanceOption7=10000;
					$minimalDistanceOption8=10000;
				  }

			}
			

			//echo "minimalDistanceOption7".$minimalDistanceOption7;
			//echo "minimalDistanceOption8".$minimalDistanceOption8;
			//echo "<br/>";
			if($minimalDistanceOption7==10000 && $minimalDistanceOption8==10000)			
			{
									 //the depots are too far away
					 $strRoute='<Routes>';
					 $strRoute=$strRoute.'<Route>';
					 $strRoute=$strRoute.'<IsDirectRoute>N</IsDirectRoute>';
					 $strRoute=$strRoute.'<ErrorCode>411</ErrorCode>';
					 $strRoute=$strRoute.'</Route>';
					 $strRoute=$strRoute.'</Routes>';
					// echo $strRoute;

			}
			else if($minimalDistanceOption7 < $minimalDistanceOption8 )
			{
				echo $strRoute3;
			}
			else
			{
				echo $strRoute4;

			}

				
				
				/*if($totalDistanceForCase1==10000 && $totalDistanceForCase2==10000)
				{
					 $strRoute='<Routes>';
					 $strRoute=$strRoute.'<Route>';
					 $strRoute=$strRoute.'<IsDirectRoute>N</IsDirectRoute>';
					 $strRoute=$strRoute.'<ErrorCode>404</ErrorCode>';
					 $strRoute=$strRoute.'</Route>';
					 $strRoute=$strRoute.'</Routes>';
					 echo $strRoute;
				}*/

				///neeed to check how the final message that nothing was found will be shown



				//the below ones are complicated anc will be too time consuming to calculate so may be taken later

				//3. possible to have startStop->StartDepot->Junction b/w StartDepot and Endpoint->endpoint using Bus1, Bus2, Bus3
				//{
					//find the direct bus between the start stop and start depot

					//find the junction between the start depot and end
				//}

				//4. possible to have startStop->Junction B/w StartStop & EndDepot->EndDepot->endpoint using Bus1, Bus2, Bus3

				//5. Possible to have startStop->StartDepot->EndDepot->endpoint using Bus1, Bus2, Bus3
				




			 /*
 			   echo $depotName.",".$depotDistance."<br/>";
			   
			  
				
					//get the buses from the original stop to the depot
                    //echo $depotName;
                    //finding of the depot buses
					$depotBuses=getBusesCommonBetweenTwoStops($depotName,$endStop);
					if(strlen(trim($depotBuses[0]))==0)
					{
					   $strRoute='<Routes>';
					   $strRoute=$strRoute.'<Route>';
					   $strRoute=$strRoute.'<IsDirectRoute>N</IsDirectRoute>';
					   $strRoute=$strRoute.'<ErrorCode>41</ErrorCode>';
					   $strRoute=$strRoute.'</Route>';
					   $strRoute=$strRoute.'</Routes>';
					   echo $strRoute;
					}
					else
					{
                         $depotBusesString=implode(",",$depotBuses);
						 $useDepot=$depotName."^".$depotDistance."^".$depotBusesString;
                         $startBuses=explode(",",getBusesForStopWithFrequency($depotName));
						 $status= getJunctionsForIndirectBusesRevamp2($depotName,$endStop,$startBuses,
                             $endBuses,$startDistance,$endDistance,$showOnlyIndirectRoutes,$useDepot);
					}*/

        }
        else
		{
         
		    $strRoute='<Routes>'.$status.'</Routes>';
			echo $strRoute;
		}



    }
		//echo "<sample>hhhh</sample>";
}

?>

