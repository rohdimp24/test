<?php
require_once 'loginDetailsV2.php';
require('GoogleMapAPI.class.php');
require_once('BusDataStructure.php');
require_once('IndirectBusStructure.php');
require_once('IndirectBusStructureWithFrequency.php');
require_once 'KLogger.php';
require_once 'DisplayJunctionsDataV2.php';
require_once 'DisplaySortedJunctionData.php';
//require('GoogleApiAdvancedClass.php');
//require('JSMin.php');
set_time_limit(60);
//$DEBUG=false;
$map = new GoogleMapAPI('map');
// setup database for geocode caching
// $map->setDSN('mysql://USER:PASS@localhost/GEOCODES');
// enter YOUR Google Map Key
//$map->setAPIKey('ABQIAAAA1c1sWAqiVfYVo2H2uZO3DRSWrvxHdeTKbGAggAmAoqEyMU0eFRSTrS7LnHnkyvA93YPmiuF_C-0r7Q');
// this is registered for mygann.com
// new key ABQIAAAA1c1sWAqiVfYVo2H2uZO3DRSdbZxIVjTSMKDiD-iCCeLYxJJn_BTfNn4DtNyckPujCTOcXysH3Glq9g
$map->setAPIKey('ABQIAAAA1c1sWAqiVfYVo2H2uZO3DRSdbZxIVjTSMKDiD-iCCeLYxJJn_BTfNn4DtNyckPujCTOcXysH3Glq9g');

$log = new KLogger ( "log.txt" , KLogger::DEBUG );
// connect to the database
$db_server = mysql_connect($db_hostname, $db_username, $db_password);
if (!$db_server) die("Unable to connect to MYSQL: " . mysql_error());

mysql_select_db($db_database)
    or die("Unable to select database: " . mysql_error());

//$DEBUG=true;

// increase the time out to infinity default is 60
set_time_limit(0);

/**
 * distance(): this function will calcualte the distance between the two points given the latitude and the longitude of the 2 points
 * the distance is bas4ed on the straight line and is not the driving distance
 **/
function distance($lat1, $lon1, $lat2, $lon2, $unit) {
   $lat1=doubleval($lat1);
    $lon1=doubleval($lon1);
    $lat2=doubleval($lat2);
    $lon2=doubleval($lon2);

    $theta = $lon1 - $lon2;
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    $dist = acos($dist);
    $dist = rad2deg($dist);
    $miles = $dist * 60 * 1.1515;
    $unit = strtoupper($unit);
    //echo $miles."...";
    if ($unit == "K") {
        return ($miles * 1.609344);
    } else if ($unit == "N") {
        return ($miles * 0.8684);
    } else {
        return $miles;
    }
}

/**
 * BubbleSort is the  function to sort the array on the basis of the distance paramters
 **/
function BubbleSort($SortArray,$array_size)
{

    for($x = 0; $x < $array_size; $x++) {
        for($y = 0; $y < $array_size; $y++) {
            if($SortArray[$x]->getDistance() < $SortArray[$y]->getDistance()) {
                $hold = $SortArray[$x];
                $SortArray[$x] = $SortArray[$y];
                $SortArray[$y] = $hold;
            }
        }
    }
    return $SortArray;
}

/**
 * getBusesForStop is useful to get the buses for the stop
 * I wamt that the BIAS and the G series bus to come at the last so if you do the ordering in the
 * desc order the buses starting with V will come first
 * the fucntion will return the names of the stops in the form of  a , seperated string
 * this is the reason why peope will not get the G series buses on the top. I think we need to put the frequency in place. Also because of the
 * simple order by all 500 series is getting listed before 3XX. We need to bring in the frequency thing
 **/
function getBusesForStop($stopName)
{
    // for each stop found also get the bus number .. also I want BIAS and G series to be considered last so order by
    $busQuery="Select BusNumber from newBusDetails where StopName='".$stopName."'ORDER BY BusNumber DESC";
    $busResult= mysql_query($busQuery);
    $busRowsnum = mysql_num_rows($busResult);
    $buses="";
    for($j=0;$j<$busRowsnum;$j++)
    {
        $busRow=mysql_fetch_row($busResult);
        $buses = $buses.$busRow[0].",";
    }
    return $buses;
    //echo $buses;
}


function getBusesForStopWithFrequency($stopName)
{
    // for each stop found also get the bus number .. also I want BIAS and G series to be considered last so order by
    // $busQuery="Select busnumber, frequency from busfrequency where busnumber in (Select BusNumber from newBusDetails where StopName='".$stopName."') order by frequency Desc";
    $busQuery="select busFrequency.BusNumber,Frequency from busFrequency,newBusDetails where StopName='".$stopName."'
    and busFrequency.BusNumber=newBusDetails.BusNumber order by Frequency desc";
    $busResult= mysql_query($busQuery);
    $busRowsnum = mysql_num_rows($busResult);
    $buses="";
    for($j=0;$j<$busRowsnum;$j++)
    {
        $busRow=mysql_fetch_row($busResult);
        $temp=$busRow[0].":".$busRow[1];
        if($j==$busRowsnum-1)
            $buses = $buses.$temp;
        else
            $buses = $buses.$temp.",";
    }
    return $buses;
    //echo $buses;
}




/**
 * getArrayBusesForStop is same as the getBusesForSTop. Only difference being that the stops are resturned in the form of an array
 **/
function getArrayBusesForStop($stopName)
{
    // for each stop found also get the bus number .. also I want BIAS and G series to be considered last so order by
    $busQuery="Select BusNumber from newBusDetails where StopName='".$stopName."'ORDER BY BusNumber DESC";
    $busResult= mysql_query($busQuery);
    $busRowsnum = mysql_num_rows($busResult);
    $buses=array();
    for($j=0;$j<$busRowsnum;$j++)
    {
        $busRow=mysql_fetch_row($busResult);
        array_push($buses,$busRow[0]);
    }
    return $buses;
    //echo $buses;
}

// function to trace the route...find out all the latitudes and longitudes of the stops inbetween the source and detination.
// this will help in drawing points
function traceRouteForBus($startStop,$endStop,$busNumber)
{
    $startStopNumber=getStopNumberOnBusRoute($startStop,$busNumber);
    $endStopNumber=getStopNumberOnBusRoute($endStop,$busNumber);

    //echo " the start stop is ".$startStop."and the number".$startStopNumber."<br/>";
    //echo " the start stop is ".$endStop."and the number".$endStopNumber."<br/>";
    if($startStopNumber<$endStopNumber)
    {
        $query="SELECT   newBusDetails.BusNumber, newBusDetails.StopName, newBusDetails.StopNumber, Stops.Latitude,
		Stops.Longitude FROM newBusDetails INNER JOIN Stops ON newBusDetails.StopName = Stops.StopName AND newBusDetails.BusNumber = '".$busNumber."'
		AND newBusDetails.StopNumber >= '".$startStopNumber."' AND newBusDetails.StopNumber <= '".$endStopNumber."' ORDER BY newBusDetails.StopNumber";
    }
    else
    {
        $query="SELECT   newBusDetails.BusNumber, newBusDetails.StopName, newBusDetails.StopNumber, Stops.Latitude,
		Stops.Longitude FROM newBusDetails INNER JOIN Stops ON newBusDetails.StopName = Stops.StopName AND newBusDetails.BusNumber = '".$busNumber."'
		 AND newBusDetails.StopNumber >= '".$endStopNumber."' AND newBusDetails.StopNumber <= '".$startStopNumber."' ORDER BY newBusDetails.StopNumber";
    }

    $result= mysql_query($query);
    $rowsnum = mysql_num_rows($result);

    $stopXMLString='<TraceRoutes>';
    for($i=0;$i<$rowsnum;$i++)
    {
        $row=mysql_fetch_row($result);
        //print_r($row);
        $stopXMLString=$stopXMLString.'<Route>';
        $stopXMLString=$stopXMLString.'<Latitude>'.$row[3].'</Latitude>';
        $stopXMLString=$stopXMLString.'<Longitude>'.$row[4].'</Longitude>';
        $stopXMLString=$stopXMLString.'<StopName>'.htmlentities($row[1]).'</StopName>';
        $stopXMLString=$stopXMLString.'</Route>';

    }

    $stopXMLString=$stopXMLString.'</TraceRoutes>';
    //echo $stopXMLString;
    return $stopXMLString;
}


// this will give you the position of the stop on the bus route
function getStopNumberOnBusRoute($stopName,$busNumber)
{
    $query="Select StopNumber From newBusDetails where StopName='".$stopName."' and BusNumber='".$busNumber."'";
    $result=mysql_query($query);
    $row=mysql_fetch_row($result);
    return $row[0];
}

/**
 * getNumberOfBusesForStop: This is the function to find out how many buses operate through a stop.
 * the number of buses passing through teh stop is one of the criteria of filtering the nearest stop for the user
 **/
function getNumberOfBusesForStop($stopName)
{
    // for each stop found also get the bus number .. also I want BIAS and G series to be considered last so order by
    $busQuery="Select BusNumber from newBusDetails where StopName='".$stopName."'ORDER BY BusNumber DESC";
    $busResult= mysql_query($busQuery);
    $busRowsnum = mysql_num_rows($busResult);
    return $busRowsnum;
}

/*
given the latitudee and longitude of the address, it will find out the bus stops
within the vicinity
*/
function getBusStopsGivenLatLong($addrLat,$addrLong)
{

    $stopXMLString='<BusStops>';
    $query="SELECT  StopName, Latitude, Longitude FROM Stops WHERE (Latitude - ".$addrLat." < .009) AND (Latitude - ".$addrLat." > - .009) AND (Longitude - ".$addrLong." > - .009) AND (Longitude - ".$addrLong." < 0.009)";
    //echo $query;
    $result= mysql_query($query);
    $rowsnum = mysql_num_rows($result);

    if($rowsnum==0)
    {
        // this is the case when you gave an address that even could not be found usign the internet search
        $stopXMLString=$stopXMLString."<Info>No Stop available in the vicinity</Info>";
        $stopXMLString=$stopXMLString."<ErrorCode>NA</ErrorCode>";
        $stopXMLString=$stopXMLString."<Stops>";
        $stopXMLString=$stopXMLString."<Stop>";
        $stopXMLString=$stopXMLString."</Stop>";
        $stopXMLString=$stopXMLString."</Stops>";

    }
    else
    {
        $arrBusStops= array();
        for($i=0;$i<$rowsnum;$i++)
        {
            $row=mysql_fetch_row($result);
            //echo $row[0];
            $dist=distance($addrLat, $addrLong, $row[1],$row[2], "K");
            $BDS= new BusDataStructure($row[0],$row[1],$row[2],$dist);
            array_push($arrBusStops,$BDS);

            //echo distance($addrLat, $addrLong, $row[1],$row[2], "K") . " kilometers<br>";
            //$map->addMarkerByCoords($row[2],$row[1],$row[0].distance($addrLat, $addrLong, $row[1],$row[2], "K"),'','');
        }

        // now do the comparison of the data
        $tempSortedArray=BubbleSort($arrBusStops,$rowsnum);
        // this is the place to do One more level of filtering based on the number of buses
        $sortedArray=applyFilterForAddress($tempSortedArray);
        //print_r($sortedArray);
        $stopXMLString=$stopXMLString."<Info>Address Found via Internet</Info>";
        $stopXMLString=$stopXMLString."<ErrorCode>IA</ErrorCode>";
        $stopXMLString=$stopXMLString."<Stops>";
        for($i=0;$i<sizeof($sortedArray);$i++)
        {
            //echo $sortedArray[$i]->getStopName().$sortedArray[$i]->getDistance()."<br/>";
            $stopXMLString=$stopXMLString."<Stop>";	//$map->addMarkerByCoords($sortedArray[$i]->getLongitude(),$sortedArray[$i]->getLatitude(),$sortedArray[$i]->getStopName().$sortedArray[$i]->getDistance(),getBusesForStop($sortedArray[$i]->getStopName()),'');
            $stopXMLString=$stopXMLString."<Name>".htmlentities($sortedArray[$i]->getStopName())."</Name>";
            $stopXMLString=$stopXMLString."<Latitude>".$sortedArray[$i]->getLatitude()."</Latitude>";
            $stopXMLString=$stopXMLString."<Longitude>".$sortedArray[$i]->getLongitude()."</Longitude>";
            $stopXMLString=$stopXMLString."<Distance>".$sortedArray[$i]->getDistance()."</Distance>";
            $stopXMLString=$stopXMLString."<Buses>".getBusesForStop($sortedArray[$i]->getStopName())."</Buses>";
            $stopXMLString=$stopXMLString."</Stop>";
        }
        $stopXMLString=$stopXMLString."</Stops>";
        $stopDataString=$sortedArray[0]->getStopName().":".$sortedArray[0]->getLatitude().":".$sortedArray[0]->getLongitude().":".$sortedArray[0]->getDistance();
    }


    $stopXMLString=$stopXMLString.'</BusStops>';
    return $stopXMLString;
}






