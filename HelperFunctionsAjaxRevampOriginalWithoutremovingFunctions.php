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
    $busQuery="select busfrequency.busNumber,frequency from busFrequency,newBusDetails where StopName='".$stopName."'
    and busfrequency.busnumber=newbusdetails.busnumber order by frequency desc";
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

/**
 *getCommonBuses: function to get the buses for the start and teh end point
 * the function will check what should be the stopover point for the buses
 * means what is the combination of the bus to be used and also where to change the bus.
 * Try to avoid the starting points of the bus as the junctions.
 * need to find out which are the most common junctions and try to get the buses for that
 * Also in case multiple buses fulfilling the combination is found then list them all. FOr e.g. take 505, 500K, 500C to Marathatlli and then
 * change to 400, 335E for the Final destination

 * Changes DOne : 26/Dec/2011: Added the conditional Indirect route. So in case you get only one or two direct routes we can offer the
 * user an option to check for the indirect buses also.
 * Comment added : 09/01/2012: Frequency data obtained for few buses. That data can be used to sort the arrays. We can have the sorting algo whihc
 * will take care of the frequency based sorting of the final array.
 * We are taking care of only the distance that is required to walk. Along with that we need to put some weight on the frequency. I feel we should perform some kind
 * of the weighted evealuation of the sorting.
 * In case of the direct bus it is straight forward
 * IN caese of the indirect bus we need to check if both the buses are frequent or not
 * so basically we will check busA+busB.
 * first sort it on the basis of the distances whihc is already done
 * now we will sort them on the basis of sum of the combination frequency. So best number is 4+4=8 and worst is 1+1=2
 * need to add teh frequency data for the missing buses
 * I think the frequecny wise sorting can also be done in the indirectJunction function
 **/

