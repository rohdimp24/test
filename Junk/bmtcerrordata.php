<?php
/**
 * Created by PhpStorm.
 * User: fz015992
 * Date: 12/16/13
 * Time: 2:28 PM
 */

require_once "LIB_parse.php";


$fh=fopen("bmtcerrordata\\testBusroutesForError411.txt","r");
$count=0;
while(($str=fgets($fh))!=null)
{

  //  echo $str."<br/>";
    $startStop=return_between($str,"<StartStop>","</StartStop>",EXCL);
    $endStop=return_between($str,"<EndStop>","</EndStop>",EXCL);
    $depotErrorCode=return_between($str,"<ErrorCode>","</ErrorCode>",EXCL);
	
    if($depotErrorCode!="411")
    {
        if($depotErrorCode=="1")
        {
            echo $str;
        }
        else
        {
            $depotNameString=return_between($str,"<Depot>","</Depot>",EXCL);
            //need to further process the depot name string to find the depot name, buses and the lat/lon values
            $busesBetweenStopAndDepot=return_between($str,"<BusesBetweenStopAndDepot>","</BusesBetweenStopAndDepot>",EXCL);
            $distanceBetweenDepotAndStop=return_between($str,"<DistanceBetweenDepotAndStop>","</DistanceBetweenDepotAndStop>",EXCL);

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
			$numRoutes=0;
            //parse the routes
            foreach($routes as $route)
            {
				$numRoutes++;
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

        }

        /*

        $indirectRoute=parse_array($str,"<StartStop>","</StartStop>");
        $indirectRoute=parse_array($str,"<StartStop>","</StartStop>");
        $indirectRoute=parse_array($str,"<StartStop>","</StartStop>");
        $indirectRoute=parse_array($str,"<StartStop>","</StartStop>");
        $indirectRoute=parse_array($str,"<StartStop>","</StartStop>");
        $indirectRoute=parse_array($str,"<StartStop>","</StartStop>");
        $indirectRoute=parse_array($str,"<StartStop>","</StartStop>");
        $indirectRoute=parse_array($str,"<StartStop>","</StartStop>");
        $indirectRoute=parse_array($str,"<StartStop>","</StartStop>");
        */
    }
    else
    {
        echo "StartStop:".$startStop.","."EndStop:".$endStop.",depotErrorCode:".$depotErrorCode."n";
    }
	
	for($i=0;$i<$numRoutes;$i++)
	{
		if($i==0)
			echo "For "."StartStop:".$startStop.","."EndStop:".$endStop.",depotErrorCode:".$depotErrorCode."n";
		echo "Route#".$i;
		echo "Route Error".$indirectRouteErrorCodeArray[$i]."\t";
		echo "StartStop".$indirectStartStopsArray[$i]."\t";
		echo "StartBuses".$indirectStartBusesArray[$i]."\t";
		echo "FirstJunction".$indirectFirstJunctionsArray[$i]."\t";
		echo "DistanceBetweenJunction".$indirectDistanceBetweenJunctionArray[$i]."\t";
		echo "Second Junction".$indirectSecondJunctionsArray[$i]."\t";
		echo "End Stop".$indirectEndStopsArray[$i]."\t";		
		echo "End buses".$indirectEndBusesArray[$i]."\t";
		echo "TotalIndirectRouteDistcnae".$indirectTotalIndirectRouteDistanceArray[$i]."\t";
		echo "TotalRouteDistance".$indirectTotalRouteDistanceArray[$i]."\t";		
	}
    //echo $str;
   // echo "StartStop:".$startStop.","."EndStop:".$endStop.",depotErrorCode:".$depotErrorCode."n";
    echo "<hr/>";
    $count++;
   // if($count>10)
     //   exit();


}


function get_content( $tag , $content )
{
    preg_match("/<".$tag."[^>]*>(.*?)<\/$tag>/si", $content, $matches);
    return $matches[1];
}