/**
 * getStopForAddress: This function will take the address in free string as the input and
 * will try to resolve that address to recognized stops.
 * There are 3 places where the search is made
1. the search is made in the stops database whihc is the collection of all the valid stops
2. Next the search is made in the roadaddresses which is the database for the valid roads. we have identified a prominenet Latitude/long information
and we try to find the nearest stop to that lat/long based on the stop database
3. Finally we go to the internet using the google api to find the address. 
TODO: We need to enter the address in the road address database so that next time we can save on the lookup time
 **/
/**
The xml should be
<Address>
<Value></Value>
<Info></Info>
<Stops>
<Stop>
<Name></Name>
<Latitude></Latitude>
<Longitude></Longitude>
<Distance></Distance>
<Buses></Buses>
</Stop>
.
.
</Stops>
<Result>
<BusStop></BusStop>
<BusStopLatitude></BusStopLatitude>
<BusStopLongitude></BusStopLongitude>
<Offset></Offset>
</Result>
</Address>
 **/
function getStopForAddress($addr,$log,$mediaType)
{

    //echo $addr;
    $stopDataString='';
    $originalRequestedLatitude=0;
    $originalRequestedLongitude=0;

    // create a xml string
    $stopXMLString='<Address>';
    $query="Select StopName, Latitude, Longitude from Stops Where StopName = '".$addr."'";
    $result= mysql_query($query);
    $rowsnum = mysql_num_rows($result);
    //find from the stop table..more than one stop found
//	if($rowsnum>1)
//	{
//		if($DEBUG)
//			echo "more than one stops are found with your search criteria <br/>";
//		$log->LogDebug("[".$mediaType."] The address ".$addr." matches more than one in stops table. taking the first one ");
//		$stopXMLString=$stopXMLString."<Info>More than one stops are found with your search criteria</Info>";
//		$stopXMLString=$stopXMLString."<ErrorCode>MS</ErrorCode>";
//		$stopXMLString=$stopXMLString."<Stops>";
//		// this is the case wehre we can check which is the best choice in terms of buses coming thourgh..E.g. Marathahalli
//		$maxBus=0;
//		for($i=0;$i<$rowsnum;$i++)
//		{
//			$row=mysql_fetch_row($result);
//			$stopXMLString=$stopXMLString."<Stop>";
//		   if($DEBUG)
//			 echo $row[0]."<br/>";
//
//			$stopXMLString=$stopXMLString."<Name>".htmlentities($row[0])."</Name>";
//			$stopXMLString=$stopXMLString."<Latitude>".$row[1]."</Latitude>";
//			$stopXMLString=$stopXMLString."<Longitude>".$row[2]."</Longitude>";
//			$stopXMLString=$stopXMLString."<Distance>-1</Distance>";
//			$stopXMLString=$stopXMLString."<Buses>".getBusesForStop($row[0])."</Buses>";
//
//			// $stopDetails=$row[0];
//			//$map->addMarkerByCoords($row[2],$row[1],$row[0],getBusesForStop($row[0]),'');
//			$NumberOfBus=getNumberOfBusesForStop($row[0]);
//			if($NumberOfBus>$maxBus)
//			{
//				$maxBus=$NumberOfBus;
//				$stopDataString=$row[0].":".$row[1].":".$row[2].":0";
//			}
//			$stopXMLString=$stopXMLString."</Stop>";
//		}
//		$stopXMLString=$stopXMLString."</Stops>";
//	}
//	else //only one stop is found
    // if the stop is found
    if($rowsnum==1)
    {
        $stopXMLString=$stopXMLString."<Info>Your address matches the correct stop name</Info>";
        $stopXMLString=$stopXMLString."<ErrorCode>OS</ErrorCode>";
        $stopXMLString=$stopXMLString."<Stops>";
        $stopXMLString=$stopXMLString."<Stop>";
        $row=mysql_fetch_row($result);
//        if($DEBUG)
//            echo " Your stop is ".$row[0]."<br/>";
        $log->LogDebug("[".$mediaType."] The address ".$addr." is a valid stopname ");
        $stopXMLString=$stopXMLString."<Name>".htmlentities($row[0])."</Name>";
        $stopXMLString=$stopXMLString."<Latitude>".$row[1]."</Latitude>";
        $stopXMLString=$stopXMLString."<Longitude>".$row[2]."</Longitude>";
        $stopXMLString=$stopXMLString."<Distance>-1</Distance>";
        $stopXMLString=$stopXMLString."<Buses>".getBusesForStop($row[0])."</Buses>";
        $stopXMLString=$stopXMLString."</Stop>";
        // $map->addMarkerByCoords($row[2],$row[1],$row[0],getBusesForStop($row[0]),'');
        $stopDataString=$row[0].":".$row[1].":".$row[2].":0";
        $stopXMLString=$stopXMLString."</Stops>";
    }
    else //no stop found..user must have entered some free string
        if($rowsnum==0)
        {
            //First check in the road database if the address meets
            //echo "going to the roads database"."<br/>";
            // Checking in the road database
            $query="Select * from newRoadAddresses Where Address ='".$addr."'";
            $result= mysql_query($query);
            $rowsnum = mysql_num_rows($result);
            //the address was not found in the road database so get it from internet
            if($rowsnum==0)
            {
//                if($DEBUG)
//                    echo "The address not found in the database so fetching from internet <br/>";
                //$stopXMLString=$stopXMLString."<Info>The address ".$addr." not found in the database so fetching from internet</Info>";
                $logString="[".$mediaType."] The address ".$addr." not found in the database so fetching from internet ";
                //$log->LogDebug("[".$mediaType."] The address ".$addr." not found in the database so fetching from internet ");
                // fimd from the net
                $addressString="http://maps.google.com/maps/api/geocode/json?address=".urlencode($addr).",+Bangalore,+Karnataka,+India&sensor=false";
                $geocode=file_get_contents($addressString);
                $formattedAddress=return_between($geocode,"formatted_address","\",",EXCL);
                $formattedAddressNoiseRemoved=return_between($formattedAddress,":","India",INCL);
                $place=substr($formattedAddressNoiseRemoved,3);
                //echo $place."<br/>";
                //exit(0);
                ///echo $place."<br/>";
                //$arr=array();

                $arr=explode(",",$place);
                //the address was not at all valid
                if(strcmp(trim($arr[0]),"Bangalore")==0 && strcmp(trim($arr[1]),"Karnataka")==0 && strcmp(trim($arr[2]),"India")==0)
                {
                    $log->LogDebug($logString.". Invalid Address provided ");
                    $stopXMLString=$stopXMLString."<Info>Address is not valid. Try giving some locality name</Info>";
                    $stopXMLString=$stopXMLString."<ErrorCode>AM</ErrorCode>";
                    $stopXMLString=$stopXMLString."<Stops>";
                    $stopXMLString=$stopXMLString."<Stop>";
                    $stopXMLString=$stopXMLString."</Stop>";
                    $stopXMLString=$stopXMLString."</Stops>";
//                    if($DEBUG)
//                        echo "<h3>".trim($addr)."  Address is not valid"."</h3><br/>";
                    $stopDataString="0:0:0:0";
                }
                else // the address was valid and need to extract the location
                {

                    $locations=return_between($geocode,"location","}",EXCL);
                    //echo $locations;
                    $latitude=return_between($locations,"lat\"",",",EXCL);
                    $latitude=substr($latitude,3);
                    //echo $latitude;

                    list($lat,$long)=explode(",",$locations);

                    list($cap,$val)=explode(":",$long);
                    //echo trim($val);

                    $addrLat=trim($latitude);
                    $addrLong=trim($val);

                    $originalRequestedLatitude=$addrLat;
                    $originalRequestedLongitude=$addrLong;

                    // add the marker for the searched address
                    //$map->addMarkerIcon("marker_star.png","marker_shadow.png",15,29,15,3);
                    //$map->addMarkerByCoords($addrLong,$addrLat,$addr,'','');
                    //Now check that we have any stop that is within 1 km of the stop.
                    //we need to use the direct method to calculate the distance
                    // we could have used the heversine formaula also but this is OK
                    $query="SELECT  StopName, Latitude, Longitude FROM Stops WHERE (Latitude - ".$addrLat." < .009) AND (Latitude - ".$addrLat." > - .009) AND (Longitude - ".$addrLong." > - .009) AND (Longitude - ".$addrLong." < 0.009)";

                    //echo $query."<br/>";

                    $result= mysql_query($query);
                    $rowsnum = mysql_num_rows($result);
                    //we could not find any stops near to this address.
                    if($rowsnum==0)
                    {
                        // this is the case when you gave an address that even could not be found usign the internet search
                        $log->LogDebug($logString.". No Stops nearby");
                        $stopXMLString=$stopXMLString."<Info>Sorry we could not find any stops nearby</Info>";
                        $stopXMLString=$stopXMLString."<ErrorCode>AM</ErrorCode>";
                        $stopXMLString=$stopXMLString."<Stops>";
                        $stopXMLString=$stopXMLString."<Stop>";
                        $stopXMLString=$stopXMLString."</Stop>";
                        $stopXMLString=$stopXMLString."</Stops>";
//                        if($DEBUG)
//                            echo "<h3>".trim($addr)."  Address is not valid"."</h3><br/>";
                        $stopDataString="0:0:0:0";

                    }
                    else //we have found the stops. a collection of stops in that area
                    {
                        $arrBusStops= array();
                        for($i=0;$i<$rowsnum;$i++)
                        {
                            $row=mysql_fetch_row($result);
                            //echo $row[0];
                            $dist=distance($addrLat, $addrLong, $row[1],$row[2], "K");
                            $BDS= new BusDataStructure($row[0],$row[1],$row[2],$dist);
                            array_push($arrBusStops,$BDS);

                            //echo distance($addrLat, $addrLong, $row[1],$row[2], "K") . " kilometers<br>";
                            //$map->addMarkerByCoords($row[2],$row[1],$row[0].distance($addrLat, $addrLong, $row[1],$row[2], "K"),'','');
                        }

                        // now do the comparison of the data
                        //sort the stops in the order of some preference
                        $tempSortedArray=BubbleSort($arrBusStops,$rowsnum);
                        // this is the place to do One more level of filtering based on the number of buses
                        $sortedArray=applyFilterForAddress($tempSortedArray);
                        //print_r($sortedArray);
                        $log->LogDebug($logString.". Valid Address found via internet");
                        $stopXMLString=$stopXMLString."<Info>Address Found via Internet</Info>";
                        $formattedPlace=preg_replace('/Bangalore, Karnataka, India$/', '', $place);
                        $formattedPlaceRemovingTrailingComma=rtrim(trim($formattedPlace), ",");
                        $stopXMLString=$stopXMLString."<InternetAddress>".$formattedPlaceRemovingTrailingComma."</InternetAddress>";
                        $stopXMLString=$stopXMLString."<ErrorCode>IA</ErrorCode>";
                        $stopXMLString=$stopXMLString."<Stops>";
                        for($i=0;$i<sizeof($sortedArray);$i++)
                        {
                            //echo $sortedArray[$i]->getStopName().$sortedArray[$i]->getDistance()."<br/>";
                            $stopXMLString=$stopXMLString."<Stop>";	//$map->addMarkerByCoords($sortedArray[$i]->getLongitude(),$sortedArray[$i]->getLatitude(),$sortedArray[$i]->getStopName().$sortedArray[$i]->getDistance(),getBusesForStop($sortedArray[$i]->getStopName()),'');
                            $stopXMLString=$stopXMLString."<Name>".htmlentities($sortedArray[$i]->getStopName())."</Name>";
                            $stopXMLString=$stopXMLString."<Latitude>".$sortedArray[$i]->getLatitude()."</Latitude>";
                            $stopXMLString=$stopXMLString."<Longitude>".$sortedArray[$i]->getLongitude()."</Longitude>";
                            $stopXMLString=$stopXMLString."<Distance>".$sortedArray[$i]->getDistance()."</Distance>";
                            $stopXMLString=$stopXMLString."<Buses>".getBusesForStop($sortedArray[$i]->getStopName())."</Buses>";
                            $stopXMLString=$stopXMLString."</Stop>";
                        }
                        $stopXMLString=$stopXMLString."</Stops>";
                        $stopDataString=$sortedArray[0]->getStopName().":".$sortedArray[0]->getLatitude().":".$sortedArray[0]->getLongitude().":".$sortedArray[0]->getDistance();
                    }

                }

            }
            //we found some addresses but more than one was matching. We need some more specific on that
            // basically more than one road adress match
            // this should not be a case ...should be an error if the address reach here
            // I think we need to add the log statement here
            else if($rowsnum>1)
            {
                $stopXMLString=$stopXMLString."<Info>More than one matching address found in road database. Kindly add some more specifics</Info>";
                $stopXMLString=$stopXMLString."<ErrorCode>MA</ErrorCode>";
                $stopXMLString=$stopXMLString."<Stops>";
                $stopXMLString=$stopXMLString."<Stop>";
                $stopXMLString=$stopXMLString."</Stop>";
                $stopXMLString=$stopXMLString."</Stops>";
//                if($DEBUG)
//                    echo "More than one matching address found in road database. Kindly add some more specifics";
                $stopDataString="m:m:m:m";

            }
            else // the address is found in the road database
            {
//                if($DEBUG)
//                    echo "Found in the road database"."<br/>";

                // we found the address in th road data base but unfortunately all the stops are more than KM .
                // this issue will not be there after we have the pegging of the stops.
                // in the first sql itself we will know the stop that is meant for this road address
                $log->LogDebug("[".$mediaType."] The address ".$addr." found in the road database ");
                $row=mysql_fetch_row($result);
                $addrLat=$row[3];
                $addrLong=$row[4];
                $stopNumber=$row[5];
                $stopName=$row[6];
                $vicinityDistance=$row[7];
                $originalRequestedLatitude=$addrLat;
                $originalRequestedLongitude=$addrLong;

                //get the latutude/logitude of the stop
                $sqlStop="Select * from Stops where SNo='".$stopNumber."'";
                $resultStop=mysql_query($sqlStop);
                $rowStop=mysql_fetch_row($resultStop);
                $stopLat=$rowStop[2];
                $stopLon=$rowStop[3];


                $stopXMLString=$stopXMLString."<Info>Address Found in the Road Database</Info>";
                $stopXMLString=$stopXMLString."<ErrorCode>RA</ErrorCode>";
                $stopXMLString=$stopXMLString."<Stops>";
                $stopXMLString=$stopXMLString."<Stop>";	//$map->addMarkerByCoords($sortedArray[$i]->getLongitude(),$sortedArray[$i]->getLatitude(),$sortedArray[$i]->getStopName().$sortedArray[$i]->getDistance(),getBusesForStop($sortedArray[$i]->getStopName()),'');
                $stopXMLString=$stopXMLString."<Name>".htmlentities($stopName)."</Name>";
                $stopXMLString=$stopXMLString."<Latitude>".$stopLat."</Latitude>";
                $stopXMLString=$stopXMLString."<Longitude>".$stopLon."</Longitude>";
                $stopXMLString=$stopXMLString."<Distance>".$vicinityDistance."</Distance>";
                $stopXMLString=$stopXMLString."<Buses>".getBusesForStop($stopName)."</Buses>";
                $stopXMLString=$stopXMLString."</Stop>";
                $stopXMLString=$stopXMLString."</Stops>";

                $stopDataString=$stopName.":".$stopLat.":".$stopLon.":".$vicinityDistance;

                //echo $addrLat;
                //$map->addMarkerByCoords($addrLong,$addrLat,$addr,'','');
                /*$query="SELECT  StopName, Latitude, Longitude FROM Stops WHERE (Latitude - ".$addrLat." < .009) AND (Latitude - ".$addrLat." > - .009) AND (Longitude - ".$addrLong." > - .009) AND (Longitude - ".$addrLong." < 0.009)";
                //echo $query."<br/>";
                $result= mysql_query($query);
                $rowsnum = mysql_num_rows($result);
                if($rowsnum==0)
                {
                    $stopXMLString=$stopXMLString."<Info>All stops are more then 1 km far</Info>";
                    $stopXMLString=$stopXMLString."<ErrorCode>KM</ErrorCode>";
                    $stopXMLString=$stopXMLString."<Stops>";
                    $stopXMLString=$stopXMLString."<Stop>";
                    $stopXMLString=$stopXMLString."</Stop>";
                    $stopXMLString=$stopXMLString."</Stops>";
                    //echo "All stops are more then 1 km far";
                }
                else
                {
                    // we should try to show nearest 5 at the max
                    // one way is to create a array of datastructure and then sort them on the basis of distance.
                    // also find the buses for the finalised stops
                    $arrBusStops= array();
                    $stopXMLString=$stopXMLString."<Info>Address Found in the Road Database</Info>";
                    $stopXMLString=$stopXMLString."<ErrorCode>RA</ErrorCode>";
                    for($i=0;$i<$rowsnum;$i++)
                    {
                        $row=mysql_fetch_row($result);
                        $dist=distance($addrLat, $addrLong, $row[1],$row[2], "K");
                        $BDS= new BusDataStructure($row[0],$row[1],$row[2],$dist);
                        array_push($arrBusStops,$BDS);

                    }
                    // now do the comparison of the data
                    $tempSortedArray=BubbleSort($arrBusStops,$rowsnum);
                    // this is the place to do One more level of filtering based on the number of buses
                    $sortedArray=applyFilterForAddress($tempSortedArray);
                    //print_r($sortedArray);
                    $stopXMLString=$stopXMLString."<Stops>";
                    for($i=0;$i<sizeof($sortedArray);$i++)
                    {
                        //echo $sortedArray[$i]->getStopName().$sortedArray[$i]->getDistance()."<br/>";
                    $stopXMLString=$stopXMLString."<Stop>";	//$map->addMarkerByCoords($sortedArray[$i]->getLongitude(),$sortedArray[$i]->getLatitude(),$sortedArray[$i]->getStopName().$sortedArray[$i]->getDistance(),getBusesForStop($sortedArray[$i]->getStopName()),'');
                    $stopXMLString=$stopXMLString."<Name>".htmlentities($sortedArray[$i]->getStopName())."</Name>";
                    $stopXMLString=$stopXMLString."<Latitude>".$sortedArray[$i]->getLatitude()."</Latitude>";
                    $stopXMLString=$stopXMLString."<Longitude>".$sortedArray[$i]->getLongitude()."</Longitude>";
                    $stopXMLString=$stopXMLString."<Distance>".$sortedArray[$i]->getDistance()."</Distance>";
                    $stopXMLString=$stopXMLString."<Buses>".getBusesForStop($sortedArray[$i]->getStopName())."</Buses>";
                    $stopXMLString=$stopXMLString."</Stop>";

                    }
                    $stopXMLString=$stopXMLString."</Stops>";
                    $stopDataString=$sortedArray[0]->getStopName().":".$sortedArray[0]->getLatitude().":".$sortedArray[0]->getLongitude().":".$sortedArray[0]->getDistance();

                }*/

            }
        }



    $stopXMLString=$stopXMLString."<Result>";
    list($BusStop,$BusLatitude,$BusLongitude,$BusOffset)=explode(":",$stopDataString);
    $stopXMLString=$stopXMLString."<BusStop>".htmlentities($BusStop)."</BusStop>";
    $stopXMLString=$stopXMLString."<BusStopLatitude>".$BusLatitude."</BusStopLatitude>";
    $stopXMLString=$stopXMLString."<BusStopLongitude>".$BusLongitude."</BusStopLongitude>";
    $stopXMLString=$stopXMLString."<Offset>".$BusOffset."</Offset>";
    $stopXMLString=$stopXMLString."<Buses>".getBusesForStopWithFrequency($BusStop)."</Buses>";
    $stopXMLString=$stopXMLString."<OriginalStopLatitude>".$originalRequestedLatitude."</OriginalStopLatitude>";
    $stopXMLString=$stopXMLString."<OriginalStopLongitude>".$originalRequestedLongitude."</OriginalStopLongitude>";
    $stopXMLString=$stopXMLString."</Result>";
    $stopXMLString=$stopXMLString."</Address>";

    return $stopXMLString;
}