function getCommonBuses($startBuses,$endBuses,$showOnlyIndirectBuses)
{
    // actually the arrays have the last entry as null so 1 less
    $arrSameBus=array();
    for($ii=0;$ii<sizeof($startBuses)-1;$ii++)
    {
        $tempStart=$startBuses[$ii];
        /*for($jj=0;$jj<sizeof($endBuses);$jj++)
        {
            if(strcmp($tempStart,$end
        }*/
        // this checks is the same bus is available in the start buses and end buses.
        if(in_array($tempStart,$endBuses))
        {
            //return $tempStart.":".$tempStart;
            $element=$tempStart.":".$tempStart;
            array_push($arrSameBus,$element);
        }

    }


    if(sizeof($arrSameBus)>0 && $showOnlyIndirectBuses==0)
    {
        /*echo "before sort <br/>";
        print_r($arrSameBus);
        echo "after sorting <br/>";
        $sortedArray= sortBasedOnBusFrequency($arrSameBus);
        print_r($sortedArray);*/
        return sortBasedOnBusFrequency($arrSameBus); // at this point we can sort it out based on the frequency of the buses and also let the know the meaning of the frequency
    }

    // in case you need to get the indirect buses forcefully then remove the common buses from the array of the $startBuses and $endBuses

    for($i=0;$i<sizeof($arrSameBus);$i++)
    {
        $temp=explode(":",$arrSameBus[$i]);
        $key=array_search($temp[0],$startBuses);
        unset($startBuses[$key]);
        $startBuses=array_values($startBuses);

    }

    for($i=0;$i<sizeof($arrSameBus);$i++)
    {
        $temp=explode(":",$arrSameBus[$i]);
        $key=array_search($temp[0],$endBuses);
        unset($endBuses[$key]);
        $endBuses=array_values($endBuses);

    }

    //return $startBuses;

    // if the common bus is not found
    //echo "no comon bus found";
    // in case the common bus is not found we need to see what is the distance that the user has to walk and the
    // stop from where he can get the bus. That will also determin the choice of the bus. If the distance to walk is more then
    //consider other bus.
    //echo "jjj";
    $indirectBusDistances=array();
    for($i=0;$i<sizeof($startBuses)-1;$i++)
    {
        $firstBus=$startBuses[$i];
        $minimum=10000;
        for($j=0;$j<sizeof($endBuses)-1;$j++)
        {
            $secondBus=$endBuses[$j];
            // i guess with the change in the data contained in the intermediate stop table we need to change this
            list($firstBusNumber,$firstRouteNumber,$firstBusStop,$secondBusNumber,$secondRouteNumber,$secondBusStop,$distance)=explode(":",getIntermediateStopsAndDistance($firstBus,$secondBus));
            if($distance<=$minimum)
            {
                $minimum=$distance;
                $element=new IndirectBusStructure($firstBus,$secondBus,$distance);
                array_push($indirectBusDistances,$element);
            }


        }

    }

    $sortedIndirectBuses=BubbleSort($indirectBusDistances,sizeof($indirectBusDistances));
    //print_r($sortedIndirectBuses);
    // create an array that will contain the various combinations as found
    $arrBuses=array();
    $arrBIASBuses=array();	// array to collect the BIAS BIAS combination
    // try to avoid the BIAS and BIAS combination.

    for($k=0;$k<sizeof($sortedIndirectBuses);$k++)
    {
        //echo $sortedIndirectBuses[$k]->getFirstBus();
        $pos1=strpos($sortedIndirectBuses[$k]->getFirstBus(),'BIAS');
        $pos2=strpos($sortedIndirectBuses[$k]->getSecondBus(),'BIAS');
        //echo $pos1.$pos2;
        if($pos1!==false && $pos2!==false)
        {
            $element=$sortedIndirectBuses[$k]->getFirstBus().":".$sortedIndirectBuses[$k]->getSecondBus();
            //echo $element;
            array_push($arrBIASBuses,$element);

        }//continue;
        else
        {
            //return $sortedIndirectBuses[$k]->getFirstBus().":".$sortedIndirectBuses[$k]->getSecondBus();
            //echo "dskdjsjd";
            $element=$sortedIndirectBuses[$k]->getFirstBus().":".$sortedIndirectBuses[$k]->getSecondBus();
            //echo $element;
            array_push($arrBuses,$element);

        }
    }
    // if BIAS and BIAS is the only combination then go for it
    //return $startBuses[0].":".$endBuses[0];
    if(sizeof($arrBuses)>0)
    {
        //print_r($arrBuses);
        return sortBasedOnBusFrequency($arrBuses);
    }
    else
    {
        //return $sortedIndirectBuses[0]->getFirstBus().":".$sortedIndirectBuses[0]->getSecondBus();
        //print_r($arrBIASBuses);
        return sortBasedOnBusFrequency($arrBIASBuses);
    }

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
 * getRouteName: This function is used to return the route number based on teh bus number
 **/
function getRouteName($busNumber)
{
    $query="Select RouteName From BusRouteNumbers Where BusNumber='".$busNumber."'";
    $result=mysql_query($query);
    $row=mysql_fetch_row($result);
    return $row[0];
}

/**
 * getIntermediateStopsAndDistance: given the first bus and the end bus it will return the intermediate stops and the distance between them
TODO: I think we sh0ould find the intermediate stops two time once normal and then swap the buses
 **/
function getIntermediateStopsAndDistance($firstBus,$endBus)
{
    // need to deal with the Ruote number 21 and 43. buses 356M and 365P and Route 43 V319C,V335E
    $query="Select FirstBusNumber, FirstRouteName, FirstBusRouteStop,
	SecondBusNumber, SecondRouteName, SecondBusRouteStop, Distance from  IntermediateStops where FirstBusNumber='".$firstBus."' and SecondBusNumber='".$endBus."'";
    //echo $query;
    $result=mysql_query($query);
    $row=mysql_fetch_row($result);
    return $row[0].":".$row[1].":".$row[2].":".$row[3].":".$row[4].":".$row[5].":".$row[6];


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


/**
 * The function should be doing the following
 * 1. Find the buses that pass from the source
 * 2. Find the buses that pass from the destination
 * 3. Get the buses that are direct buses whihc are the ones that pass through the source and the destination. (deprioritse the BIA and g)
 * 4. For th edirect buses also the sorting order should be based on the distance
 * 5. In case of the indirect routes
 * 6. Use the array that was created for the source and destination buses
 * 7. Now for each bus find the intermediate junction points: To do so as per the new table we need to know whether it is the lower
 * junction or the higher junction with respect to the source bus so find disatce (source,lower junction) versus dis(source, higher junctio)
 * based on the outcome we need to get the secon bus higher or lower junction
 * We basically need the following
 * Lower/Higher FirstJunction: Lower/Higher Second Junction:frequency for the bus on each junction: distance between the junction
 * First need to sort on the basis of the distance + the BIA and G buses. Later we need to bring in the frequency considerations *
 * There are few scenarios that we have to take care
 * 1. when you have a single junction
 * 2. When you have two junction which means the distance is not zero between the first junction & the second junction
 * 3. When the junction is near to the destnation so you just have to walk from the junction
 *
 *
 */

//Need to be filled in
function getJunctionsForIndirectBusesRevamp($startStop,$endStop,$startBuses,$endBuses,$startOffsetDistance,$endOffsetDistance,$showOnlyIndirectBuses)
{


    //get the buses that are direct
    // actually the arrays have the last entry as null so 1 less
    $arrSameBus=array();
    $numStartBuses=sizeof($startBuses);
    for($ii=0;$ii<$numStartBuses-1;$ii++)
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
    if(sizeof($arrSameBus)>0 && $showOnlyIndirectBuses==0)
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
        echo "found the direct buses";
        return displayDirectBuses($sortedDirectBuses,$directDistance);


    }
    else
    {
        echo "need to get indirect routes";
        $arrIndirectBuses=array();
        //print_r($startBuses);
        //print_r($endBuses);
        //need to find the junctions
        $indirectBusDistances=array();
        $numStartBuses=sizeof($startBuses);
        $numEndBuses=sizeof($endBuses);
        for($i=0;$i<$numStartBuses;$i++)
        {
            $firstBus=$startBuses[$i];
            $minimum=10000;
            for($j=0;$j<$numEndBuses;$j++)
            {
                $secondBus=$endBuses[$j];
                //echo "secondBus".$secondBus;
                //find the entry from the table.
                list($firstBusNumber,$firstBusFrequency,$firstBusStartPoint,$secondBusNumber,$secondBusFrequency,
                    $firstBusLowerJunction,$secondBusLowerJunction,$distLower,$lowerJunctionFrequency,$firstBusHigherJunction,
                    $secondBusHigherJunction,$distHigher,$higherJunctionFrequency)=
                    explode(":",getIntermediateStopsAndDistanceWithFrequency($firstBus,$secondBus));

                /* if($firstBusNumber=='V500W')
                 {
                     echo "hhhh";
                     echo getIntermediateStopsAndDistanceWithFrequency($firstBus,$secondBus);
                     exit();
                 }*/

                //return;
                //need to find which junction is valid for the source stop
                if($firstBusLowerJunction!=$firstBusHigherJunction)
                {

                    if(distanceBetweenStops($firstBusStartPoint,$firstBusLowerJunction)<distanceBetweenStops($firstBusStartPoint,$firstBusHigherJunction))
                    {
                        echo "inside lower <br/>";
                        $junction1=$firstBusLowerJunction;
                        $junction2=$secondBusLowerJunction;
                        $junctionFrequency=$lowerJunctionFrequency;
                        $dist=$distLower;
                        $totalRouteDistance=getTotalRouteDistance($startStop,$junction1,$junction2,$dist,$endStop,$startOffsetDistance,$endOffsetDistance);

                    }
                    else
                    {
                        echo "inside higher <br/>";
                        $junction1=$firstBusHigherJunction;
                        $junction2=$secondBusHigherJunction;
                        $junctionFrequency=$higherJunctionFrequency;
                        $dist=$distHigher;
                        $totalRouteDistance=getTotalRouteDistance($startStop,$junction1,$junction2,$dist,$endStop,$startOffsetDistance,$endOffsetDistance);
                    }
                }
                else
                {
                    echo "inside common <br/>";
                    $junction1=$firstBusLowerJunction;
                    $junction2=$secondBusLowerJunction;
                    $junctionFrequency=$lowerJunctionFrequency;
                    $dist=$distLower;
                    $totalRouteDistance=getTotalRouteDistance($startStop,$junction1,$junction2,$dist,$endStop,$startOffsetDistance,$endOffsetDistance);
                }

                //collective frequency= sum(firstbus frequency, second bus frequency)
                //may be buses are high frequency but the junction are seperataed
                //&& $firstBusFrequency >0 && $secondBusFrequency >0
                if($dist<(float)0.8 )
                {
                    if($dist<=(float)0.05)
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
                }

                //based on the junction create an array as
                // firstbus:freq:$junction

            }


        }

        //  echo "<br/>"."The raw indirect buses <br/>";
        //  print_r($arrIndirectBuses);

        $finalIndirectBuses=array();
        $tempHighDistanceBuses=array();
        $midFrequencyBuses=array();
        //sort the array on the basis of the collective frequency which is frequency of the buses
        $sortedIndirectBuses=sortBasedOnCollectiveFrequency($arrIndirectBuses,sizeof($arrIndirectBuses));
        echo "After Sorting on the bus frequency<br/>";
        // print_r($sortedIndirectBuses);
        for($ii=0;$ii<sizeof($sortedIndirectBuses);$ii++)
        {
            echo "firstBus..".$sortedIndirectBuses[$ii]->getFirstBus().",FirstBusFreq..".$sortedIndirectBuses[$ii]->getFirstBusFrequency().
                ",SeconBus..".$sortedIndirectBuses[$ii]->getSecondBus().",SecondBusFreq..".$sortedIndirectBuses[$ii]->getSecondBusFrequency().
                ",Junction1..".$sortedIndirectBuses[$ii]->getJunction1().",junction2...".$sortedIndirectBuses[$ii]->getJunction2().
                ", junctionFrequency..".$sortedIndirectBuses[$ii]->getJunctionFrequency().", distancebtw junction..".$sortedIndirectBuses[$ii]->getDistanceBetweenJunctions().
                ", collective Frequncy (b1+b2-dp)..".$sortedIndirectBuses[$ii]->getCollectiveFrequency().
                ", total distance..".$sortedIndirectBuses[$ii]->getTotalRouteDistance()."<br/>";
        }

        //sort the buses based on the total distance
        $sortedIndirectBuses=sortBasedOnTotalRouteDistance($sortedIndirectBuses,sizeof($sortedIndirectBuses));

        echo "after sorting based on the total route distnace <br/>";
        for($ii=0;$ii<sizeof($sortedIndirectBuses);$ii++)
        {
            echo "firstBus..".$sortedIndirectBuses[$ii]->getFirstBus().",FirstBusFreq..".$sortedIndirectBuses[$ii]->getFirstBusFrequency().
                ",SeconBus..".$sortedIndirectBuses[$ii]->getSecondBus().",SecondBusFreq..".$sortedIndirectBuses[$ii]->getSecondBusFrequency().
                ",Junction1..".$sortedIndirectBuses[$ii]->getJunction1().",junction2...".$sortedIndirectBuses[$ii]->getJunction2().
                ", junctionFrequency..".$sortedIndirectBuses[$ii]->getJunctionFrequency().", distancebtw junction..".$sortedIndirectBuses[$ii]->getDistanceBetweenJunctions().
                ", collective Frequncy (b1+b2-dp)..".$sortedIndirectBuses[$ii]->getCollectiveFrequency().
                ", total distance..".$sortedIndirectBuses[$ii]->getTotalRouteDistance()."<br/>";
        }





        //if the collective frequency is 8,7,6 then it doesnot matter waht the junction frequency is. Also this will take care of BIAS
        $lenIndirect=sizeof($sortedIndirectBuses);
        $lastEnteredIndex=0;
        for($i=0;$i<$lenIndirect;$i++)
        {
            if($sortedIndirectBuses[$i]->getCollectiveFrequency()>10)
            {
                array_push($finalIndirectBuses,$sortedIndirectBuses[$i]);
                $lastEnteredIndex++;
            }

        }

        echo "<br/>Displaying the high bus frequency <br/>";
        //print_r($finalIndirectBuses);



        //now for the remaining buses remove the buses where you need to walk
        for($i=$lastEnteredIndex;$i<$lenIndirect;$i++)
        {
            if($sortedIndirectBuses[$i]->getDistanceBetweenJunctions()>.3)
            {
                array_push($tempHighDistanceBuses,$sortedIndirectBuses[$i]);
            }
            else
            {

                $sortedIndirectBuses[$i]->setCollectiveFrequency($sortedIndirectBuses[$i]->getJunctionFrequency());
                array_push($midFrequencyBuses,$sortedIndirectBuses[$i]);
            }
        }

        //sort the buses on the basis of the junction frequency
        //$sortedBasedOnJunctionFrequency=array();
        $sortedBasedOnJunctionFrequency=sortBasedOnCollectiveFrequency($midFrequencyBuses,sizeof($midFrequencyBuses));
        echo "<br/>the sotered on Junction Frequency<br/>";
        //print_r($sortedBasedOnJunctionFrequency);

        //finally sort the buses on the basis of the distance
        $sortedBasedOnDistanceBetweenJunction=sortBasedOnJunctionDistances($tempHighDistanceBuses,sizeof($tempHighDistanceBuses));
        echo "<br/>the options sorthed on the distncae<br/>";
        // print_r($sortedBasedOnDistanceBetweenJunction);


    }

    /*

    // in case you need to get the indirect buses forcefully then remove the common buses from the array of the $startBuses and $endBuses
    for($i=0;$i<sizeof($arrSameBus);$i++)
    {
        $temp=explode(":",$arrSameBus[$i]);
        $key=array_search($temp[0],$startBuses);
        unset($startBuses[$key]);
        $startBuses=array_values($startBuses);

    }

    for($i=0;$i<sizeof($arrSameBus);$i++)
    {
        $temp=explode(":",$arrSameBus[$i]);
        $key=array_search($temp[0],$endBuses);
        unset($endBuses[$key]);
        $endBuses=array_values($endBuses);

    }

    //return $startBuses;

    // if the common bus is not found
    //echo "no comon bus found";
    // in case the common bus is not found we need to see what is the distance that the user has to walk and the
    // stop from where he can get the bus. That will also determin the choice of the bus. If the distance to walk is more then
    //consider other bus.
    //echo "jjj";
    $indirectBusDistances=array();
    for($i=0;$i<sizeof($startBuses)-1;$i++)
    {
        $firstBus=$startBuses[$i];
        $minimum=10000;
        for($j=0;$j<sizeof($endBuses)-1;$j++)
        {
            $secondBus=$endBuses[$j];
            // i guess with the change in the data contained in the intermediate stop table we need to change this
            list($firstBusNumber,$firstRouteNumber,$firstBusStop,$secondBusNumber,$secondRouteNumber,$secondBusStop,$distance)=explode(":",getIntermediateStopsAndDistance($firstBus,$secondBus));
            if($distance<=$minimum)
            {
                $minimum=$distance;
                $element=new IndirectBusStructure($firstBus,$secondBus,$distance);
                array_push($indirectBusDistances,$element);
            }


        }

    }

    $sortedIndirectBuses=BubbleSort($indirectBusDistances,sizeof($indirectBusDistances));
    //print_r($sortedIndirectBuses);
    // create an array that will contain the various combinations as found
    $arrBuses=array();
    $arrBIASBuses=array();	// array to collect the BIAS BIAS combination
    // try to avoid the BIAS and BIAS combination.

    for($k=0;$k<sizeof($sortedIndirectBuses);$k++)
    {
        //echo $sortedIndirectBuses[$k]->getFirstBus();
        $pos1=strpos($sortedIndirectBuses[$k]->getFirstBus(),'BIAS');
        $pos2=strpos($sortedIndirectBuses[$k]->getSecondBus(),'BIAS');
        //echo $pos1.$pos2;
        if($pos1!==false && $pos2!==false)
        {
            $element=$sortedIndirectBuses[$k]->getFirstBus().":".$sortedIndirectBuses[$k]->getSecondBus();
            //echo $element;
            array_push($arrBIASBuses,$element);

        }//continue;
        else
        {
            //return $sortedIndirectBuses[$k]->getFirstBus().":".$sortedIndirectBuses[$k]->getSecondBus();
            //echo "dskdjsjd";
            $element=$sortedIndirectBuses[$k]->getFirstBus().":".$sortedIndirectBuses[$k]->getSecondBus();
            //echo $element;
            array_push($arrBuses,$element);

        }
    }
    // if BIAS and BIAS is the only combination then go for it
    //return $startBuses[0].":".$endBuses[0];
    if(sizeof($arrBuses)>0)
    {
        //print_r($arrBuses);
        return sortBasedOnBusFrequency($arrBuses);
    }
    else
    {
        //return $sortedIndirectBuses[0]->getFirstBus().":".$sortedIndirectBuses[0]->getSecondBus();
        //print_r($arrBIASBuses);
        return sortBasedOnBusFrequency($arrBIASBuses);
    }

    */






}



//If the database is in place then the call should be
function getBusRoutesForRendering($startStop,$endStop,$startOffsetDistance,$endOffsetDistance,$showOnlyIndirectRoutes)
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
    $status= getJunctionsForIndirectBusesRevamp2($startStop,$endStop,$startBuses,$endBuses,$startOffsetDistance,$endOffsetDistance,$showOnlyIndirectRoutes);

    if($status=="404" || $status=="405")
    {
        $arrRows=array();
        $arrDirectRows=array();
        //I am assuming that for the given source and end there is only one type of depot error code. So it can be either 7,8,9,10 but not both
        //check the database for the error code and output
        $sql="Select * from depotbusroutes where SourceStartStop='".$startStop."' and DestinationEndStop='".$endStop."'";
        $result=mysql_query($sql);
        $rowsnum=mysql_num_rows($result);
        if($rowsnum==0)
        {
            //check in the other database if the data is present over there
            $sqlDirect="Select * from directdepotbusroutes where StartStop='".$startStop."' and EndStop='".$endStop."'";
            $resultDirect=mysql_query($sqlDirect);
            $rowsnumDirect=mysql_num_rows($resultDirect);
            if($rowsnumDirect==0)
                return "411";
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
                        return $strRoute;

                    }
                    else if($rowDirect->DepotErrorCode=="6"||$rowDirect->DepotErrorCode=="7"||$rowDirect->DepotErrorCode=="8")
                    {
                        array_push($arrDirectRows,$rowDirect);
                       // print_r($arrDirectRows);

                    }
                    else
                    {
                        echo "dsdstbd";
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
                    return $strRoute;
                }
                else if($row->DepotErrorCode=="10"||$row->DepotErrorCode=="9")
                {
                    array_push($arrRows,$row);
                }
                else
                {
                    echo "tbd";
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

function getJunctionsForIndirectBusesRevamp2($startStop,$endStop,$startBuses,$endBuses,$startOffsetDistance,
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
        //echo "numstart".$numStartBuses;
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


                //return;
                //need to find which junction is valid for the source stop
                if($firstBusLowerJunction!=$firstBusHigherJunction)
                {

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
                        //echo "inside higher <br/>";
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
                        // also chekc that the endstop or the start stop is not same as the juntion as in case of marathahalli bridge and multiplex
                        if(strcmp($junction1,$startStop)==0 ||
                            strcmp($junction1,$endStop)==0 ||
                            strcmp($junction2,$startStop)==0 ||
                            strcmp($junction2,$endStop)==0)
                        {
                            //echo "in the pit";
                            continue;
                        }
                        else
                        {
                            $junctionString=$junction1.":".$junction2.":".$dist;
                            //echo "distashdg".$dist;
                            //$junctionString,$totalDistance,$startStop,$endStop,$junction1,$junction2,$junctionDistance
                            $element=new DisplaySortedJunctionsData($junctionString,$totalRouteDistance,$startStop,$endStop,
                                $junction1,$junction2,$dist);
                            //echo "element".$i.",".$j."<br/>";
                            //	print_r($element);
                            array_push($displayJunctionsDataArray,$element);
                            //array_push($arrayUniqueJunctions,$element);
                        }
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
        //print_r($displayJunctionsDataArray);
        $SortedJunctionsDataArray=sortBasedOnTotalRouteDistance($displayJunctionsDataArray);
        // print_r($SortedJunctionsDataArray);


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


            $sizeofSortedJunctions=sizeof($SortedJunctionsDataArray);


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

                //get all the buses for second jun..
                // if the distance between the two junctions is 0
                if($distanceJn==0 && strcmp($firstJnName,$startStop)!=0)
                {
                    $getCommonBusesForFirstJunction=$SortedJunctionsDataArray[$i]->getStartBusString();//getBusesCommonBetweenTwoStops($startStop,$firstJnName);
                    $getCommonBusesForSecondJunction=$SortedJunctionsDataArray[$i]->getEndBusString();//getBusesCommonBetweenTwoStops($secondJnName,$endStop);
                    if($junction1Frequency>0 && $junction2Frequency>0)
                    {
                        $strRoute=$strRoute.'<ErrorCode>1</ErrorCode>';
                        /*
                        $routeDetail=htmlentities($startStop).":".$getCommonBusesForFirstJunction.":";
                        $routeDetail=$routeDetail.htmlentities($firstJnName).":".getLatitudeLongitude($firstJnName).":0:";
                        $routeDetail=$routeDetail.htmlentities($firstJnName).":".getLatitudeLongitude($firstJnName).":".$getCommonBusesForSecondJunction.":";
                        $routeDetail=$routeDetail.htmlentities($endStop);
                        */
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
                        //echo "False positive";
                        //$strRoute=$strRoute.'<ErrorCode>6</ErrorCode>';
                        $FalseAlarmCounter++;
                    }

                }
                else
                {
                    // if the end point is same as the second junction it may happend that you juct have to walk from the first junction to the end point
                    if(strcmp($secondJnName,$endStop)==0)
                    {
                        //check once this thing... I dont think that we will hit this condition. Alos why are we finding bus
                        //betweem second junction and endpoint
                        $strRoute=$strRoute.'<ErrorCode>2</ErrorCode>';
                        $routeDetail=htmlentities($startStop).":".getBusesCommonBetweenTwoStops($startStop,$firstJnName).":";
                        $routeDetail=$routeDetail.htmlentities($firstJnName).":".getLatitudeLongitude($firstJnName).":".$distanceJn.":";
                        $routeDetail=$routeDetail.htmlentities($firstJnName).":".getLatitudeLongitude($firstJnName).":".getBusesCommonBetweenTwoStops($secondJnName,$endStop).":";
                        $routeDetail=$routeDetail.htmlentities($endStop);

                    }
                    else
                    {
                        //$getCommonBusesForFirstJunction=getBusesCommonBetweenTwoStops($startStop,$firstJnName);
                        //$getCommonBusesForSecondJunction=getBusesCommonBetweenTwoStops($secondJnName,$endStop);
                        $getCommonBusesForFirstJunction=$SortedJunctionsDataArray[$i]->getStartBusString();//getBusesCommonBetweenTwoStops($startStop,$firstJnName);
                        $getCommonBusesForSecondJunction=$SortedJunctionsDataArray[$i]->getEndBusString();
                        if($junction1Frequency>0 && $junction2Frequency>0)
                        {
                            $strRoute=$strRoute.'<ErrorCode>3</ErrorCode>';
                            /*$routeDetail=htmlentities($startStop).":".$getCommonBusesForFirstJunction.":";
                            $routeDetail=$routeDetail.htmlentities($firstJnName).":".getLatitudeLongitude($firstJnName).":".$distanceJn.":";
                            $routeDetail=$routeDetail.htmlentities($secondJnName).":".getLatitudeLongitude($secondJnName).":".$getCommonBusesForSecondJunction.":";
                            $routeDetail=$routeDetail.htmlentities($endStop);
                            */
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
                            // echo "False positive";
                            $FalseAlarmCounter++;
                        }

                    }
                }

                //echo "The approximate route distance="."<b>".$SortedJunctionsDataArray[$i]->getDistance()."KM</b>";
                //$routeDetail=$routeDetail.":".$SortedJunctionsDataArray[$i]->getTotalRouteDistance();
                $routeDetail=$routeDetail."<TotalRouteDistance>".$SortedJunctionsDataArray[$i]->getTotalRouteDistance()."</TotalRouteDistance>";
                $routeDetail=$routeDetail."<UseDepot>".$useDepot."</UseDepot>";

                $strRoute=$strRoute.'<RouteDetails>'.$routeDetail.'</RouteDetails>';
                //echo "<hr/>";
                $strRoute=$strRoute.'</Route>';
                //echo $strRoute;

            }
            $strRoute=$strRoute.'</Routes>';
            // check if every thing was wrong that means we need to send the erro codes.
            if($FalseAlarmCounter==sizeof($SortedJunctionsDataArray))
            {
                /*$strRoute='<Routes>';
                $strRoute=$strRoute.'<Route>';
                $strRoute=$strRoute.'<IsDirectRoute>N</IsDirectRoute>';
                $strRoute=$strRoute.'<ErrorCode>4</ErrorCode>';
                $strRoute=$strRoute.'</Route>';
                $strRoute=$strRoute.'</Routes>';
                */
                return "405";
            }

        }
        // echo "---------------------------------<br/>";

        // echo $strRoute;
        return $strRoute;

    }
}


function getJunctionsForIndirectBusesRevamp3($startStop,$endStop,$startBuses,$endBuses,$startOffsetDistance,
                                             $endOffsetDistance,$showOnlyIndirectBuses,$useDepot="0",$distanceCoveredToDepotFromCorrespondingStop=0)
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
        $directDistance=getDirectBusDistance($startStop,$endStop,$startOffsetDistance,$endOffsetDistance);
        // $sortedDirectBuses=sortBusesBasedOnDistanceToDestination($arrSameBus);
        $sortedDirectBuses=sortBasedOnBusFrequency($arrSameBus); // at this point we can sort it out based on the frequency of the buses and also let the know the meaning of the frequency
        $sortedDirectBuses=applyBIAFilter($sortedDirectBuses);
        //echo "found the direct buses";
        if($useDepot=="1")
            return displayDirectBusesForDepot($sortedDirectBuses,$directDistance);
        else
            return displayDirectBusesWithoutFormatting($sortedDirectBuses,$directDistance);
        /***** need to chekc how to return the direct bus information ...our assumption is that you can always reach to the depot using the
        direct buses. So the first call for the >7 cases will be to get the direct buses between the stop and the depot.
        once you get that information then you will see what is the indirect case.


         ***/
    }
    // indirect buses
    else
    {
        $arrFirstJunctions=array();
        $arrSecondJunctions=array();
        $arrFirstBuses=array();
        $arrSecondBuses=array();

        $numEndBuses=sizeof($endBuses);
        $displayJunctionsDataArray=array();
        if($numStartBuses >10)
            $numStartBuses=10;
        if($numEndBuses > 10)
            $numEndBuses=10;
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

                //need to find which junction is valid for the source stop
                if($firstBusLowerJunction!=$firstBusHigherJunction)
                {

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
                        //echo "inside higher <br/>";
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
                    $junctionFrequency=$lowerJunctionFrequency;
                    $dist=$distLower;
                    $totalRouteDistance=getTotalRouteDistance($startStop,$junction1,$junction2,$dist,$endStop,$startOffsetDistance,$endOffsetDistance);
                }

                if($dist<(float)0.8 )
                {
                    if(checkUniqueJunctions($displayJunctionsDataArray,$junction1,$junction2,$dist)==0)
                    {
                        // also chekc that the endstop or the start stop is not same as the juntion as in case of marathahalli bridge and multiplex
                        if(strcmp($junction1,$startStop)==0 ||
                            strcmp($junction1,$endStop)==0 ||
                            strcmp($junction2,$startStop)==0 ||
                            strcmp($junction2,$endStop)==0)
                        {
                            //echo "in the pit";
                            continue;
                        }
                        else
                        {
                            $junctionString=$junction1.":".$junction2.":".$dist;
                            $element=new DisplaySortedJunctionsData($junctionString,$totalRouteDistance,$startStop,$endStop,
                                $junction1,$junction2,$dist);
                            array_push($displayJunctionsDataArray,$element);
                        }
                    }
                }
            }
        }

        $SortedJunctionsDataArray=sortBasedOnTotalRouteDistance($displayJunctionsDataArray);

        // display the results
        $strRoute='';
        if(sizeof($SortedJunctionsDataArray)==0||strlen($SortedJunctionsDataArray[0]->getFirstJunction())==0)
        {
            return "404";
        }

        else
        {
            //$strRoute='<Routes>';
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
            $SortedJunctionsDataArray= prioritizeAndSortJunctionArray($SortedJunctionsDataArray);
            $sizeofSortedJunctions=sizeof($SortedJunctionsDataArray);
            $routeDetail='';
            //speerate xml tag
            for($i=0;$i<$sizeofSortedJunctions;$i++)
            {
                $isCorrectEntry=1;// check if this particular entry is correct. Since the node need to be removed otherwise
                if($i>2)// this will restrict the indirect results to 3
                    break;
                $firstJnName=$SortedJunctionsDataArray[$i]->getFirstJunction();
                $secondJnName=$SortedJunctionsDataArray[$i]->getSecondJunction();
                $distanceJn=$SortedJunctionsDataArray[$i]->getJunctionDistance();
                $junction1Frequency=$SortedJunctionsDataArray[$i]->getFirstJunctionFrequency();
                $junction2Frequency=$SortedJunctionsDataArray[$i]->getSecondJunctionFrequency();
                //get all the buses for firstJn
                $strRoute=$strRoute.'<Route>';
                $strRoute=$strRoute.'<IsDirectRoute>N</IsDirectRoute>';
                //get all the buses for second jun..
                // if the distance between the two junctions is 0
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
                    // if the end point is same as the second junction it may happend that you juct have to walk from the first junction to the end point
                    if(strcmp($secondJnName,$endStop)==0)
                    {
                        //check once this thing... I dont think that we will hit this condition. Alos why are we finding bus
                        //betweem second junction and endpoint
                        $strRoute=$strRoute.'<ErrorCode>2</ErrorCode>';
                        $routeDetail=htmlentities($startStop).":".getBusesCommonBetweenTwoStops($startStop,$firstJnName).":";
                        $routeDetail=$routeDetail.htmlentities($firstJnName).":".getLatitudeLongitude($firstJnName).":".$distanceJn.":";
                        $routeDetail=$routeDetail.htmlentities($firstJnName).":".getLatitudeLongitude($firstJnName).":".getBusesCommonBetweenTwoStops($secondJnName,$endStop).":";
                        $routeDetail=$routeDetail.htmlentities($endStop);

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
                }
                $routeDetail=$routeDetail."<TotalIndirectRouteDistance>".$SortedJunctionsDataArray[$i]->getTotalRouteDistance()."</TotalIndirectRouteDistance>";
                $totalRouteDistance=$distanceCoveredToDepotFromCorrespondingStop+$SortedJunctionsDataArray[$i]->getTotalRouteDistance();
                $routeDetail=$routeDetail."<TotalRouteDistance>".$totalRouteDistance."</TotalRouteDistance>";
                $routeDetail=$routeDetail."<UseDepot>".$useDepot."</UseDepot>";
                $strRoute=$strRoute.'<RouteDetails>'.$routeDetail.'</RouteDetails>';
                $strRoute=$strRoute.'</Route>';
            }
            //$strRoute=$strRoute.'</Routes>';
            // check if every thing was wrong that means we need to send the erro codes.
            if($FalseAlarmCounter==sizeof($SortedJunctionsDataArray))
            {
                return "405";
            }

        }
        $minimumDistance=$SortedJunctionsDataArray[0]->getTotalRouteDistance()+$distanceCoveredToDepotFromCorrespondingStop;
        if($useDepot=="0")
            return $strRoute;
        else
            return $strRoute."^".$minimumDistance;
    }
}

