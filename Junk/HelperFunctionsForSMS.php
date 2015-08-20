<?php
require_once 'loginMySQL.php';
require('GoogleMapAPI.class.php');
require_once('BusDataStructure.php');
require_once('IndirectBusStructure.php');
require_once 'KLogger.php';
require_once 'DisplayJunctionsData.php';
//require('GoogleApiAdvancedClass.php');
//require('JSMin.php');
  
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

$DEBUG=true;

// increase the time out to infinity default is 60
set_time_limit(0);   

/**
* distance(): this function will calcualte the distance between the two points given the latitude and the longitude of the 2 points
* the distance is bas4ed on the straight line and is not the driving distance
**/
function distance($lat1, $lon1, $lat2, $lon2, $unit) { 

  $theta = $lon1 - $lon2; 
  $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta)); 
  $dist = acos($dist); 
  $dist = rad2deg($dist); 
  $miles = $dist * 60 * 1.1515;
  $unit = strtoupper($unit);

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
**/
function getBusesForStop($stopName)
{
	// for each stop found also get the bus number .. also I want BIAS and G series to be considered last so order by
	$busQuery="Select BusNumber from BusDetails where StopName='".$stopName."'ORDER BY BusNumber DESC";
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

/**
* getArrayBusesForStop is same as the getBusesForSTop. Only difference being that the stops are resturned in the form of an array
**/
function getArrayBusesForStop($stopName)
{
	// for each stop found also get the bus number .. also I want BIAS and G series to be considered last so order by
	$busQuery="Select BusNumber from BusDetails where StopName='".$stopName."'ORDER BY BusNumber DESC";
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
		$query="SELECT   BusDetails.BusNumber, BusDetails.StopName, BusDetails.StopNumber, Stops.Latitude, Stops.Longitude FROM BusDetails INNER JOIN Stops ON BusDetails.StopName = Stops.StopName AND BusDetails.BusNumber = '".$busNumber."' AND BusDetails.StopNumber >= '".$startStopNumber."' AND BusDetails.StopNumber <= '".$endStopNumber."' ORDER BY BusDetails.StopNumber";
	}
	else
	{
		$query="SELECT   BusDetails.BusNumber, BusDetails.StopName, BusDetails.StopNumber, Stops.Latitude, Stops.Longitude FROM BusDetails INNER JOIN Stops ON BusDetails.StopName = Stops.StopName AND BusDetails.BusNumber = '".$busNumber."' AND BusDetails.StopNumber >= '".$endStopNumber."' AND BusDetails.StopNumber <= '".$startStopNumber."' ORDER BY BusDetails.StopNumber";
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
	$query="Select StopNumber From BusDetails where StopName='".$stopName."' and BusNumber='".$busNumber."'";
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
	$busQuery="Select BusNumber from BusDetails where StopName='".$stopName."'ORDER BY BusNumber DESC";
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
function getStopForAddress($addr,$log)
{
	
	//echo $addr;
	$stopDataString='';
	$originalRequestedLatitude=0;
	$originalRequestedLongitude=0;

	// create a xml string
	$stopXMLString='<Address>';
	$query="Select StopName, Latitude, Longitude from Stops Where StopName LIKE '".$addr."%'";
	$result= mysql_query($query);	
	$rowsnum = mysql_num_rows($result);
	if($rowsnum>1)
	{
		if($DEBUG)
			echo "more than one stops are found with your search criteria <br/>";
		$log->LogDebug("The address ".$addr." matches more than one in stops table. taking the first one ");
		$stopXMLString=$stopXMLString."<Info>More than one stops are found with your search criteria</Info>";
		$stopXMLString=$stopXMLString."<ErrorCode>MS</ErrorCode>";
		$stopXMLString=$stopXMLString."<Stops>";
		// this is the case wehre we can check which is the best choice in terms of buses coming thourgh..E.g. Marathahalli
		$maxBus=0;
		for($i=0;$i<$rowsnum;$i++)
		{
			$row=mysql_fetch_row($result);
			$stopXMLString=$stopXMLString."<Stop>";
		   if($DEBUG)
			 echo $row[0]."<br/>";
			
			$stopXMLString=$stopXMLString."<Name>".htmlentities($row[0])."</Name>";
			$stopXMLString=$stopXMLString."<Latitude>".$row[1]."</Latitude>";
			$stopXMLString=$stopXMLString."<Longitude>".$row[2]."</Longitude>";
			$stopXMLString=$stopXMLString."<Distance>-1</Distance>";
			$stopXMLString=$stopXMLString."<Buses>".getBusesForStop($row[0])."</Buses>";
			
			// $stopDetails=$row[0];
			//$map->addMarkerByCoords($row[2],$row[1],$row[0],getBusesForStop($row[0]),'');			
			$NumberOfBus=getNumberOfBusesForStop($row[0]);
			if($NumberOfBus>$maxBus)
			{
				$maxBus=$NumberOfBus;
				$stopDataString=$row[0].":".$row[1].":".$row[2].":0";
			}
			$stopXMLString=$stopXMLString."</Stop>";
		}
		$stopXMLString=$stopXMLString."</Stops>";
	}
	else
	if($rowsnum==1)
	{
		$stopXMLString=$stopXMLString."<Info>Your address matches the correct stop name</Info>";
		$stopXMLString=$stopXMLString."<ErrorCode>OS</ErrorCode>";
		$stopXMLString=$stopXMLString."<Stops>";
		$stopXMLString=$stopXMLString."<Stop>";
		$row=mysql_fetch_row($result);
		if($DEBUG)
		   echo " Your stop is ".$row[0]."<br/>";
		$log->LogDebug("The address ".$addr." is a valid stopname ");
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
	else
	if($rowsnum==0)
	{
	
		//echo "going to the roads database"."<br/>";
		// Checking in the road database
		$query="Select * from RoadAddresses Where Address ='".$addr."'";
		$result= mysql_query($query);	
		$rowsnum = mysql_num_rows($result);
		if($rowsnum==0)
		{
			if($DEBUG)
		        echo "The address not found in the database so fetching from internet <br/>";
			//$stopXMLString=$stopXMLString."<Info>The address ".$addr." not found in the database so fetching from internet</Info>";
			$log->LogDebug("The address ".$addr." not found in the database so fetching from internet ");
			// fimd from the net 
			$addressString="http://maps.google.com/maps/api/geocode/json?address=".urlencode($addr).",+Bangalore,+Karnataka,+India&sensor=false";
			$geocode=file_get_contents($addressString);
			//echo $geocode;
			$formattedAddress=return_between($geocode,"formatted_address","\",",EXCL);
			$formattedAddressNoiseRemoved=return_between($formattedAddress,":","India",INCL);
			$place=substr($formattedAddressNoiseRemoved,3);
		//	echo $place."<br/>";
			///echo $place."<br/>";
			$arr=array();
			$arr=split(",",$place);
			if(strcmp(trim($arr[0]),"Bengaluru")==0 && strcmp(trim($arr[1]),"Karnataka")==0 && strcmp(trim($arr[2]),"India")==0)
			{
				$stopXMLString=$stopXMLString."<Info>Address is not valid</Info>";
				$stopXMLString=$stopXMLString."<ErrorCode>AM</ErrorCode>";
				$stopXMLString=$stopXMLString."<Stops>";
				$stopXMLString=$stopXMLString."<Stop>";
				$stopXMLString=$stopXMLString."</Stop>";
				$stopXMLString=$stopXMLString."</Stops>";
				if($DEBUG)
		            echo "<h3>".trim($roadName)."  Address is not valid"."</h3><br/>";
				$stopDataString="0:0:0:0";
			}
			else
			{

				$locations=return_between($geocode,"location","}",EXCL);
				//echo $locations;
				$latitude=return_between($locations,"lat\"",",",EXCL);
				$latitude=substr($latitude,3);
				//echo $latitude;

				list($lat,$long)=split(",",$locations);

				list($cap,$val)=split(":",$long);
				//echo trim($val);
				
				$addrLat=trim($latitude);
				$addrLong=trim($val);
				
				$originalRequestedLatitude=$addrLat;
				$originalRequestedLongitude=$addrLong;
				
				// add the marker for the searched address
				//$map->addMarkerIcon("marker_star.png","marker_shadow.png",15,29,15,3);
				//$map->addMarkerByCoords($addrLong,$addrLat,$addr,'','');
				
				$query="SELECT  StopName, Latitude, Longitude FROM Stops WHERE (Latitude - ".$addrLat." < .009) AND (Latitude - ".$addrLat." > - .009) AND (Longitude - ".$addrLong." > - .009) AND (Longitude - ".$addrLong." < 0.009)";
			
				//echo $query."<br/>";
				
				$result= mysql_query($query);	
				$rowsnum = mysql_num_rows($result);
				if($rowsnum==0)
				{
					// this is the case when you gave an address that even could not be found usign the internet search
					$stopXMLString=$stopXMLString."<Info>Address is not valid</Info>";
					$stopXMLString=$stopXMLString."<ErrorCode>AM</ErrorCode>";
					$stopXMLString=$stopXMLString."<Stops>";
					$stopXMLString=$stopXMLString."<Stop>";
					$stopXMLString=$stopXMLString."</Stop>";
					$stopXMLString=$stopXMLString."</Stops>";
					if($DEBUG)
						echo "<h3>".trim($roadName)."  Address is not valid"."</h3><br/>";
					$stopDataString="0:0:0:0";

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

			}

		}
		else if($rowsnum>1)
		{
			   $stopXMLString=$stopXMLString."<Info>More than one matching address found in road database. Kindly add some more specifics</Info>";
			   $stopXMLString=$stopXMLString."<ErrorCode>MA</ErrorCode>";
				$stopXMLString=$stopXMLString."<Stops>";
				$stopXMLString=$stopXMLString."<Stop>";
				$stopXMLString=$stopXMLString."</Stop>";
				$stopXMLString=$stopXMLString."</Stops>";
			 if($DEBUG)
		        echo "More than one matching address found in road database. Kindly add some more specifics";
			$stopDataString="m:m:m:m";

		}	
		else
		{
			if($DEBUG)
				echo "Found in the road database"."<br/>";
			
			$log->LogDebug("The address ".$addr." found in the road database ");
			$row=mysql_fetch_row($result);
			$addrLat=$row[3];
			$addrLong=$row[4];

			$originalRequestedLatitude=$addrLat;
			$originalRequestedLongitude=$addrLong;
			
			//echo $addrLat;
			//$map->addMarkerByCoords($addrLong,$addrLat,$addr,'','');
			$query="SELECT  StopName, Latitude, Longitude FROM Stops WHERE (Latitude - ".$addrLat." < .009) AND (Latitude - ".$addrLat." > - .009) AND (Longitude - ".$addrLong." > - .009) AND (Longitude - ".$addrLong." < 0.009)";
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

			}

		}
	}



	$stopXMLString=$stopXMLString."<Result>";
	list($BusStop,$BusLatitude,$BusLongitude,$BusOffset)=split(":",$stopDataString);
	$stopXMLString=$stopXMLString."<BusStop>".htmlentities($BusStop)."</BusStop>";
	$stopXMLString=$stopXMLString."<BusStopLatitude>".$BusLatitude."</BusStopLatitude>";
	$stopXMLString=$stopXMLString."<BusStopLongitude>".$BusLongitude."</BusStopLongitude>";
	$stopXMLString=$stopXMLString."<Offset>".$BusOffset."</Offset>";
	$stopXMLString=$stopXMLString."<Buses>".getBusesForStop($BusStop)."</Buses>";
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
			$element=$tempStart.":".$tempStart;;
			array_push($arrSameBus,$element);
		}			

	}

	
	if(sizeof($arrSameBus)>0 && $showOnlyIndirectBuses==0)
	{
		return $arrSameBus;
	}
	
	// in case you need to get the indirect buses forcefully then remove the common buses from the array of the $startBuses and $endBuses

	for($i=0;$i<sizeof($arrSameBus);$i++)
	{
		$temp=split(":",$arrSameBus[$i]);
		$key=array_search($temp[0],$startBuses);
		unset($startBuses[$key]);
		$startBuses=array_values($startBuses);
		
	}
	
	for($i=0;$i<sizeof($arrSameBus);$i++)
	{
		$temp=split(":",$arrSameBus[$i]);
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
			list($firstBusNumber,$firstRouteNumber,$firstBusStop,$secondBusNumber,$secondRouteNumber,$secondBusStop,$distance)=split(":",getIntermediateStopsAndDistance($firstBus,$secondBus));			
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
		 return $arrBuses;
	}
	else
	{
		//return $sortedIndirectBuses[0]->getFirstBus().":".$sortedIndirectBuses[0]->getSecondBus();
		//print_r($arrBIASBuses);
		return $arrBIASBuses;
	}
		
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
	$query="Select FirstBusNumber, FirstRouteName, FirstBusRouteStop, SecondBusNumber, SecondRouteName, SecondBusRouteStop, Distance from  IntermediateStops where FirstBusNumber='".$firstBus."' and SecondBusNumber='".$endBus."'";
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
	list($firstBus,$secondBus)=split(":",$arrBuses[0]);

	if($array_size>0 && strcmp($firstBus,$secondBus)==0)
		return displayDirectBuses($arrBuses);
	
	// indirect buses
	else
	{
		$arrFirstJunctions=array();
		$arrSecondJunctions=array();
		$arrFirstBuses=array();
		$arrSecondBuses=array();
	

		for($i=0;$i<$array_size;$i++)
		{
			list($firstBus,$secondBus)=split(":",$arrBuses[$i]);
			
			array_push($arrFirstBuses,$firstBus);
			array_push($arrSecondBuses,$secondBus);
			
		}
		//print_r($arrFirstBuses);
		$arrayUniqueJunctions=array();
		for($i=0;$i<$array_size;$i++)
		{
			list($firstBus,$secondBus)=split(":",$arrBuses[$i]);
			list($firstBus,$firstBusRouteNumber,$firstJunction,$secondBus,$secondBusRouteNumber,$secondJunction,$distance)=split(":",getIntermediateStopsAndDistance($firstBus,$secondBus));
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
			list($firstJnName,$secondJnName,$distanceJn)=split(":",$arrayUniqueJunctions[$i]);
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
				list($firstJnName,$secondJnName,$distanceJn)=split(":",$SortedJunctionsDataArray[$i]->getJunctionString());
				
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
 getCommonBusesForNormalizedStopName: function returns the common buses that go to the junction from the start or end point. We will be 
TODO: CHekc the functionality again
**/
function getCommonBusesForNormalizedStopName($stopName,$arrBusesForIntersection)
{
	$arrJunctionBuses=array();
	
	
	/*if(strcmp($stopName,"Central Silk Board (ORR)")==0)
	{

		$busQuery="SELECT DISTINCT BusNumber FROM  BusDetails WHERE (StopName='Central Silk Board (ORR)') OR (StopName = 'Central Silk Board (Hosur RD)') OR (StopName = 'Central Silk Board (BTM)') ORDER BY BusNumber";
		$busResult= mysql_query($busQuery);
		$busRowsnum = mysql_num_rows($busResult);
		
		for($j=0;$j<$busRowsnum;$j++)
		{
			$busRow=mysql_fetch_row($busResult);
			array_push($arrJunctionBuses,$busRow[0]);
		}	
		

	}
	else if(strcmp($stopName,"Marathahalli Bridge")==0)
	{
		$busQuery="SELECT DISTINCT BusNumber FROM  BusDetails WHERE (StopName='Marathahalli (Jn. Vartur & ORR)') OR (StopName = 'Marathahalli Bridge (ORR)') OR (StopName = 'Marathahalli Bridge') OR (StopName='Marathahalli Multiplex Bridge') OR (StopName='Marathahalli Jn') ORDER BY BusNumber";
		$busResult= mysql_query($busQuery);
		$busRowsnum = mysql_num_rows($busResult);
		
		for($j=0;$j<$busRowsnum;$j++)
		{
			$busRow=mysql_fetch_row($busResult);
			array_push($arrJunctionBuses,$busRow[0]);
		}	
		
	}
	else if(strcmp($stopName,"Marathahalli Multiplex")==0)
	{
		$busQuery="SELECT DISTINCT BusNumber FROM  BusDetails WHERE (StopName='Marathahalli Jn(Multiplex)') OR (StopName = 'Marathahalli (Mulitplex ORR)') OR (StopName = 'Marathahalli Multiplex') ORDER BY BusNumber";
		$busResult= mysql_query($busQuery);
		$busRowsnum = mysql_num_rows($busResult);
		
		for($j=0;$j<$busRowsnum;$j++)
		{
			$busRow=mysql_fetch_row($busResult);
			array_push($arrJunctionBuses,$busRow[0]);
		}

	}
	else if(strcmp($stopName,"Hebbala")==0)
	{
		$busQuery="SELECT DISTINCT BusNumber FROM  BusDetails WHERE (StopName='Hebbala') OR (StopName = 'Hebbala (ORR)') OR (StopName = 'Hebbala (Canara Bank)') ORDER BY BusNumber";
		$busResult= mysql_query($busQuery);
		$busRowsnum = mysql_num_rows($busResult);
		
		for($j=0;$j<$busRowsnum;$j++)
		{
			$busRow=mysql_fetch_row($busResult);
			array_push($arrJunctionBuses,$busRow[0]);
		}
	
	}

	else
	{
		//echo "STOP".$stopName;
		$arrJunctionBuses=getArrayBusesForStop($stopName);
	}*/

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
	//print_r($arrCommon);

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
	
		list($firstJnName,$secondJnName,$distanceJn)=split(":",$arr[$i]);
		if(strcmp(getNormalizedStopName($firstJnName),getNormalizedStopName($firstJunction))==0 && strcmp(getNormalizedStopName($secondJnName),getNormalizedStopName($secondJunction))==0 && $distanceJn==$distance)
		{
			return 1;
		}
			
	}
	return 0;

}

/**
*displayDirectBuses: function to display the direct buses
**/
function displayDirectBuses($arrBuses)
{
	$strRoute='';
	$strRoute='<Routes>';
	$array_size=sizeof($arrBuses);
	for($i=0;$i<$array_size;$i++)
	{
		$strRoute=$strRoute.'<Route>';
		list($firstBus,$secondBus)=split(":",$arrBuses[$i]);
		//echo "Direct Bus #".$i."=>".$firstBus."</br>";
		$strRoute=$strRoute.'<IsDirectRoute>Y</IsDirectRoute>';
		$strRoute=$strRoute.'<ErrorCode>0</ErrorCode>';
		$strRoute=$strRoute.'<RouteDetails>'.$firstBus.'</RouteDetails>';
		$strRoute=$strRoute.'</Route>';

	}
	$strRoute=$strRoute.'</Routes>';
	return $strRoute;
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
    for($i=0;$i<sizeof($sortedJunctionArray);$i++)
	{
		// read the first point
		//$junctionString=$element=getNormalizedStopName($firstJunction).":".getNormalizedStopName($secondJunction).":".$distance;
		list($firstJunction,$secondJunction,$distance)=split(":",$sortedJunctionArray[$i]->getJunctionString());			
		if(strcmp($firstJunction,$secondJunction)==0)
			array_push($singleJunctionArray,$sortedJunctionArray[$i]);
		else
			array_push($doubleJunctionArray,$sortedJunctionArray[$i]);
		
	}
	
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
function getDirectBusDistance($startStop,$endStop,$startOffsetDistance,$endOffsetDistance)
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


?>