//for testing //http://localhost/bmtcroutes/GetBusRouteDetails.php?sourceStopName=Bellandur%20Gate%20(ORR)&destStopName=Marathahalli%20Bridge&sourceOffset=0&destOffset=0&onlyIndirectRout//es=0

function sortBasedOnBusFrequency($SortArray)
{
    //print_r($SortArray);//bugtest
    $array_size=sizeof($SortArray);
    //$arrFrequency=getAllBusFrequency();
    for($x = 0; $x < $array_size; $x++)
    {
        for($y = 0; $y < $array_size; $y++)
        {
            if(getBusFrequencyScore($SortArray[$x]) > getBusFrequencyScore($SortArray[$y]))
            {
                $hold = $SortArray[$x];
                $SortArray[$x] = $SortArray[$y];
                $SortArray[$y] = $hold;
            }
        }
    }
    //echo "After sorting";
    //print_r($SortArray);
    return $SortArray;

}


/**
 * The sort function will use the collective frequency value from the indirectBusStructureWithFrequency to sort the
 * indirect buses
 * The collective frequency can represent the summation of the bus frequency or junction frequency
 * @param $SortArray
 * @return mixed
 */
function sortBasedOnCollectiveFrequency($SortArray)
{
    $array_size=sizeof($SortArray);
    //$arrFrequency=getAllBusFrequency();
    for($x = 0; $x < $array_size; $x++)
    {
        for($y = 0; $y < $array_size; $y++)
        {
            if($SortArray[$x]->getCollectiveFrequency() > $SortArray[$y]->getCollectiveFrequency())
            {
                $hold = $SortArray[$x];
                $SortArray[$x] = $SortArray[$y];
                $SortArray[$y] = $hold;
            }
        }
    }
    return $SortArray;

}

/*
 *
 * I think this code needs to be changed as the collective frequency is no longer valid..29/09/2013
 */

function sortBasedOnJunctionFrequency($SortArray)
{
    $array_size=sizeof($SortArray);
    //$arrFrequency=getAllBusFrequency();
    for($x = 0; $x < $array_size; $x++)
    {
        for($y = 0; $y < $array_size; $y++)
        {

            //if($SortArray[$x]->getCollectiveFrequency() > $SortArray[$y]->getCollectiveFrequency())
            //need to do this only if the junction frequency is high
            if($SortArray[$x]->getFirstJunctionFrequency()>5 && $SortArray[$x]->getSecondJunctionFrequency()>5)
            {
                $totDistanceDiff=$SortArray[$x]->getTotalRouteDistance()-$SortArray[$y]->getTotalRouteDistance();
                /*if(((float)$totDistanceDiff > -2 && (float)$totDistanceDiff < 2 ) &&
                    ($SortArray[$x]->getJunctionDistance() < $SortArray[$y]->getJunctionDistance()))*/
                if((float)$totDistanceDiff > -2 && (float)$totDistanceDiff < 2 )
                {
                    $hold = $SortArray[$x];
                    $SortArray[$x] = $SortArray[$y];
                    $SortArray[$y] = $hold;
                }
            }
        }
    }
    return $SortArray;

}






/**
 * Based on thedistance between the junctions sort the indirect arracy in ascending order
 * @param $SortArray
 * @return mixed
 */
function sortBasedOnJunctionDistances($SortArray)
{
    $array_size=sizeof($SortArray);
    //$arrFrequency=getAllBusFrequency();
    for($x = 0; $x < $array_size; $x++)
    {
        for($y = 0; $y < $array_size; $y++)
        {
            if($SortArray[$x]->getDistanceBetweenJunctions() < $SortArray[$y]->getDistanceBetweenJunctions())
            {
                $hold = $SortArray[$x];
                $SortArray[$x] = $SortArray[$y];
                $SortArray[$y] = $hold;
            }
        }
    }
    return $SortArray;

}



/**
 * Based on the total route distance
 * @param $SortArray
 * @return mixed
 */
function sortBasedOnTotalRouteDistance($SortArray)
{
    $array_size=sizeof($SortArray);
    //$arrFrequency=getAllBusFrequency();
    for($x = 0; $x < $array_size; $x++)
    {
        for($y = 0; $y < $array_size; $y++)
        {
            if($SortArray[$x]->getTotalRouteDistance() < $SortArray[$y]->getTotalRouteDistance())
            {
                $hold = $SortArray[$x];
                $SortArray[$x] = $SortArray[$y];
                $SortArray[$y] = $hold;
            }
        }
    }
    return $SortArray;

}




// get the frequency on the basis of the frequencies of the two buses
function getBusFrequencyScore($busData)
{
    /**
    The below code was returning wrong or less number of routes compared to the one that I tested on the
     * desktop like Shabari Nagar Cross to Electronic City (Wipro Gate)
     */

    //echo $busData."<br/>";
    /*list($bus1,$freq1,$bus2,$freq2)=explode(":",$busData);
    $freq=$freq1+$freq2;
    echo "freq=".$freq;
    return $freq1+$freq2;
    */

    //copied from the desktop verions
    $arrData=explode(":",$busData);
    if(sizeof($arrData)>2)
        return $arrData[1]+$arrData[3];
    else
        return $arrData[1];

}

function getBusFrequencyScoreOld($arrFrequency,$busData)
{
    list($bus1,$bus2)=explode(":",$busData);
    return $arrFrequency[trim($bus1)]+$arrFrequency[trim($bus2)];

}

function getAllBusFrequency()
{
    $query="Select BusNumber,Frequency from busFrequency";
    $result=mysql_query($query);
    $rowsnum=mysql_num_rows($result);
    // need to create an associative array that can be easily queried
    $arrFrequency=array();
    for($j=0;$j<$rowsnum;$j++)
    {
        $row=mysql_fetch_row($result);
        $arrFrequency[$row[0]]=$row[1];

    }

    return $arrFrequency;
}