/**
 * This is a special function for the depots.
 * This needs to return the direct or indirect buses between the two depots
 * Important thing is to finalize on the structure of the xml that will be returned.
 * This will return only a subpart of the xml
 * the structure in case of direct bus found between the depots the structure will be
 *<Routes>
<Route>
<IsDirectRoute />
<ErrorCode>9</ErrorCode>
<IsDirectBusesBwDepots>Y</IsDirectBusesBwDepots>
<RouteInfo />
<StartStop/>
<EndStop/>
<FirstDepotNames />
<SecondDepotName />
<BusesStartStopAndDepot />
<BusesInterDepot />
<BusesEndStopAndDepot />
<DistanceBetweenStartStopAndFirstDepot />
<DistanceBetweenDepots />
<DistanceBetweenEndStopAndLastDepot />
<TotalRouteDistance />
<UseDepot />
</Route>
</Routes>
 *
 * In this case the user will be given 3 buses
 *the structure in case of indirect buses found the structure will be
 *<Routes>
<Route>
<IsDirectRoute />
<ErrorCode>10</ErrorCode>
<IsDirectBusesBwDepots>N</IsDirectBusesBwDepots>
<RouteInfo />
<StartStop/>
<EndStop/>
<FirstDepotNames />
<SecondDepotName />
<BusesStartStopAndDepot />
<BusesInterDepot> {for loop..we need to remove the cases where there are two junctions}
<BusesFirstDepotAndJunction></BusesFirstDepotAndJunction>
<JunctionName></JunctionName>
<BusesSecondDepotAndJunction></BusesSecondDepotAndJunction>
<DistanceBetweenFirstDepotAndJunction />
<DistanceBetweenSecondDepotAndJunction />
</BusesInterDepot>
<BusesEndStopAndDepot />
<DistanceBetweenStartStopAndFirstDepot />
<DistanceBetweenEndStopAndLastDepot />
<StartStopOffset />
<EndSopOffset/>
<UseDepot />
</Route>
</Routes>

 * Note: in this case we will end up in 4 buses. I think that is too much but will show this
 * The errror condition and invealid search will give 407 & 408 respectively we will display the errorr that nothing could be found

 * startDepotStop : this is the first depot
 * endDepotStop: This is the second depot
 * startDepotBuses : this is the set of buses which goes to first depot
 * endDepotBuses: this is the set of buses that goes to the second depot
 * startStopStartDepotCommonBusesString: these are the buses that goes between the start stop and the startdepot
 * endDepotEndStopCommonBusesString : these are the buses that are common between the end stop and the end depot
 * startOffsetDistance: This is the offest distacne between the source and the start stop. Here used to calculate the total distance
 * endOffsetDistance: This is the offset distance between the end point and the destnatiuon. here used to calculate the total distance
 * distanceBetweenStartStopAndFirstDepot: The distance between the start stop an dthe first depot
 * distanceBetweenEndStopAndLastDepot : The distcnae between the end point and the last depot

 */