/**
 *applyFilterForAddress:function to re sort the staions on the basis of the number of the stops and the distance from the
actual address
 * this is the function that will decide on the order of the stops

check the number of the buses. If the number of buses in the first stop is >5 then we can return the array without doing any check
else from the array try to find the stop whihc is the minimal distant and has more than 3 buses.
We may have to change it again.

 */
function applyFilterForAddress($arrAddress)
{
    //print_r($arrAddress);

    if(getNumberOfBusesForStop($arrAddress[0]->getStopName())>5)
        return $arrAddress;

    $array_size=sizeof($arrAddress);
    //echo "arraySize".$array_size;
    for($x = 0; $x < $array_size; $x++)
    {
        for($y = 0; $y < $array_size; $y++)
        {
            //	echo "sdkhkshdkhskhds".getNumberOfBusesForStop($arrAddress[$x]->getStopName())."<br/>";
            if((getNumberOfBusesForStop($arrAddress[$x]->getStopName()) > getNumberOfBusesForStop($arrAddress[$y]->getStopName())) &&
                ($arrAddress[$x]->getDistance()<0.75))
            {
                //echo "dsdks";
                $hold = $arrAddress[$x];
                $arrAddress[$x] = $arrAddress[$y];
                $arrAddress[$y] = $hold;
            }
        }
    }


    //



    /*for($i=0;$i<sizeof($arrAddress);$i++)
    {
        $stopName=$arrAddress[$i]->getStopName();
        // find the number of buses
        sizeof(getBusesForStop($row[0]));
    }*/
    //print_r($arrAddress);
    return $arrAddress;
}




//If the database is in place then the call should be
function getBusRoutesForRendering($log,$startStop,$endStop,$startOffsetDistance,$endOffsetDistance,$showOnlyIndirectRoutes)
{
    //go to the database
    //find the route information which is error code, depot name1,depot name2, indirect route, startdepotdistance, endDepotDistance,junction1, junction2
    //routetype : direct or indirect

    //construct a route string and return to the caling function.

    //ifshowonlyINdirectBuses then find only the indirect route else try to find the direct first, if not then indirect

    //return the xml.

    $startBuses=explode(",",getBusesForStopWithFrequency($startStop));
    $endBuses=explode(",",getBusesForStopWithFrequency($endStop));

    //print_r($startBuses);
    //print_r($endBuses);

    // for now pick the first entry as the bus number but we need to do some optimization on choosing the buses based on their frequency, the stop number etc

    //$arrCommonBuses=getCommonBuses($startBuses,$endBuses,$showOnlyIndirectRoutes);
    // print_r($arrCommonBuses);

    //echo getJunctionsForIndirectBuses($arrCommonBuses,$startStop,$endStop,$startDistance,$endDistance);
    //I guess pass the log in this function also to print the log message
    $status= getJunctionsForIndirectBusesRevamp2($log,$startStop,$endStop,$startBuses,$endBuses,$startOffsetDistance,$endOffsetDistance,$showOnlyIndirectRoutes);
	
	           
	
    if($status=="404" || $status=="405")
    {
        $arrRows=array();
        $arrDirectRows=array();
        //I am assuming that for the given source and end there is only one type of depot error code. So it can be either 7,8,9,10 but not both
        //check the database for the error code and output
        $sql="Select * from depotBusRoutes where SourceStartStop='".$startStop."' and DestinationEndStop='".$endStop."'";
        $result=mysql_query($sql);
        $rowsnum=mysql_num_rows($result);
        if($rowsnum==0)
        {
            //check in the other database if the data is present over there
            $sqlDirect="Select * from directDepotBusRoutes where StartStop='".$startStop."' and EndStop='".$endStop."'";
            $resultDirect=mysql_query($sqlDirect);
            $rowsnumDirect=mysql_num_rows($resultDirect);
            if($rowsnumDirect==0)
                return exceptionalCondition($startStop,$endStop);
            else
            {
                //this will take care of 6,7,8
                //echo "Total Routes".$rowsnum;
                for($i=0;$i<$rowsnumDirect;$i++)
                {
                    $rowDirect=mysql_fetch_object($resultDirect);
                    //            print_r($row)."<br/>";
                    if($rowDirect->DepotErrorCode=="411")
                    {
                        $strRoute='<Routes>';
                        $strRoute=$strRoute.'<Route>';
                        $strRoute=$strRoute.'<IsDirectRoute>N</IsDirectRoute>';
                        $strRoute=$strRoute.'<ErrorCode>411</ErrorCode>';
                        $strRoute=$strRoute.'<StartStop>'.$startStop.'</StartStop>';
                        $strRoute=$strRoute.'<EndStop>'.$endStop.'</EndStop>';
                        $strRoute=$strRoute.'</Route>';
                        $strRoute=$strRoute.'</Routes>';
                       // $log->LogDebug("[".$mediaType."] The result status is 411 between ".$startStop." and ".$endStop);
    
                        return $strRoute;

                    }
                    else if($rowDirect->DepotErrorCode=="6"||$rowDirect->DepotErrorCode=="7"||$rowDirect->DepotErrorCode=="8")
                    {
                    	//$log->LogDebug("[".$mediaType."] The result status is ".$rowDirect->DepotErrorCode." between ".$startStop." and ".$endStop);
    
                        array_push($arrDirectRows,$rowDirect);
                       // print_r($arrDirectRows);

                    }
                    else
                    {
                        echo "something is really wrong 678";
                    }

                }
                //add the print

                //this is the case which will come if somehow the errorcode1 is missed as it was happening before. the errorcode 6 is
                //same as errorcode 1 but these cases are normally getting missed out initially
                if($arrDirectRows[0]->DepotErrorCode=="6")
                {
                    //print_r($arrDirectRows);
                    //basically this is a special case of 1. With the addition that it is not getting caught in the
                    //revamp2 function
                    $routeString='';
                    $routeString.="<Routes>";
                    for($i=0;$i<sizeof($arrDirectRows);$i++)
                    {

                        $routeString .='<Route>';
                        $routeString .='<IsDirectRoute>N</IsDirectRoute>';
                        $routeString.= "<ErrorCode>".$arrDirectRows[$i]->DepotErrorCode."</ErrorCode>";
                        $routeString.="<RouteDetails>";
                        $routeString.="<StartStop>".$arrDirectRows[$i]->StartStop."</StartStop>";
                        if($arrDirectRows[$i]->DistanceBetweenDepotAndStartStop<.5)
                        {
                            $routeString.="<StartBuses>WD</StartBuses>";
                        }
                        else
                        {
                            $routeString.="<StartBuses>".$arrDirectRows[$i]->BusesBetweenStartStopAndDepot."</StartBuses>";
                        }
                        $routeString.="<FirstJunction>".$arrDirectRows[$i]->DepotNameString."</FirstJunction>";
                        $routeString.="<DistanceBetweenJunction>0</DistanceBetweenJunction>";
                        $routeString.="<SecondJunction>".$arrDirectRows[$i]->DepotNameString."</SecondJunction>";
                        $routeString.="<EndBuses>".$arrDirectRows[$i]->BusesBetweenEndStopAndDepot."</EndBuses>";
//                        list($depotName,$lat,$lon)=explode(":",$arrDirectRows[$i]->IndirectFirstJunction);
//                        $distanceBetweenStartStopAndDepot=distanceBetweenStops($arrDirectRows[$i]->SourceStartStop,$depotName);
//                        // $routeString.="<DistanceBetweenDepotAndStop>".$distanceBetweenStartStopAndDepot."</DistanceBetweenDepotAndStop>";
//                        if($distanceBetweenStartStopAndDepot<.5)
//                        {
//                            $routeString.="<BusesStartStopAndDepot>WD</BusesStartStopAndDepot>";
//                        }
//                        else
//                        {
//                            $routeString.="<BusesStartStopAndDepot>".$arrDirectRows[0]->IndirectStartBuses."</BusesStartStopAndDepot>";
//                        }
                        $routeString.="<EndStop>".$arrDirectRows[$i]->EndStop."</EndStop>";
                 //       $distanceBetweenEndStopAndDepot=distanceBetweenStops($arrDirectRows[0]->DestinationEndStop,$depotName);
//                        if($distanceBetweenEndStopAndDepot<.5)
//                        {
//                            $routeString.="<BusesEndStopAndDepot>WD</BusesEndStopAndDepot>";
//                        }
//                        else
//                        {
//                            $routeString.="<BusesEndStopAndDepot>".$arrDirectRows[0]->IndirectEndBuses."</BusesEndStopAndDepot>";
//                        }

//                        $routeString.="<DistanceBetweenDepotAndStartStop>".$distanceBetweenStartStopAndDepot."</DistanceBetweenDepotAndStartStop>";
//                        $routeString.="<DistanceBetweenDepotAndEndStop>".$distanceBetweenEndStopAndDepot."</DistanceBetweenDepotAndEndStop>";
                        $totalRouteDistance=floatval($arrDirectRows[$i]->TotalRouteDistance)+$startOffsetDistance+$endOffsetDistance;
                        $routeString.="<TotalRouteDistance>".$totalRouteDistance."</TotalRouteDistance>";
                        $routeString.="<UseDepot>1</UseDepot>";
                        $routeString.="</RouteDetails>";
                        $routeString.="</Route>";

                    }
                    $routeString.="</Routes>";
                    return $routeString;
                }
                else if($arrDirectRows[0]->DepotErrorCode=="7"||$arrDirectRows[0]->DepotErrorCode=="8")
                {
                    $depotNameStringArray=explode(":",$arrDirectRows[0]->DepotNameString);
                    $routeString='';
                    //echo $routeString;
                    $totalDistance=$startOffsetDistance+$endOffsetDistance+floatval($arrDirectRows[0]->TotalRouteDistance);
                    $routeInfoString="There is no direct or indirect bus available between ".$arrDirectRows[0]->StartStop." and ".$arrDirectRows[0]->EndStop." We have tried to find a route that takes you to a stop where you can find a bus to your destination. However you might have to travel little offroute to reach to the intermediate stop";
                    $routeString .="<Routes>";
                    $routeString.="<Route>";
                    $routeString.="<IsDirectRoute>N</IsDirectRoute>";
                    $routeString.="<DepotErrorCode>".$arrDirectRows[0]->DepotErrorCode."</DepotErrorCode>";
                    $routeString.="<RouteInfo>".$routeInfoString."</RouteInfo>";
                    $routeString.="<RouteDetails>";
                    $routeString.="<StartStop>".htmlentities($arrDirectRows[0]->StartStop)."</StartStop>";
                    $routeString.="<EndStop>".htmlentities($arrDirectRows[0]->EndStop)."</EndStop>";
                    $routeString.="<Depot>".$arrDirectRows[0]->DepotNameString."</Depot>";
                    $routeString.="<DepotBuses>".getBusesForStopWithFrequency($depotNameStringArray[0])."</DepotBuses>";
                    $routeString.="<BusesBetweenStartStopAndDepot>".$arrDirectRows[0]->BusesBetweenStartStopAndDepot."</BusesBetweenStartStopAndDepot>";
                    $routeString.="<BusesBetweenEndStopAndDepot>".$arrDirectRows[0]->BusesBetweenEndStopAndDepot."</BusesBetweenEndStopAndDepot>";
                    $routeString.="<DistanceBetweenDepotAndStartStop>".$arrDirectRows[0]->DistanceBetweenDepotAndStartStop."</DistanceBetweenDepotAndStartStop>";
                    $routeString.="<DistanceBetweenDepotAndEndStop>".$arrDirectRows[0]->DistanceBetweenDepotAndEndStop."</DistanceBetweenDepotAndEndStop>";
                    $routeString.="<TotalRouteDistance>".$totalDistance."</TotalRouteDistance>";
                    $routeString.="<UseDepot>1</UseDepot>";
                    $routeString.="</RouteDetails>";
                    $routeString.="</Route>";
                    $routeString.="</Routes>";
                    return $routeString;
                }
            }

        }
        else
        {
            for($i=0;$i<$rowsnum;$i++)
            {
                $row=mysql_fetch_object($result);
                if($row->DepotErrorCode=="411")
                {
                    $strRoute='<Routes>';
                    $strRoute=$strRoute.'<Route>';
                    $strRoute=$strRoute.'<IsDirectRoute>N</IsDirectRoute>';
                    $strRoute=$strRoute.'<ErrorCode>411</ErrorCode>';
                    $strRoute=$strRoute.'<StartStop>'.$startStop.'</StartStop>';
                    $strRoute=$strRoute.'<EndStop>'.$endStop.'</EndStop>';
                    $strRoute=$strRoute.'</Route>';
                    $strRoute=$strRoute.'</Routes>';
                	//$log->LogDebug("[".$mediaType."] The result status is 411 between ".$startStop." and ".$endStop);
        
                    return $strRoute;
                }
                else if($row->DepotErrorCode=="10"||$row->DepotErrorCode=="9")
                {
                	//$log->LogDebug("[".$mediaType."] The result status is ".$row->DepotErrorCode." between ".$startStop." and ".$endStop);
    
                    array_push($arrRows,$row);
                }
                else
                {
                    echo "something is really wrong 910";
                }
            }
            if($arrRows[0]->DepotErrorCode=="10"||$arrRows[0]->DepotErrorCode=="9")
            {
                $routeString='';
                $routeString.="<Routes>";
                //$routeString.="<DepotErrorCode>".$arrRows[0]->DepotErrorCode."</DepotErrorCode>";
//                $routeString.="<RouteInfo>There is no direct or indirect bus available between".$arrRows[0]->SourceStartStop." and ".$arrRows[0]->DestinationEndStop.". We have tried to find a route that takes you to a stop where you can find a bus to your destination. However you might have to travel little offroute to reach to the intermediate stop</RouteInfo>";
//                $routeString.="<SourceStartStop>".$arrRows[0]->SourceStartStop."</SourceStartStop>";
//                $routeString.="<DestinationEndStop>".$arrRows[0]->DestinationEndStop."</DestinationEndStop>";
//                $routeString.="<DepotRouteDetails>";
//                list($depotName,$lat,$lon,$buses)=explode(":",$arrRows[0]->DepotNameString);
//                $routeString.="<DepotName>".$depotName.":".$lat.":".$lon."</DepotName>";
//                $routeString.="<BusesBetweenStopAndDepot>".$arrRows[0]->BusesBetweenStopAndDepot."</BusesBetweenStopAndDepot>";
//                $routeString.="<DistanceBetweenDepotAndStop>".$arrRows[0]->DistanceBetweenDepotAndStop."</DistanceBetweenDepotAndStop>";
//                $routeString.="<IndirectRoutes>";
                for($i=0;$i<sizeof($arrRows);$i++)
                {
                    $routeString.="<Route>";
                    $routeString.="<IsDirectRoute>N</IsDirectRoute>";
                    $routeString.="<ErrorCode>".$arrRows[0]->DepotErrorCode."</ErrorCode>";
                    $routeString.="<RouteInfo>There is no direct or indirect bus available between ".$arrRows[0]->SourceStartStop." and ".$arrRows[0]->DestinationEndStop.". We have tried to find a route that takes you to a stop where you can find a bus to your destination. However you might have to travel little offroute to reach to the intermediate stop</RouteInfo>";
                    $routeString.="<RouteDetails>";
                    $routeString.="<SourceStartStop>".$arrRows[0]->SourceStartStop."</SourceStartStop>";
                    $routeString.="<DestinationEndStop>".$arrRows[0]->DestinationEndStop."</DestinationEndStop>";
                    //$routeString.="<DepotRouteDetails>";
                    list($depotName,$lat,$lon,$buses)=explode(":",$arrRows[0]->DepotNameString);
                    $routeString.="<DepotName>".$depotName.":".$lat.":".$lon."</DepotName>";
                    $routeString.="<BusesBetweenStopAndDepot>".$arrRows[0]->BusesBetweenStopAndDepot."</BusesBetweenStopAndDepot>";
                    $routeString.="<DistanceBetweenDepotAndStop>".$arrRows[0]->DistanceBetweenDepotAndStop."</DistanceBetweenDepotAndStop>";
                    $routeString.="<IndirectRoutes>";
                    $routeString.="<IndirectRouteErrorCode>".$arrRows[$i]->IndirectRouteErrorCode."</IndirectRouteErrorCode>";
                    $routeString.="<StartStop>".$arrRows[$i]->IndirectStartStop."</StartStop>";
                    if($arrRows[0]->DepotErrorCode=="10")
                         $routeString.="<StartBuses>".$arrRows[$i]->IndirectStartBuses."</StartBuses>";
                    else
                        $routeString.="<StartBuses>".$arrRows[$i]->IndirectEndBuses."</StartBuses>";

                    $routeString.="<FirstJunction>".$arrRows[$i]->IndirectFirstJunction."</FirstJunction>";
                    $routeString.="<DistanceBetweenJunction>".$arrRows[$i]->IndirectDistanceBetweenJunction."</DistanceBetweenJunction>";
                    $routeString.="<SecondJunction>".$arrRows[$i]->IndirectSecondJunction."</SecondJunction>";
                    if($arrRows[0]->DepotErrorCode=="10")
                        $routeString.="<EndBuses>".$arrRows[$i]->IndirectEndBuses."</EndBuses>";
                    else
                        $routeString.="<EndBuses>".$arrRows[$i]->IndirectStartBuses."</EndBuses>";

                    $routeString.="<EndStop>".$arrRows[$i]->IndirectEndStop."</EndStop>";
                    $routeString.="<TotalIndirectRouteDistance>".$arrRows[$i]->IndirectTotalIndirectRouteDistance."</TotalIndirectRouteDistance>";
                    $routeString.="</IndirectRoutes>";
                    $routeString.="<TotalRouteDistance>".$arrRows[$i]->IndirectTotalRouteDistance."</TotalRouteDistance>";
                    $routeString.="<UseDepot>1</UseDepot>";

                    $routeString.="</RouteDetails>";
                    $routeString.="</Route>";
                }
                //$routeString.="</IndirectRoutes>";
                //$routeString.="</DepotRouteDetails>";
                $routeString.="</Routes>";
                return $routeString;
            }
        }

    }

    return $status;


}







/**
This is the correct one
 */

function getJunctionsForIndirectBusesRevamp2($log,$startStop,$endStop,$startBuses,$endBuses,$startOffsetDistance,
                                             $endOffsetDistance,$showOnlyIndirectBuses,$useDepot="0")
{
    //get the buses that are direct
    // actually the arrays have the last entry as null so 1 less
    $arrSameBus=array();
    //  print_r($startBuses);
    $numStartBuses=sizeof($startBuses);
    //print_r($endBuses);
    for($ii=0;$ii<$numStartBuses;$ii++)
    {
        $tempStart=$startBuses[$ii];
        // this checks is the same bus is available in the start buses and end buses.
        if(in_array($tempStart,$endBuses))
        {
            $element=$tempStart.":".$tempStart;
            array_push($arrSameBus,$element);
        }
    }
    // print_r($arrSameBus);
    $sortedDirectBuses=array();
    //sort all the direct buses on the basis of frequency and distance of the route. Also see if the direct bus is not a BIAs
    if(sizeof($arrSameBus)>0 && $showOnlyIndirectBuses==0 )
    {
        /*echo "before sort <br/>";
        print_r($arrSameBus);
        echo "after sorting <br/>";
        $sortedArray= sortBasedOnBusFrequency($arrSameBus);
        print_r($sortedArray);*/
        $directDistance=getDirectBusDistance($startStop,$endStop,$startOffsetDistance,$endOffsetDistance);

        // $sortedDirectBuses=sortBusesBasedOnDistanceToDestination($arrSameBus);
        $sortedDirectBuses=sortBasedOnBusFrequency($arrSameBus); // at this point we can sort it out based on the frequency of the buses and also let the know the meaning of the frequency
        $sortedDirectBuses=applyBIAFilter($sortedDirectBuses);
        //echo "found the direct buses";
        $log->LogDebug("[STATUS] result status is DIRECT between ".$startStop." and ".$endStop);
        return displayDirectBuses($sortedDirectBuses,$directDistance);


    } /**
     * I think
     */


//    //print_r($arrBuses);
//    $array_size=sizeof($arrBuses);
//    // check if the buses are direct buses
//    list($firstBus,$secondBus)=explode(":",$arrBuses[0]);
//
//    if($array_size>0 && strcmp($firstBus,$secondBus)==0)
//    {
//        //get the direct distance
//        $directDistance=getDirectBusDistance($startStop,$endStop,$startOffsetDistance,$endOffsetDistance);
//        return displayDirectBuses($arrBuses,$directDistance);
//    }
    // indirect buses
    else
    {
        $arrFirstJunctions=array();
        $arrSecondJunctions=array();
        $arrFirstBuses=array();
        $arrSecondBuses=array();

        $numEndBuses=sizeof($endBuses);
        $displayJunctionsDataArray=array();
        //print_r($displayJunctionsDataArray);
        // print_r($startBuses);
        //print_r($endBuses);
        if($numStartBuses >10)
            $numStartBuses=10;
        if($numEndBuses > 10)
            $numEndBuses=10;
       // echo "numstart".$numStartBuses;
        //echo "numEnd".$numEndBuses;
        for($i=0;$i<$numStartBuses;$i++)
        {
            $firstBus=$startBuses[$i];
            $minimum=10000;
            for($j=0;$j<$numEndBuses;$j++)
            {
                $secondBus=$endBuses[$j];
                //avoiud the direct bus combination
                if($firstBus==$secondBus)
                    continue;
                //echo "secondBus".$secondBus;
                // i think when the first bus is same as the second bus this fails
                //find the entry from the table.
                list($firstBusNumber,$firstBusFrequency,$firstBusStartPoint,$secondBusNumber,$secondBusFrequency,
                    $firstBusLowerJunction,$secondBusLowerJunction,$distLower,$lowerJunctionFrequency,$firstBusHigherJunction,
                    $secondBusHigherJunction,$distHigher,$higherJunctionFrequency)=
                    explode(":",getIntermediateStopsAndDistanceWithFrequency($firstBus,$secondBus));

                //echo "firstBusLowerJunction".$firstBusLowerJunction."firstBusHigherJunction".$firstBusHigherJunction;
                //return;
                //need to find which junction is valid for the source stop
                if($firstBusLowerJunction!=$firstBusHigherJunction)
                {
                    //echo "<b>hhh</b>";
                    if(distanceBetweenStops($firstBusStartPoint,$firstBusLowerJunction)<distanceBetweenStops($firstBusStartPoint,$firstBusHigherJunction))
                    {
                        // echo "inside lower <br/>";
                        $junction1=$firstBusLowerJunction;
                        $junction2=$secondBusLowerJunction;
                        $junctionFrequency=$lowerJunctionFrequency;
                        $dist=$distLower;
                        $totalRouteDistance=getTotalRouteDistance($startStop,$junction1,$junction2,$dist,$endStop,$startOffsetDistance,$endOffsetDistance);
                        //echo $totalRouteDistance."<br/>";
                    }
                    else
                    {
                       // echo "inside higher <br/>";
                        $junction1=$firstBusHigherJunction;
                        $junction2=$secondBusHigherJunction;
                        $junctionFrequency=$higherJunctionFrequency;
                        $dist=$distHigher;
                        $totalRouteDistance=getTotalRouteDistance($startStop,$junction1,$junction2,$dist,$endStop,$startOffsetDistance,$endOffsetDistance);
                    }
                }
                else
                {
                    //echo "inside common <br/>";
                    $junction1=$firstBusLowerJunction;
                    $junction2=$secondBusLowerJunction;
                    //echo $junction1;
                    $junctionFrequency=$lowerJunctionFrequency;
                    $dist=$distLower;
                    $totalRouteDistance=getTotalRouteDistance($startStop,$junction1,$junction2,$dist,$endStop,$startOffsetDistance,$endOffsetDistance);
                }

                //echo $dist."<br/>";
                //collective frequency= sum(firstbus frequency, second bus frequency)
                //may be buses are high frequency but the junction are seperataed
                //&& $firstBusFrequency >0 && $secondBusFrequency >0
                if($dist<(float)0.8 )
                {
                    // echo "hh".$junction1.",".$junction2."<br/>";
                    //print_r($displayJunctionsDataArray);
                    //echo "<br/>";
                    if(checkUniqueJunctions($displayJunctionsDataArray,$junction1,$junction2,$dist)==0)
                    {
                       /* echo "jnc1".$junction1."start stop".$startStop."jnc2".$junction2."end stop".$endStop."<br/>";
                        // also chekc that the endstop or the start stop is not same as the juntion as in case of marathahalli bridge and multiplex
                        if(strcmp($junction1,$startStop)==0 ||
                            strcmp($junction1,$endStop)==0 ||
                            strcmp($junction2,$startStop)==0 ||
                            strcmp($junction2,$endStop)==0)
                        {
                            // this is the case that is removing the case 2
                            echo "in the pit";
                            continue;
                        }
                        else
                        {*/
                            $junctionString=$junction1.":".$junction2.":".$dist;
                            //echo "distashdg".$dist;
                            //$junctionString,$totalDistance,$startStop,$endStop,$junction1,$junction2,$junctionDistance
                            $element=new DisplaySortedJunctionsData($junctionString,$totalRouteDistance,$startStop,$endStop,
                                $junction1,$junction2,$dist);
                            //echo "element".$i.",".$j."<br/>";
                            //	print_r($element);
                            array_push($displayJunctionsDataArray,$element);
                            //array_push($arrayUniqueJunctions,$element);
                        //}
                    }

                    /* if($dist<=(float)0.05)
                         $distPenalty=0;
                     else if($dist>(float)0.05 && $dist<=(float)0.2)
                         $distPenalty=0.5;
                     else if($dist>(float)0.2 && $dist<=(float)0.5)
                         $distPenalty=1;
                     else
                         $distPenalty=1.5;
                     echo "penalty".$distPenalty;
                     $collectiveFreq=$firstBusFrequency+$secondBusFrequency-($distPenalty);
                     $element=new IndirectBusStructureWithFrequency($firstBusNumber,$firstBusFrequency,
                         $secondBusNumber,$secondBusFrequency,$junction1,$junction2,$junctionFrequency,$dist,$collectiveFreq,$totalRouteDistance);
                     array_push($arrIndirectBuses,$element);
                    }*/
                }


            }
        }

        //	echo "<br/>";
       // print_r($displayJunctionsDataArray);
        $SortedJunctionsDataArray=sortBasedOnTotalRouteDistance($displayJunctionsDataArray);
        //echo "after sorting <br/>";
         //print_r($SortedJunctionsDataArray);


        //echo "sdsd";



        // display the results
        $strRoute='';
        if(sizeof($SortedJunctionsDataArray)==0||strlen($SortedJunctionsDataArray[0]->getFirstJunction())==0)
        {
            /*$strRoute='<Routes>';
            $strRoute=$strRoute.'<Route>';
            $strRoute=$strRoute.'<IsDirectRoute>N</IsDirectRoute>';
            $strRoute=$strRoute.'<ErrorCode>4</ErrorCode>';
            $strRoute=$strRoute.'</Route>';
            $strRoute=$strRoute.'</Routes>';
            */
            return "404";
        }

        else
        {
            $strRoute='<Routes>';
            $routeDetail='';
            // there can be chances that when we are filling the xml all the details are not properly found. In that case we need to
            // remove that entry. If at the end this check counter becomes equals to the sizeof($SortedJunctionsDataArray). It means every entry had a problem
            // and we will return the error code 4
            $FalseAlarmCounter=0;
            $sizeofSortedJunctions=sizeof($SortedJunctionsDataArray);

            //populate a datastructure
            for($i=0;$i<$sizeofSortedJunctions;$i++)
            {
                list($firstJnName,$secondJnName,$distanceJn)=explode(":",$SortedJunctionsDataArray[$i]->getJunctionString());
                $getCommonBusesForFirstJunctionArray=getBusesCommonBetweenTwoStops($startStop,$firstJnName);
                $getCommonBusesForSecondJunctionArray=getBusesCommonBetweenTwoStops($secondJnName,$endStop);
                $junction1Frequency=sizeof($getCommonBusesForFirstJunctionArray);
                $junction2Frequency=sizeof($getCommonBusesForSecondJunctionArray);
                $getCommonBusesForFirstJunction=implode(",",$getCommonBusesForFirstJunctionArray);
                $getCommonBusesForSecondJunction=implode(",",$getCommonBusesForSecondJunctionArray);
                $SortedJunctionsDataArray[$i]->setFirstJunctionFrequency($junction1Frequency);
                $SortedJunctionsDataArray[$i]->setSecondJunctionFrequency($junction2Frequency);
                $SortedJunctionsDataArray[$i]->setStartBusString($getCommonBusesForFirstJunction);
                $SortedJunctionsDataArray[$i]->setEndBusString($getCommonBusesForSecondJunction);

            }
            // print_r($SortedJunctionsDataArray);

            //echo "Raw";
            //print_r($SortedJunctionsDataArray);
            //echo "<br/><hr/>";
            //sorting on the baseis of the collective
            //$SortedJunctionsDataArray= sortBasedOnJunctionFrequency($SortedJunctionsDataArray);
            $SortedJunctionsDataArray= prioritizeAndSortJunctionArray($SortedJunctionsDataArray);
            //echo "final";
            //print_r($SortedJunctionsDataArray);

            $sizeofSortedJunctions=sizeof($SortedJunctionsDataArray);

            //print_r($SortedJunctionsDataArray);
            //need to see how to create this xml. If needed we can break the bus into seperate sml tags, every element as
            //speerate xml tag
            for($i=0;$i<$sizeofSortedJunctions;$i++)
            {
                $isCorrectEntry=1;// check if this particular entry is correct. Since the node need to be removed otherwise
                if($i>2)// this will restrict the indirect results to 3
                    break;
                //list($firstJnName,$secondJnName,$distanceJn)=explode(":",$SortedJunctionsDataArray[$i]->getJunctionString());
                $firstJnName=$SortedJunctionsDataArray[$i]->getFirstJunction();
                $secondJnName=$SortedJunctionsDataArray[$i]->getSecondJunction();
                $distanceJn=$SortedJunctionsDataArray[$i]->getJunctionDistance();
                $junction1Frequency=$SortedJunctionsDataArray[$i]->getFirstJunctionFrequency();
                $junction2Frequency=$SortedJunctionsDataArray[$i]->getSecondJunctionFrequency();
                //get all the buses for firstJn
                $strRoute=$strRoute.'<Route>';
                $strRoute=$strRoute.'<IsDirectRoute>N</IsDirectRoute>';
				//echo "<h1>data firstJnName->".$firstJnName." secondJunctionName->".$secondJnName. " endpoint->".$endStop."</h1>";
                /**
                 *We have the following conditions now
                 * if(J1==J2) && (A!=J1) && (B!=J2) ...then this is error code 1
                 * If(J1!=J2)
                 *    if(A==J1) && (B!=J2) ..then this is error code 4
                 *    if(A!=J1) && (B==J2) ..then this is error code 2
                 *    if(A!=J1) && (B!=J2) ..then this is error code 3
                 *
                 *
                 * */

                if($junction1Frequency>0 && $junction2Frequency>0)
                {
                    $getCommonBusesForFirstJunction=$SortedJunctionsDataArray[$i]->getStartBusString();//getBusesCommonBetweenTwoStops($startStop,$firstJnName);
                    $getCommonBusesForSecondJunction=$SortedJunctionsDataArray[$i]->getEndBusString();//getBusesCommonBetweenTwoStops($secondJnName,$endStop);
                    //condition exists in magestic okalipuran. basically sometimes there is no valid indirect route
                    //it means that there is only a single junction and also the junction is same as the stop
                    if(($distanceJn==0 && strcmp($firstJnName,$startStop)==0)||($distanceJn==0 && strcmp($secondJnName,$endStop)==0))
                    {
                        return exceptionalConditionForIndirectRoute($startStop,$endStop);
                    }
                    if($distanceJn==0 && strcmp($firstJnName,$startStop)!=0 && strcmp($secondJnName,$endStop)!=0)
                    {
                        $strRoute=$strRoute.'<ErrorCode>1</ErrorCode>';
                        $log->LogDebug("[STATUS] result status is 1 between ".$startStop." and ".$endStop);
    
                    }
                    else
                    {
                        //echo intval($distanceJn).",".$startStop.",".$endStop.",".$firstJnName.",".$secondJnName."<br/>";
                        if($distanceJn!=0)
                        {

                            if(strcmp($firstJnName,$startStop)==0 && strcmp($secondJnName,$endStop)!=0)
                            {

                                // this is more or less like error code 1.
                                $strRoute=$strRoute.'<ErrorCode>4</ErrorCode>';
                                $log->LogDebug("[STATUS] result status is 4 between ".$startStop." and ".$endStop);
                            }
                            else if(strcmp($firstJnName,$startStop)!=0 && strcmp($secondJnName,$endStop)==0)
                            {
                                //echo "dss2";
                                $strRoute=$strRoute.'<ErrorCode>2</ErrorCode>';
                                $log->LogDebug("[STATUS] result status is 2 between ".$startStop." and ".$endStop);
                            }
                            else if(strcmp($firstJnName,$startStop)!=0 && strcmp($secondJnName,$endStop)!=0)
                            {
                                $strRoute=$strRoute.'<ErrorCode>3</ErrorCode>';
                                $log->LogDebug("[STATUS] result status is 3 between ".$startStop." and ".$endStop);
                            }
                            else
                            {

                                return exceptionalConditionForIndirectRoute($startStop,$endStop);  //not sure what has happened
                            }

                        }

                    }
                    $routeDetail="<StartStop>".htmlentities($startStop)."</StartStop>";
                    $routeDetail=$routeDetail."<StartBuses>".$getCommonBusesForFirstJunction."</StartBuses>";
                    $routeDetail=$routeDetail."<FirstJunction>".htmlentities($firstJnName).":".getLatitudeLongitude($firstJnName)."</FirstJunction>";
                    $routeDetail=$routeDetail."<DistanceBetweenJunction>".$distanceJn."</DistanceBetweenJunction>";
                    $routeDetail=$routeDetail."<SecondJunction>".htmlentities($secondJnName).":".getLatitudeLongitude($secondJnName)."</SecondJunction>";
                    $routeDetail=$routeDetail."<EndBuses>".$getCommonBusesForSecondJunction."</EndBuses>";
                    $routeDetail=$routeDetail."<EndStop>".htmlentities($endStop)."</EndStop>";

                }
                else
                {
                    $FalseAlarmCounter++;
                }




               /*
                if(strcmp($secondJnName,$endStop)==0)
                {

                    if($junction1Frequency>0 && $junction2Frequency>0)
                    {
                        $getCommonBusesForFirstJunction=$SortedJunctionsDataArray[$i]->getStartBusString();//getBusesCommonBetweenTwoStops($startStop,$firstJnName);
                        $getCommonBusesForSecondJunction=$SortedJunctionsDataArray[$i]->getEndBusString();//getBusesCommonBetweenTwoStops($secondJnName,$endStop);
                        $strRoute=$strRoute.'<ErrorCode>2</ErrorCode>';
                        $routeDetail="<StartStop>".htmlentities($startStop)."</StartStop>";
                        $routeDetail=$routeDetail."<StartBuses>".$getCommonBusesForFirstJunction."</StartBuses>";
                        $routeDetail=$routeDetail."<FirstJunction>".htmlentities($firstJnName).":".getLatitudeLongitude($firstJnName)."</FirstJunction>";
                        $routeDetail=$routeDetail."<DistanceBetweenJunction>".$distanceJn."</DistanceBetweenJunction>";
                        $routeDetail=$routeDetail."<SecondJunction>".htmlentities($secondJnName).":".getLatitudeLongitude($secondJnName)."</SecondJunction>";
                        $routeDetail=$routeDetail."<EndBuses>".$getCommonBusesForSecondJunction."</EndBuses>";
                        $routeDetail=$routeDetail."<EndStop>".htmlentities($endStop)."</EndStop>";
                    }
                    else
                    {
                        $FalseAlarmCounter++;
                    }
                }
                else
                {
                    if($distanceJn==0 && strcmp($firstJnName,$startStop)!=0)
                    {
                        $getCommonBusesForFirstJunction=$SortedJunctionsDataArray[$i]->getStartBusString();//getBusesCommonBetweenTwoStops($startStop,$firstJnName);
                        $getCommonBusesForSecondJunction=$SortedJunctionsDataArray[$i]->getEndBusString();//getBusesCommonBetweenTwoStops($secondJnName,$endStop);
                        if($junction1Frequency>0 && $junction2Frequency>0)
                        {
                            $strRoute=$strRoute.'<ErrorCode>1</ErrorCode>';
                            $routeDetail="<StartStop>".htmlentities($startStop)."</StartStop>";
                            $routeDetail=$routeDetail."<StartBuses>".$getCommonBusesForFirstJunction."</StartBuses>";
                            $routeDetail=$routeDetail."<FirstJunction>".htmlentities($firstJnName).":".getLatitudeLongitude($firstJnName)."</FirstJunction>";
                            $routeDetail=$routeDetail."<DistanceBetweenJunction>0</DistanceBetweenJunction>";
                            $routeDetail=$routeDetail."<SecondJunction>".htmlentities($secondJnName).":".getLatitudeLongitude($secondJnName)."</SecondJunction>";
                            $routeDetail=$routeDetail."<EndBuses>".$getCommonBusesForSecondJunction."</EndBuses>";
                            $routeDetail=$routeDetail."<EndStop>".htmlentities($endStop)."</EndStop>";
                        }
                        else
                        {
                            $FalseAlarmCounter++;
                        }

                    }
                    else
                    {
                        $getCommonBusesForFirstJunction=$SortedJunctionsDataArray[$i]->getStartBusString();//getBusesCommonBetweenTwoStops($startStop,$firstJnName);
                        $getCommonBusesForSecondJunction=$SortedJunctionsDataArray[$i]->getEndBusString();
                        if($junction1Frequency>0 && $junction2Frequency>0)
                        {
                            $strRoute=$strRoute.'<ErrorCode>3</ErrorCode>';
                            $routeDetail="<StartStop>".htmlentities($startStop)."</StartStop>";
                            $routeDetail=$routeDetail."<StartBuses>".$getCommonBusesForFirstJunction."</StartBuses>";
                            $routeDetail=$routeDetail."<FirstJunction>".htmlentities($firstJnName).":".getLatitudeLongitude($firstJnName)."</FirstJunction>";
                            $routeDetail=$routeDetail."<DistanceBetweenJunction>".$distanceJn."</DistanceBetweenJunction>";
                            $routeDetail=$routeDetail."<SecondJunction>".htmlentities($secondJnName).":".getLatitudeLongitude($secondJnName)."</SecondJunction>";
                            $routeDetail=$routeDetail."<EndBuses>".$getCommonBusesForSecondJunction."</EndBuses>";
                            $routeDetail=$routeDetail."<EndStop>".htmlentities($endStop)."</EndStop>";
                        }
                        else
                        {
                            $FalseAlarmCounter++;
                        }

                    }
                }*/

                $routeDetail=$routeDetail."<TotalRouteDistance>".$SortedJunctionsDataArray[$i]->getTotalRouteDistance()."</TotalRouteDistance>";
                $routeDetail=$routeDetail."<UseDepot>".$useDepot."</UseDepot>";
                $strRoute=$strRoute.'<RouteDetails>'.$routeDetail.'</RouteDetails>';
                $strRoute=$strRoute.'</Route>';
            }
            $strRoute=$strRoute.'</Routes>';
            // check if every thing was wrong that means we need to send the erro codes.
            if($FalseAlarmCounter==sizeof($SortedJunctionsDataArray))
            {
                return "405";
            }

        }
        // echo "---------------------------------<br/>";

        // echo $strRoute;
        return $strRoute;

    }
}