function getJunctionsForInterDepotTravel($startStop,$endStop,$startDepotStop,$endDepotStop,$startDepotBuses,$endDepotBuses,
                                         $startOffsetDistance,$endOffsetDistance,$startStopStartDepotCommonBusesString,
                                         $endDepotEndStopCommonBusesString,$distanceBetweenStartStopAndFirstDepot,
                                         $distanceBetweenEndStopAndLastDepot,$showOnlyIndirectBuses)
{

    $useDepot="1";
    //echo "ddd";

    //get the buses that are direct
    // actually the arrays have the last entry as null so 1 less
    $arrSameBus=array();
    //  print_r($startBuses);
    $numStartBuses=sizeof($startDepotBuses);
    //print_r($endBuses);
    for($ii=0;$ii<$numStartBuses;$ii++)
    {
        $tempStart=$startDepotBuses[$ii];
        // this checks is the same bus is available in the start buses and end buses.
        if(in_array($tempStart,$endDepotBuses))
        {
            $element=$tempStart.":".$tempStart;
            array_push($arrSameBus,$element);
        }
    }
    // print_r($arrSameBus);
    $sortedDirectBuses=array();
    //sort all the direct buses on the basis of frequency and distance of the route. Also see if the direct bus is not a BIAs
    if(sizeof($arrSameBus)>0 && $showOnlyIndirectBuses==0)
    {
        $directDistance=getDirectBusDistance($startDepotStop,$endDepotStop,0,0);

        // $sortedDirectBuses=sortBusesBasedOnDistanceToDestination($arrSameBus);
        $sortedDirectBuses=sortBasedOnBusFrequency($arrSameBus); // at this point we can sort it out based on the frequency of the buses and also let the know the meaning of the frequency
        $sortedDirectBuses=applyBIAFilter($sortedDirectBuses);
        //echo "found the direct buses";
        //return displayDirectBuses($sortedDirectBuses,$directDistance);
        //need to return a sub xml in this place.
        $distanceBetweenDepots=distanceBetweenStops($startDepotStop,$endDepotStop);
        $totalRouteDistance=$startOffsetDistance+$distanceBetweenStartStopAndFirstDepot+$distanceBetweenDepots+$distanceBetweenEndStopAndLastDepot+$endOffsetDistance;

        $routeInfoString="There is no direct or indirect bus availalble between ".$startStop." and ".$endStop.". We have tried to find a route that takes you to a stop where you can find a bus to your destination. However you might have to travel little offroute to reach to the intermediate stop";

        $routeDetails="<Routes>";
        $routeDetails=$routeDetails."<Route>";
        $routeDetails=$routeDetails."<IsDirectRoute>N</IsDirectRoute>";
        $routeDetails=$routeDetails."<ErrorCode>9</ErrorCode>";
        //$routeDetails=$routeDetails."<IsDirectBusesBwDepots>Y</IsDirectBusesBwDepots>";
        $routeDetails=$routeDetails."<RouteInfo>".$routeInfoString."</RouteInfo>";
        $routeDetails=$routeDetails."<RouteDetails>";
        $routeDetails=$routeDetails."<StartStop>".htmlentities($startStop)."</StartStop>";
        $routeDetails=$routeDetails."<EndStop>".htmlentities($endStop)."</EndStop>";
        $routeDetails=$routeDetails."<FirstDepotName>".htmlentities($startDepotStop).":".getLatitudeLongitude($startDepotStop).":".getBusesForStop($startDepotStop)."</FirstDepotName>";
        $routeDetails=$routeDetails."<SecondDepotName>".htmlentities($endDepotStop).":".getLatitudeLongitude($endDepotStop).":".getBusesForStop($endDepotStop)."</SecondDepotName>";
        $routeDetails=$routeDetails."<BusesStartStopAndDepot>".$startStopStartDepotCommonBusesString."</BusesStartStopAndDepot>";
        $routeDetails=$routeDetails."<BusesInterDepot>".implode(",",$sortedDirectBuses)."</BusesInterDepot>";
        $routeDetails=$routeDetails."<BusesEndStopAndDepot>".$endDepotEndStopCommonBusesString."</BusesEndStopAndDepot>";
        $routeDetails=$routeDetails."<DistanceBetweenStartStopAndFirstDepot>".$distanceBetweenStartStopAndFirstDepot."</DistanceBetweenStartStopAndFirstDepot>";
        $routeDetails=$routeDetails."<DistanceBetweenEndStopAndLastDepot>".$distanceBetweenEndStopAndLastDepot."</DistanceBetweenEndStopAndLastDepot>";
        $routeDetails=$routeDetails."<DistanceBetweenDepots>".$distanceBetweenDepots."</DistanceBetweenDepots>";
        $routeDetails=$routeDetails."<TotalRouteDistance>".$totalRouteDistance."</TotalRouteDistance>";
        $routeDetails=$routeDetails."</RouteDetails>";
        $routeDetails=$routeDetails."</Route>";
        $routeDetails=$routeDetails."</Routes>";
        return $routeDetails;
    }
    // indirect buses
    else
    {
        $arrFirstJunctions=array();
        $arrSecondJunctions=array();
        $arrFirstBuses=array();
        $arrSecondBuses=array();

        $numEndBuses=sizeof($endDepotBuses);
        $displayJunctionsDataArray=array();
        if($numStartBuses >10)
            $numStartBuses=10;
        if($numEndBuses > 10)
            $numEndBuses=10;
        for($i=0;$i<$numStartBuses;$i++)
        {
            $firstBus=$startDepotBuses[$i];
            $minimum=10000;
            for($j=0;$j<$numEndBuses;$j++)
            {
                $secondBus=$endDepotBuses[$j];
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


                //return;
                //need to find which junction is valid for the source stop
                if($firstBusLowerJunction!=$firstBusHigherJunction)
                {

                    if(distanceBetweenStops($firstBusStartPoint,$firstBusLowerJunction)<distanceBetweenStops($firstBusStartPoint,$firstBusHigherJunction))
                    {
                        // echo "inside lower <br/>";
                        $junction1=$firstBusLowerJunction;
                        $junction2=$secondBusLowerJunction;
                        $junctionFrequency=$lowerJunctionFrequency;
                        $dist=$distLower;
                        $totalRouteDistance=getTotalRouteDistance($startDepotStop,$junction1,$junction2,$dist,$endDepotStop,0,0);
                        //echo $totalRouteDistance."<br/>";
                    }
                    else
                    {
                        //echo "inside higher <br/>";
                        $junction1=$firstBusHigherJunction;
                        $junction2=$secondBusHigherJunction;
                        $junctionFrequency=$higherJunctionFrequency;
                        $dist=$distHigher;
                        $totalRouteDistance=getTotalRouteDistance($startDepotStop,$junction1,$junction2,$dist,$endDepotStop,0,0);
                    }
                }
                else
                {
                    //echo "inside common <br/>";
                    $junction1=$firstBusLowerJunction;
                    $junction2=$secondBusLowerJunction;
                    $junctionFrequency=$lowerJunctionFrequency;
                    $dist=$distLower;
                    $totalRouteDistance=getTotalRouteDistance($startDepotStop,$junction1,$junction2,$dist,$endDepotStop,0,0);
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
                        // also chekc that the endstop or the start stop is not same as the juntion as in case of marathahalli bridge and multiplex
                        if(strcmp($junction1,$startDepotStop)==0 ||
                            strcmp($junction1,$endDepotStop)==0 ||
                            strcmp($junction2,$startDepotStop)==0 ||
                            strcmp($junction2,$endDepotStop)==0)
                        {
                            //echo "in the pit";
                            continue;
                        }
                        else
                        {
                            $junctionString=$junction1.":".$junction2.":".$dist;
                            //echo "distashdg".$dist;
                            //$junctionString,$totalDistance,$startStop,$endStop,$junction1,$junction2,$junctionDistance
                            $element=new DisplaySortedJunctionsData($junctionString,$totalRouteDistance,$startStop,$endStop,
                                $junction1,$junction2,$dist);
                            //echo "element".$i.",".$j."<br/>";
                            //	print_r($element);
                            array_push($displayJunctionsDataArray,$element);
                            //array_push($arrayUniqueJunctions,$element);
                        }
                    }
                }
            }
        }

        $SortedJunctionsDataArray=sortBasedOnTotalRouteDistance($displayJunctionsDataArray);

        // display the results
        $strRoute='';
        if(sizeof($SortedJunctionsDataArray)==0||strlen($SortedJunctionsDataArray[0]->getFirstJunction())==0)
        {
            //echo "inside 409";
            return "409";
        }

        else
        {
            $strRoute='<Routes>';
            // there can be chances that when we are filling the xml all the details are not properly found. In that case we need to
            // remove that entry. If at the end this check counter becomes equals to the sizeof($SortedJunctionsDataArray). It means every entry had a problem
            // and we will return the error code 4
            $FalseAlarmCounter=0;
            $sizeofSortedJunctions=sizeof($SortedJunctionsDataArray);
            //populate a datastructure
            for($i=0;$i<$sizeofSortedJunctions;$i++)
            {
                list($firstJnName,$secondJnName,$distanceJn)=explode(":",$SortedJunctionsDataArray[$i]->getJunctionString());
                $getCommonBusesForFirstJunctionArray=getBusesCommonBetweenTwoStops($startDepotStop,$firstJnName);
                $getCommonBusesForSecondJunctionArray=getBusesCommonBetweenTwoStops($secondJnName,$endDepotStop);
                $junction1Frequency=sizeof($getCommonBusesForFirstJunctionArray);
                $junction2Frequency=sizeof($getCommonBusesForSecondJunctionArray);
                $getCommonBusesForFirstJunction=implode(",",$getCommonBusesForFirstJunctionArray);
                $getCommonBusesForSecondJunction=implode(",",$getCommonBusesForSecondJunctionArray);
                $SortedJunctionsDataArray[$i]->setFirstJunctionFrequency($junction1Frequency);
                $SortedJunctionsDataArray[$i]->setSecondJunctionFrequency($junction2Frequency);
                $SortedJunctionsDataArray[$i]->setStartBusString($getCommonBusesForFirstJunction);
                $SortedJunctionsDataArray[$i]->setEndBusString($getCommonBusesForSecondJunction);

            }
            $SortedJunctionsDataArray= prioritizeAndSortJunctionArray($SortedJunctionsDataArray);
            $sizeofSortedJunctions=sizeof($SortedJunctionsDataArray);

            $routeInfoString="There is no direct or indirect bus availalble between ".$startStop." and ".$endStop.". We have tried to find a route that ".
                "takes you to a stop where you can find a bus to your destination. However you might have to travel little offroute to reach to the intermediate stop";


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

                //get all the buses for second jun..
                // if the distance between the two junctions is 0
                if($distanceJn==0 && strcmp($firstJnName,$startDepotStop)!=0)
                {
                    $getCommonBusesForFirstJunction=$SortedJunctionsDataArray[$i]->getStartBusString();//getBusesCommonBetweenTwoStops($startStop,$firstJnName);
                    $getCommonBusesForSecondJunction=$SortedJunctionsDataArray[$i]->getEndBusString();//getBusesCommonBetweenTwoStops($secondJnName,$endStop);
                    if($junction1Frequency>0 && $junction2Frequency>0)
                    {
                        $strRoute=$strRoute.'<Route>';
                        $strRoute=$strRoute.'<IsDirectRoute>N</IsDirectRoute>';
                        $strRoute=$strRoute.'<ErrorCode>10</ErrorCode>';
                        //$strRoute=$strRoute.'<IsDirectBusesBwDepots>N</IsDirectBusesBwDepots>';
                        $strRoute=$strRoute."<RouteInfo>".$routeInfoString."</RouteInfo>";
                        $routeDetail='';
                        $routeDetail=$routeDetail."<StartStop>".htmlentities($startStop)."</StartStop>";
                        $routeDetail=$routeDetail."<EndStop>".htmlentities($endStop)."</EndStop>";
                        $routeDetail=$routeDetail."<FirstDepotName>".htmlentities($startDepotStop).":".getLatitudeLongitude($startDepotStop).":".getBusesForStop($startDepotStop)."</FirstDepotName>";
                        $routeDetail=$routeDetail."<SecondDepotName>".htmlentities($endDepotStop).":".getLatitudeLongitude($endDepotStop).":".getBusesForStop($endDepotStop)."</SecondDepotName>";
                        $routeDetail=$routeDetail."<BusesStartStopAndDepot>".$startStopStartDepotCommonBusesString."</BusesStartStopAndDepot>";
                        $routeDetail=$routeDetail."<FirstJunction>".htmlentities($firstJnName).":".getLatitudeLongitude($firstJnName).":".getBusesForStop($firstJnName)."</FirstJunction>";
                        $routeDetail=$routeDetail."<DistanceBetweenJunction>0</DistanceBetweenJunction>";
                        $routeDetail=$routeDetail."<SecondJunction>".htmlentities($secondJnName).":".getLatitudeLongitude($secondJnName).":".getBusesForStop($secondJnName)."</SecondJunction>";
                        $routeDetail=$routeDetail."<BusesStartDepotAndJunction>".$getCommonBusesForFirstJunction."</BusesStartDepotAndJunction>";
                        $routeDetail=$routeDetail."<BusesEndDepotAndJunction>".$getCommonBusesForSecondJunction."</BusesEndDepotAndJunction>";
                        $routeDetail=$routeDetail."<BusesEndStopAndDepot>".$endDepotEndStopCommonBusesString."</BusesEndStopAndDepot>"; 					      $routeDetail=$routeDetail."<DistanceBetweenStartStopAndFirstDepot>".$distanceBetweenStartStopAndFirstDepot."</DistanceBetweenStartStopAndFirstDepot>";
                        $routeDetail=$routeDetail."<DistanceBetweenEndStopAndLastDepot>".$distanceBetweenEndStopAndLastDepot."</DistanceBetweenEndStopAndLastDepot>";
                        $routeDetail=$routeDetail."<DistanceBetweenDepots>".$SortedJunctionsDataArray[$i]->getTotalRouteDistance()."</DistanceBetweenDepots>";
                        $totalDistance=$startOffsetDistance+$distanceBetweenStartStopAndFirstDepot+$SortedJunctionsDataArray[$i]->getTotalRouteDistance()+$distanceBetweenEndStopAndLastDepot+$endOffsetDistance;
                        $routeDetail=$routeDetail."<TotalDistance>".$totalDistance."</TotalDistance>";
                        $routeDetail=$routeDetail."<UseDepot>".$useDepot."</UseDepot>";
                        $strRoute=$strRoute.'<RouteDetails>'.$routeDetail.'</RouteDetails>';
                        //echo "<hr/>";
                        $strRoute=$strRoute.'</Route>';
                    }
                    else
                    {
                        //echo "False positive";
                        //$strRoute=$strRoute.'<ErrorCode>6</ErrorCode>';
                        $FalseAlarmCounter++;
                    }
                }
                //echo $strRoute;
            }
            $strRoute=$strRoute.'</Routes>';
            // check if every thing was wrong that means we need to send the erro codes.
            if($FalseAlarmCounter==sizeof($SortedJunctionsDataArray))
            {
                /*$strRoute='<Routes>';
                $strRoute=$strRoute.'<Route>';
                $strRoute=$strRoute.'<IsDirectRoute>N</IsDirectRoute>';
                $strRoute=$strRoute.'<ErrorCode>4</ErrorCode>';
                $strRoute=$strRoute.'</Route>';
                $strRoute=$strRoute.'</Routes>';
                */
                //echo "inside 410";
                return "410";
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
    $sql="Select PeggedDepot , DistanceFromDepot from stops where stopname='".$stopName."'";
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
    list($firstBus,$firstBusFrequency)=explode(":",$firstBusWithFrequency);
    list($secondBus,$secondBusFrequency)=explode(":",$secondBusWithFrequency);

    //get the first bus starting point
    $sql="Select StopName from newBusDetails where busNumber='".$firstBus."' and stopNumber=1";
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
 * getJunctionsForIndirectBuses: function to find the junction. The junction is same as the stop only difference is that some
 * of the stops have mulitple names we need to normalize them. It will combine the buses that are passing to the same
 * junction. The function is mailnly to control the way the informatioin is displayed to the user

change: 27Dec2011
In case when either the bus from the start stop to first junction is not present or the bus from the second jucntion to the end stop is not present
I am removing that particular entry (error code6) but we can as well say in the javascript that this is the case when the user can just walk to/from the 
junction .. Need to chekc on this

The error codes
0: Direct buses are found
1: only one jucntion found same as search" Bellandur" to Forum Mall
2.: One junction but then the user needs to walk to the endpoint. no need to take a bus: search string abhi yaad nahin aa rahi
3: two junctions. User needs to walk between the junction search "Bellandur" "Meanee Avenue Road"
4: No route was found ....give some junk values
5: Distance is walkable no bus to take search Bellandur and Accenture
6: Ignore this particular enetry


 **/
function getJunctionsForIndirectBuses($arrBuses,$startStop,$endStop,$startOffsetDistance,$endOffsetDistance)
{
    //print_r($arrBuses);
    $array_size=sizeof($arrBuses);
    // check if the buses are direct buses
    list($firstBus,$secondBus)=explode(":",$arrBuses[0]);

    if($array_size>0 && strcmp($firstBus,$secondBus)==0)
    {
        //get the direct distance
        $directDistance=getDirectBusDistance($startStop,$endStop,$startOffsetDistance,$endOffsetDistance);
        return displayDirectBuses($arrBuses,$directDistance);
    }
    // indirect buses
    else
    {
        $arrFirstJunctions=array();
        $arrSecondJunctions=array();
        $arrFirstBuses=array();
        $arrSecondBuses=array();


        for($i=0;$i<$array_size;$i++)
        {
            list($firstBus,$secondBus)=explode(":",$arrBuses[$i]);

            array_push($arrFirstBuses,$firstBus);
            array_push($arrSecondBuses,$secondBus);

        }
        //print_r($arrFirstBuses);
        $arrayUniqueJunctions=array();
        for($i=0;$i<$array_size;$i++)
        {
            list($firstBus,$secondBus)=explode(":",$arrBuses[$i]);
            list($firstBus,$firstBusRouteNumber,$firstJunction,$secondBus,$secondBusRouteNumber,$secondJunction,$distance)=explode(":",getIntermediateStopsAndDistance($firstBus,$secondBus));
            if(strlen($firstBus)==0)
                continue;// this is the case of wrong combinations
            //  echo getIntermediateStopsAndDistance($firstBus,$secondBus)."<br/>";
            // find the uniue combinations of fisrt and second junctions
            if($distance<.6)// make suere that the distance between the junctions is not more than 600m.
            {
                if(checkUniqueJunctions($arrayUniqueJunctions,$firstJunction,$secondJunction,$distance)==0)
                {
                    // also chekc that the endstop or the start stop is not same as the juntion as in case of marathahalli bridge and multiplex
                    if(strcmp(getNormalizedStopName($firstJunction),$startStop)==0 ||
                        strcmp(getNormalizedStopName($firstJunction),$endStop)==0 ||
                        strcmp(getNormalizedStopName($secondJunction),$startStop)==0 ||
                        strcmp(getNormalizedStopName($secondJunction),$endStop)==0)
                        continue;
                    else
                    {
                        $element=getNormalizedStopName($firstJunction).":".getNormalizedStopName($secondJunction).":".$distance;
                        array_push($arrayUniqueJunctions,$element);
                    }
                }

            }

        }

        //print_r($arrayUniqueJunctions);
        // module to sort the results on the basids of the total distance
        $displayJunctionsDataArray=array();
        for($i=0;$i<sizeof($arrayUniqueJunctions);$i++)
        {
            $tempJunctionString=$arrayUniqueJunctions[$i];
            list($firstJnName,$secondJnName,$distanceJn)=explode(":",$arrayUniqueJunctions[$i]);
            $tot=getTotalRouteDistance($startStop,$firstJnName,$secondJnName,$distanceJn,$endStop,$startOffsetDistance,$endOffsetDistance);
            $element=new DisplayJunctionsData($tempJunctionString,$tot);
            array_push($displayJunctionsDataArray,$element);

        }

        $SortedJunctionsDataArray=prioritizeAndSortJunctionArray(BubbleSort($displayJunctionsDataArray,sizeof($displayJunctionsDataArray)));
        //echo "sdsd";
        //print_r($SortedJunctionsDataArray);
        // display the results
        $strRoute='';
        if(sizeof($SortedJunctionsDataArray)==0)
        {
            $strRoute='<Routes>';
            $strRoute=$strRoute.'<Route>';
            $strRoute=$strRoute.'<IsDirectRoute>N</IsDirectRoute>';
            $strRoute=$strRoute.'<ErrorCode>4</ErrorCode>';
            $strRoute=$strRoute.'</Route>';
            $strRoute=$strRoute.'</Routes>';
        }

        else
        {
            $strRoute='<Routes>';
            // there can be chances that when we are filling the xml all the details are not properly found. In that case we need to
            // remove that entry. If at the end this check counter becomes equals to the sizeof($SortedJunctionsDataArray). It means every entry had a problem
            // and we will return the error code 4
            $FalseAlarmCounter=0;

            for($i=0;$i<sizeof($SortedJunctionsDataArray);$i++)
            {
                $isCorrectEntry=1;// check if this particular entry is correct. Since the node need to be removed otherwise
                if($i>2)// this will restrict the indirect results to 3
                    break;
                list($firstJnName,$secondJnName,$distanceJn)=explode(":",$SortedJunctionsDataArray[$i]->getJunctionString());

                //get all the buses for firstJn
                $strRoute=$strRoute.'<Route>';
                $strRoute=$strRoute.'<IsDirectRoute>N</IsDirectRoute>';

                //get all the buses for second jun
                if($distanceJn==0 && strcmp(getNormalizedStopName($firstJnName),$startStop)!=0)
                {

                    $getCommonBusesForFirstJunction=getCommonBusesForNormalizedStopName($firstJnName,$arrFirstBuses);
                    $getCommonBusesForSecondJunction=getCommonBusesForNormalizedStopName($secondJnName,$arrSecondBuses);
                    if(strlen($getCommonBusesForFirstJunction)>0 && strlen($getCommonBusesForSecondJunction)>0)
                    {
                        $strRoute=$strRoute.'<ErrorCode>1</ErrorCode>';
                        $routeDetail=htmlentities($startStop).":".$getCommonBusesForFirstJunction.":";
                        $routeDetail=$routeDetail.htmlentities(getNormalizedStopName($firstJnName)).":".getLatitudeLongitude(getNormalizedStopName($firstJnName)).":0:";	$routeDetail=$routeDetail.htmlentities(getNormalizedStopName($firstJnName)).":".getLatitudeLongitude(getNormalizedStopName($firstJnName)).":".$getCommonBusesForSecondJunction.":";
                        $routeDetail=$routeDetail.htmlentities($endStop);
                    }
                    else
                    {
                        $strRoute=$strRoute.'<ErrorCode>6</ErrorCode>';
                        $FalseAlarmCounter++;
                    }

                }
                else
                {
                    // if the end point is same as the second junction it may happend that you juct have to walk from the first junction to the end point
                    if(strcmp(getNormalizedStopName($secondJnName),$endStop)==0)
                    {
                        $strRoute=$strRoute.'<ErrorCode>2</ErrorCode>';
                        $routeDetail=htmlentities($startStop).":".getCommonBusesForNormalizedStopName($firstJnName,$arrFirstBuses).":";
                        $routeDetail=$routeDetail.htmlentities(getNormalizedStopName($firstJnName)).":".getLatitudeLongitude(getNormalizedStopName($firstJnName)).":".$distanceJn.":";				$routeDetail=$routeDetail.htmlentities(getNormalizedStopName($firstJnName)).":".getLatitudeLongitude(getNormalizedStopName($firstJnName)).":".getCommonBusesForNormalizedStopName($secondJnName,$arrSecondBuses).":";
                        $routeDetail=$routeDetail.htmlentities($endStop);

                    }
                    else
                    {
                        $getCommonBusesForFirstJunction=getCommonBusesForNormalizedStopName($firstJnName,$arrFirstBuses);
                        $getCommonBusesForSecondJunction=getCommonBusesForNormalizedStopName($secondJnName,$arrSecondBuses);

                        if(strlen($getCommonBusesForFirstJunction)>0 && strlen($getCommonBusesForSecondJunction)>0)
                        {
                            $strRoute=$strRoute.'<ErrorCode>3</ErrorCode>';
                            $routeDetail=htmlentities($startStop).":".getCommonBusesForNormalizedStopName($firstJnName,$arrFirstBuses).":";
                            $routeDetail=$routeDetail.htmlentities(getNormalizedStopName($firstJnName)).":".getLatitudeLongitude(getNormalizedStopName($firstJnName)).":".$distanceJn.":";
                            $routeDetail=$routeDetail.htmlentities(getNormalizedStopName($secondJnName)).":".getLatitudeLongitude(getNormalizedStopName($secondJnName)).":".getCommonBusesForNormalizedStopName($secondJnName,$arrSecondBuses).":";
                            $routeDetail=$routeDetail.htmlentities($endStop);
                        }
                        else
                        {
                            $FalseAlarmCounter++;
                        }

                    }
                }

                //echo "The approximate route distance="."<b>".$SortedJunctionsDataArray[$i]->getDistance()."KM</b>";
                $routeDetail=$routeDetail.":".$SortedJunctionsDataArray[$i]->getDistance();
                $strRoute=$strRoute.'<RouteDetails>'.$routeDetail.'</RouteDetails>';
                //echo "<hr/>";
                $strRoute=$strRoute.'</Route>';
                //echo $strRoute;

            }
            $strRoute=$strRoute.'</Routes>';
            // check if every thing was wrong that means we need to send the erro codes.
            if($FalseAlarmCounter==sizeof($SortedJunctionsDataArray))
            {
                $strRoute='<Routes>';
                $strRoute=$strRoute.'<Route>';
                $strRoute=$strRoute.'<IsDirectRoute>N</IsDirectRoute>';
                $strRoute=$strRoute.'<ErrorCode>4</ErrorCode>';
                $strRoute=$strRoute.'</Route>';
                $strRoute=$strRoute.'</Routes>';
            }

        }
        return $strRoute;
    }
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
    $array_size=sizeof($arr);
    //$flag=0;
    for($i=0;$i<$array_size;$i++)
    {

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




?>