/**
 * busnumber:busfreq to be converted to <busDetail><BusNumber></BusNumber><BusFrequency></busFrequency></busdetail>
 * @param busString $
 */
function getBusFrequencyXML($busString)
{
    $busStringArray=explode(",",$busString);
    $str="<BusDetails>";
    for($i=0;$i<sizeof($busStringArray);$i++)
    {
        list($busNumber,$busFreq)=explode(":",$busStringArray[$i]);
        $str=$str."<BusDetail>";
        $str=$str."<BusNumber>".$busNumber."</BusNumber>";
        $str=$str."<BusFreq>".$busFreq."</BusFreq>";
        $str=$str."</BusDetail>";

    }
    $str=$str."</BusDetails>";
    return $str;
}



function getDepotName($stopName)
{

    //get all the buses from this stop
    $sql="Select PeggedDepot , DistanceFromDepot from Stops where StopName='".$stopName."'";
    //echo $sql;
    $result=mysql_query($sql);
    $rowsnum=mysql_num_rows($result);

    if($rowsnum==0)
        return "404:404";
    $row=mysql_fetch_row($result);
    //print_r($row);
    return trim($row[0]).":".$row[1];

    /*
    $arrDepot=array();
    //find all the depots
    for($i=0;$i<$rowsnum;$i++)
    {
        $row=mysql_fetch_row($result);
        $depotSql="select StopName from newbusdetails where (stopName like '%Depot%' or stopName like 'Majestic%')
        and busnumber='".$row[0]."'";
        $depotResult=mysql_query($depotSql);
        $depotRowsnum=mysql_num_rows($depotResult);
        if($depotRowsnum>0)
        {
            $rowDepot=mysql_fetch_row($depotResult);
            array_push($arrDepot,array("depot"=>trim($rowDepot[0]),"bus"=>$row[0]));
            print_r($arrDepot);
        }
    }

    print_r($arrDepot);

    //find the depot that is nearest
    $min=10000;
    $nearestDepot="";
    $depotBus='';
    for($i=0;$i<sizeof($arrDepot);$i++)
    {
       $dist=distanceBetweenStops($stopName,$arrDepot[$i]["depot"]);
        if($dist<$min)
        {
            $min=$dist;
            $nearestDepot=$arrDepot[$i];
            $depotBus=$arrDepot[$i]["bus"];
        }
    }
    if($min==10000)
    {
        return "404";
    }
    return $nearestDepot.":".$min.":".$depotBus;
    */
}



function distanceBetweenStops($stop1,$stop2)
{
    list($lat1,$lon1)=explode(":",getLatitudeLongitude($stop1));
    list($lat2,$lon2)=explode(":",getLatitudeLongitude($stop2));
    $dist=distance($lat1,$lon1,$lat2,$lon2,"K");
    //echo "distance between".$stop1."and".$stop2."is".$dist;
    return $dist;
}



function getIntermediateStopsAndDistanceWithFrequency($firstBusWithFrequency,$secondBusWithFrequency)
{
    //echo "<br/>getIntermediateStopsAndDistanceWithFrequency..firstBusWithFrequency".$firstBusWithFrequency."  secondBusWithFrequency".$secondBusWithFrequency;
    list($firstBus,$firstBusFrequency)=explode(":",$firstBusWithFrequency);
    list($secondBus,$secondBusFrequency)=explode(":",$secondBusWithFrequency);

    //get the first bus starting point
    $sql="Select StopName from newBusDetails where BusNumber='".$firstBus."' and StopNumber=1";
    //echo $sql;
    $result=mysql_query($sql);
    $row=mysql_fetch_row($result);
    $firstBusStartingPoint=$row[0];
    //echo "first bus".$firstBusStartingPoint;

    //get the frequency
    /*$sqlFreq="select * from busFrequency where busNumber in ('".$firstBus."','".$secondBus."')";
    $resFreq=mysql_query($sqlFreq);
    $rowFirstFreq=mysql_fetch_row($resFreq);
    $firstBusFrequency=$rowFirstFreq[1];
    $rowSecondFreq=mysql_fetch_row($resFreq);
    $secondBusFrequency=$rowSecondFreq[1];
    */
    //get the intermediate
    $sqlInter="Select FirstBusRouteLowerStop,SecondBusRouteLowerStop,DistanceLowerStop,LowerStopFrequencyWithSecondBusEndpoint,
    FirstBusRouteHigherStop,SecondBusRouteHigherStop,DistanceHigherStop,HigherStopFrequencyWithSecondBusEndpoint from newIntermediateStops
    where FirstBusNumber='".$firstBus."' and SecondBusNumber='".$secondBus."'";
    //echo $sqlInter;
    $resultInter=mysql_query($sqlInter);
    $rowInter=mysql_fetch_row($resultInter);

    $firstBusRouteLowerStop=$rowInter[0];
    $secondBusRouteLowerStop=$rowInter[1];
    $distanceBetweenLowerStops=$rowInter[2];
    $junctionLowerFrequency=$rowInter[3];
    $firstBusRouteHigherStop=$rowInter[4];
    $secondBusRouteHigherStop=$rowInter[5];
    $distanceBetweenHigherStops=$rowInter[6];
    $junctionHigherFrequency=$rowInter[7];

    return $firstBus.":".$firstBusFrequency.":".$firstBusStartingPoint.":".$secondBus.":".$secondBusFrequency.":".$firstBusRouteLowerStop.
        ":".$secondBusRouteLowerStop.":".$distanceBetweenLowerStops.":".$junctionLowerFrequency.":".$firstBusRouteHigherStop.":".
        $secondBusRouteHigherStop.":".$distanceBetweenHigherStops.":".$junctionHigherFrequency;


}



function applyBIAFilter($arrBuses)
{

    // echo "<br/>";
    $len=sizeof($arrBuses);
    $arrBIABuses=array();
    $arrNormalBuses=array();
    for($i=0;$i<$len;$i++)
    {
        list($firstBus,$secondBus)=explode(":",$arrBuses[$i]);
        $pos=strpos($firstBus,'BIAS');
        //echo $pos1.$pos2;
        if($pos!==false)
        {
            //echo $element;
            array_push($arrBIABuses,$arrBuses[$i]);

        }
        else
        {
            array_push($arrNormalBuses,$arrBuses[$i]);
        }
        //continue;
    }
    // echo "the bia buses";



    //print_r($arrBIABuses);

    $arr=array_merge($arrNormalBuses,$arrBIABuses);
    return $arr;

}

/**
 * the function will be used to calculate the latitude and longitude of a stop
 *
 **/

function getLatitudeLongitude($stopName)
{

    $query="SELECT Latitude, Longitude FROM  Stops WHERE (StopName ='".$stopName."')";
    $result=mysql_query($query);
    $row=mysql_fetch_row($result);

    $lat1=$row[0];
    $lon1=$row[1];

    return $lat1.":".$lon1;

}

/**
 * getTotalRouteDistance: This function will find the total distance of the suggested route(walking+bus).
 * it is the approximate distance since we will be making use of the straight lines
 **/
function getTotalRouteDistance($startStop,$firstJunction,$secondJunction,$intermediateDistance,$endStop,$startOffsetDistance,$endOffsetDistance)
{

    //latitude & longitude for startStop
    $query="SELECT Latitude, Longitude FROM  Stops WHERE (StopName ='".$startStop."')";
    $result=mysql_query($query);
    $row=mysql_fetch_row($result);

    $lat1=$row[0];
    $lon1=$row[1];

    //latitude & longitude for firstJunction
    $query="SELECT Latitude, Longitude FROM  Stops WHERE (StopName ='".$firstJunction."')";
    $result=mysql_query($query);
    $row=mysql_fetch_row($result);
    $lat2=$row[0];
    $lon2=$row[1];

    $dist1=distance($lat1, $lon1, $lat2, $lon2, "K");

    //latitude & longitude for secondJunction
    $query="SELECT Latitude, Longitude FROM  Stops WHERE (StopName ='".$secondJunction."')";
    $result=mysql_query($query);
    $row=mysql_fetch_row($result);
    $lat3=$row[0];
    $lon3=$row[1];

    //latitude & longitude for endStop
    $query="SELECT Latitude, Longitude FROM  Stops WHERE (StopName ='".$endStop."')";
    $result=mysql_query($query);
    $row=mysql_fetch_row($result);
    $lat4=$row[0];
    $lon4=$row[1];

    $dist2=distance($lat3, $lon3, $lat4, $lon4, "K");

    $total=$startOffsetDistance+$dist1+$intermediateDistance+$dist2+$endOffsetDistance;
    return $total;

}


/**
 * Get the buses that are common between the two stops
 * @param $stopName1
 * @param $stopName2
 */
function getBusesCommonBetweenTwoStops($stopName1,$stopName2)
{

    //echo $stopName1.",".$stopName2;

    //could also use
    /*
     *
     * select * from newBusdetails as B,newBusDetails as A where (A.BusNumber=B.BusNumber) and (A.stopName='Vidyapeetha Circle' and
B.stopName='Kamakya (Depot 13)')
     *
     */

    //get all the buses for stopName1
    $startBuses=getBusesForStopWithFrequency($stopName1);
    // echo "start buses";
    //print_r($startBuses);
    //echo "<br/>";
    $startBusesArray=explode(",",$startBuses);
    //get all the buses for stopName2
    $endBuses=getBusesForStopWithFrequency($stopName2);
    //echo "End buses";
    //print_r($endBuses);
    //echo "<br/>";
    $endBusesArray=explode(",",$endBuses);
    //get the intersection
    $commonBusesArrayTemp=array_intersect($startBusesArray,$endBusesArray);

    $commonBusesArray=array();


    // echo "common buses between".$stopName1."..and..".$stopName2;
    //print_r($commonBuses);
    $sizeofCommonBuses=sizeof($commonBusesArrayTemp);
    //required to reset the keys from 0
    foreach($commonBusesArrayTemp as $key=>$value)
    {
        array_push($commonBusesArray,$value);
    }

    //print_r($commonBusesArray);
    //echo "<br/>";
    /*$commonBusesString='';
    for($j=0;$j<$sizeofCommonBuses;$j++)
    {

        if($j==$sizeofCommonBuses-1)
            $commonBusesString = $commonBusesString.$commonBuses[$j];
        else
            $commonBusesString = $commonBusesString.$commonBuses[$j].",";
    }
    */

    //array_intersect_key()
    //need to fix the indexes of the array after the intersection
    //echo implode(",",sortBasedOnBusFrequency($commonBusesArray));
    //print_r($commonBusesArray);
    return sortBasedOnBusFrequency($commonBusesArray);
    //echo $commonBusesString;
    //return $commonBusesString;
}


/**
getCommonBusesForNormalizedStopName: function returns the common buses that go to the junction from the start or end point. We will be
TODO: CHekc the functionality again
 **/
function getCommonBusesForNormalizedStopName($stopName,$arrBusesForIntersection)
{
    $arrJunctionBuses=array();
    $arrJunctionBuses=getArrayBusesForStop($stopName);
//	print_r($arrBusesForIntersection);
//	echo "$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$";
//	print_r($arrJunctionBuses);
    $arrCommon=array();
    //$arrCommon=array_intersect($arrJunctionBuses,$arrBusesForIntersection);
    //find the common values
    for($i=0;$i<sizeof($arrBusesForIntersection);$i++)
    {

        $temp=$arrBusesForIntersection[$i];
        for($j=0;$j<sizeof($arrJunctionBuses);$j++)
        {
            if(strcmp($temp,$arrJunctionBuses[$j])==0)
            {
                if(checkArray($arrCommon,$temp)==0)
                    array_push($arrCommon,$temp);
            }
        }
    }
    $buses="";
    for($i=0;$i<sizeof($arrCommon);$i++)
    {
        $buses = $buses.$arrCommon[$i].",";
    }

    //return getBusesForStop($stopName);
    return $buses;
}

/**
 *getNormalizedStopName:Helper fucntion to normalize the various names
 **/
function getNormalizedStopName($stopName)
{
    // dont want to use

    //	echo "to be normalized".$stopName;
    // this will be for the bus going  towards forum and bellandur
    /*if(strcmp($stopName,"Central Silk Board (ORR)")==0||
        strcmp($stopName,"Central Silk Board (Hosur RD)")==0||
        strcmp($stopName,"Central Silk Board (BTM)")==0)
        return "Central Silk Board (ORR)";

    //check for Marathalli..need to ange it to something else
    if(strcmp($stopName,"Marathahalli (Jn. Vartur & ORR)")==0 ||
        strcmp($stopName,"Marathahalli Bridge (ORR)")==0 ||
        strcmp($stopName,"Marathahalli Bridge")==0 ||
        strcmp($stopName,"Marathahalli Multiplex Bridge")==0 ||
        strcmp($stopName,"Marathahalli Jn")==0)
                return "Marathahalli Bridge";

    if(strcmp($stopName,"Marathahalli Jn(Multiplex)")==0 ||
        strcmp($stopName,"Marathahalli (Mulitplex ORR)")==0 ||
        strcmp($stopName,"Marathahalli Multiplex")==0)
                return "Marathahalli Multiplex";


    // check for the Hebbala... need to see for the stuffs having the hebbala  like sanjay nagar i think return heballa orr

    if(strcmp($stopName,"Hebbala")==0 ||
        strcmp($stopName,"Hebbala (ORR)")==0 ||
        strcmp($stopName,"Hebbala (Canara Bank)")==0)
    return "Hebbala";*/

// there is no normalization
    return $stopName;
}



/**
 *checkArray:fucntion to check if the string is present in the array
 **/
function checkArray($arr,$str)
{
    $array_size=sizeof($arr);
    //$flag=0;
    for($i=0;$i<$array_size;$i++)
    {

        if(strcmp($str,$arr[$i])==0)
        {
            return 1;
        }

    }
    return 0;
}

/**
checkUniqueJunctions:Function to get the unique combinatiopns of the first junction and the second junction 
 **/
function checkUniqueJunctions($arr,$firstJunction,$secondJunction,$distance)
{
    //echo "inside check uni";
    //print_r($arr);
    $array_size=sizeof($arr);
    //$flag=0;
    for($i=0;$i<$array_size;$i++)
    {
        //echo "dddf";
        list($firstJnName,$secondJnName,$distanceJn)=explode(":",$arr[$i]->getJunctionString());
        if(strcmp($firstJnName,$firstJunction)==0 && strcmp($secondJnName,$secondJunction)==0 && $distanceJn==$distance)
        {
            return 1;
        }

    }
    return 0;

}



/**
 *displayDirectBuses: function to display the direct buses
 **/
function displayDirectBuses($arrBuses,$directDistance)
{
    // print_r($arrBuses);
    $strRoute='';
    $strRoute='<Routes>';
    $array_size=sizeof($arrBuses);
    //print_r($arrBuses);
    //need to show the frequency
    for($i=0;$i<$array_size;$i++)
    {
        $strRoute=$strRoute.'<Route>';
        list($firstBus,$firstBusFrequency)=explode(":",$arrBuses[$i]);
        //echo "Direct Bus #".$i."=>".$firstBus."=>".$secondBus."</br>";
        $strRoute=$strRoute.'<IsDirectRoute>Y</IsDirectRoute>';
        $strRoute=$strRoute.'<ErrorCode>0</ErrorCode>';
        $strRoute=$strRoute.'<RouteDetails>'.$firstBus.'</RouteDetails>';
        $strRoute=$strRoute.'<RouteFrequency>'.$firstBusFrequency.'</RouteFrequency>';
        $strRoute=$strRoute.'<DirectDistance>'.$directDistance.'</DirectDistance>';
        $strRoute=$strRoute.'</Route>';

    }
    $strRoute=$strRoute.'</Routes>';
    return $strRoute;
    //echo $strRoute;
}

//just the route section and not the full xml
function displayDirectBusesWithoutFormatting($arrBuses,$directDistance)
{
    // print_r($arrBuses);
    $strRoute='';
    $array_size=sizeof($arrBuses);
    //print_r($arrBuses);
    //need to show the frequency
    for($i=0;$i<$array_size;$i++)
    {
        $strRoute=$strRoute.'<Route>';
        list($firstBus,$firstBusFrequency)=explode(":",$arrBuses[$i]);
        //echo "Direct Bus #".$i."=>".$firstBus."=>".$secondBus."</br>";
        $strRoute=$strRoute.'<IsDirectRoute>Y</IsDirectRoute>';
        $strRoute=$strRoute.'<ErrorCode>0</ErrorCode>';
        $strRoute=$strRoute.'<RouteDetails>'.$firstBus.'</RouteDetails>';
        $strRoute=$strRoute.'<RouteFrequency>'.$firstBusFrequency.'</RouteFrequency>';
        $strRoute=$strRoute.'<DirectDistance>'.$directDistance.'</DirectDistance>';
        $strRoute=$strRoute.'</Route>';

    }
    return $strRoute;
    //echo $strRoute;
}

//just the route section and not the full xml
function displayDirectBusesForDepot($arrBuses,$directDistance)
{
    // print_r($arrBuses);
    $strRoute='';
    //$strRoute=$strRoute."<DirectBuses>".
    $array_size=sizeof($arrBuses);
    //print_r($arrBuses);
    //need to show the frequency
    for($i=0;$i<$array_size;$i++)
    {
        //$strRoute=$strRoute.'';
        list($firstBus,$firstBusFrequency)=explode(":",$arrBuses[$i]);
        //echo "Direct Bus #".$i."=>".$firstBus."=>".$secondBus."</br>";
        //$strRoute=$strRoute.'<IsDirectRoute>Y</IsDirectRoute>';
        //$strRoute=$strRoute.'<ErrorCode>0</ErrorCode>';
        //$strRoute=$strRoute.'<RouteDetails>'.$firstBus.'</RouteDetails>';
        //$strRoute=$strRoute.'<RouteFrequency>'.$firstBusFrequency.'</RouteFrequency>';
        //$strRoute=$strRoute.'<DirectDistance>'.$directDistance.'</DirectDistance>';
        //$strRoute=$strRoute.'</Route>';
        $strRoute=$strRoute.$firstBus.":".$firstBusFrequency.":".$directDistance.'#';
    }

    return $strRoute;
    //echo $strRoute;
}




/**
get Latitude longitude of a stop
 **/
function findDistanceBetweenSourceDestination($source,$destination)
{
    $sourceQuery="Select Latitude,Longitude from Stops where StopName='".$source."'";
    $sourceResult= mysql_query($sourceQuery);
    $sourceRow=mysql_fetch_row($sourceResult);
    $lat1 = $sourceRow[0];
    $lon1=$sourceRow[1];

    $destQuery="Select Latitude,Longitude from Stops where StopName='".$destination."'";
    $destResult= mysql_query($destQuery);
    $destRow=mysql_fetch_row($destResult);
    $lat2 = $destRow[0];
    $lon2=$destRow[1];

    $dist=distance($lat1, $lon1, $lat2, $lon2, "K");
    return $lat1.":".$lon1.":".$lat2.":".$lon2.":".$dist;

}

/**
This function is supposed to go through the sorted junction array and find out if there are any single junction solutions that are available
If they are available then we need to speerate them out 
sort them 
similarly we need to sort out the double junction solutions
then combine them together and display
sometimes it might happen that the single junction solution is little lenghty but then its is better since you dont have to change the bus
 **/
function prioritizeAndSortJunctionArray($sortedJunctionArray)
{
    $singleJunctionArray=array();
    $doubleJunctionArray=array();
    //find the minimum distance .. we need to remove those routes whosde distance becomes too large like majestic to okalipuram.
    // one way to find that is to remove all the entries where the distance > 2*minimal distace.
    $mindist=$sortedJunctionArray[0]->getTotalRouteDistance();
    //echo "minimum dia".$mindist."<br/>";
    $maxAllowedDistance=2*$mindist;
    //echo "max distance".$maxAllowedDistance."<br/>";
    //print_r($sortedJunctionArray);
    for($i=0;$i<sizeof($sortedJunctionArray);$i++)
    {

        //added the condition for the cases where j2 is same as the endpoint. Potentially all the cases of errorcode3
        //if you just want to search for j2 then you will see them. This will also save the depot route in many cases
        if($sortedJunctionArray[$i]->getSecondJunction()==$sortedJunctionArray[$i]->getEndStop())
        {
            array_push($singleJunctionArray,$sortedJunctionArray[$i]);
        }
        else
        {
            // read the first point
            //$junctionString=$element=getNormalizedStopName($firstJunction).":".getNormalizedStopName($secondJunction).":".$distance;
            //list($firstJunction,$secondJunction,$distance)=explode(":",$sortedJunctionArray[$i]->getJunctionString());
            if($sortedJunctionArray[$i]->getTotalRouteDistance() < $maxAllowedDistance)
            {
                if($sortedJunctionArray[$i]->getJunctionDistance()<0.001)
                    //if(strcmp($firstJunction,$secondJunction)==0)
                    array_push($singleJunctionArray,$sortedJunctionArray[$i]);
                else
                    array_push($doubleJunctionArray,$sortedJunctionArray[$i]);
            }
        }
    }
    //sorting the single junction array on the basis of frequency also
    $singleJunctionArray=sortBasedOnJunctionFrequency($singleJunctionArray);
    //echo "after";
    //print_r($singleJunctionArray);

    //reunite the array or basically combine the two arrays
    // and then return them back
    $finalArray=array();
    for($i=0;$i<sizeof($singleJunctionArray);$i++)
    {
        array_push($finalArray,$singleJunctionArray[$i]);
    }

    for($i=0;$i<sizeof($doubleJunctionArray);$i++)
    {
        array_push($finalArray,$doubleJunctionArray[$i]);
    }

    //echo "heeey";
    return $finalArray;
}


/**
 * getDirectBusDistance: This function will find the distance of the direct bus
 **/
function getDirectBusDistance($startStop,$endStop,$startOffsetDistance,$endOffsetDistance,$xml=false)
{
    $strRoute='';
    $strRoute='<Distances>';
    //latitude & longitude for startStop
    $query="SELECT Latitude, Longitude FROM  Stops WHERE (StopName ='".$startStop."')";
    $result=mysql_query($query);
    $row=mysql_fetch_row($result);

    $lat1=$row[0];
    $lon1=$row[1];

    //latitude & longitude for firstJunction
    $query="SELECT Latitude, Longitude FROM  Stops WHERE (StopName ='".$endStop."')";
    $result=mysql_query($query);
    $row=mysql_fetch_row($result);
    $lat2=$row[0];
    $lon2=$row[1];

    $dist1=distance($lat1, $lon1, $lat2, $lon2, "K");

    $totalDirectRouteDistance=$startOffsetDistance+$dist1+$endOffsetDistance;
    if($xml==true)
    {
        $strRoute=$strRoute.'<Distance>';
        $strRoute=$strRoute.'<StartStop>'.$startStop.'</StartStop>';
        $strRoute=$strRoute.'<EndStop>'.$endStop.'</EndStop>';
        $strRoute=$strRoute.'<StartOffsetDistance>'.$startOffsetDistance.'</StartOffsetDistance>';
        $strRoute=$strRoute.'<EndOffsetDistance>'.$endOffsetDistance.'</EndOffsetDistance>';
        $strRoute=$strRoute.'<RouteDistance>'.$totalDirectRouteDistance.'</RouteDistance>';
        $strRoute=$strRoute.'</Distance>';
        $strRoute=$strRoute.'</Distances>';
        //return $totalDirectRouteDistance;
        return $strRoute;
    }
    else
        return $totalDirectRouteDistance; //just the number
}



function exceptionalCondition($startStop,$endStop)
{
    $strRoute='<Routes>';
    $strRoute=$strRoute.'<Route>';
    $strRoute=$strRoute.'<IsDirectRoute>N</IsDirectRoute>';
    $strRoute=$strRoute.'<ErrorCode>412</ErrorCode>';
    $strRoute=$strRoute.'<StartStop>'.$startStop.'</StartStop>';
    $strRoute=$strRoute.'<EndStop>'.$endStop.'</EndStop>';
    $strRoute=$strRoute.'</Route>';
    $strRoute=$strRoute.'</Routes>';
    return $strRoute;
}

function exceptionalConditionForIndirectRoute($startStop,$endStop)
{
    $strRoute='<Routes>';
    $strRoute=$strRoute.'<Route>';
    $strRoute=$strRoute.'<IsDirectRoute>N</IsDirectRoute>';
    $strRoute=$strRoute.'<ErrorCode>413</ErrorCode>';
    $strRoute=$strRoute.'<StartStop>'.$startStop.'</StartStop>';
    $strRoute=$strRoute.'<EndStop>'.$endStop.'</EndStop>';
    $strRoute=$strRoute.'</Route>';
    $strRoute=$strRoute.'</Routes>';
    return $strRoute;
}



?>