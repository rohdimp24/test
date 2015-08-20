<?php

# Include http library
//include("LIB_http.php");
#include parse library
include("LIB_parse.php");
require_once('HelperFunctions.php');

?>





<?php

/********************************************************************************
* HAndl;ing the GET
********************************************************************************/

if (isset($_GET['submit']))
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

}

?>

<HTML>
 <head>
    <?php $map->printHeaderJS(); ?>
    <?php $map->printMapJS(); ?>
    <!-- necessary for google maps polyline drawing in IE -->
    <style type="text/css">
      v\:* {
        behavior:url(#default#VML);
      }

	  body {
	margin:0;
	padding:0;
}

    </style>
    
 
	<link rel="stylesheet" type="text/css" href="Scripts/fonts-min.css" />
<link rel="stylesheet" type="text/css" href="Scripts/autocomplete/assets/skins/sam/autocomplete.css" />
<script type="text/javascript" src="Scripts/yahoo-dom-event.js"></script>
<script type="text/javascript" src="Scripts/connection-min.js"></script>
<script type="text/javascript" src="Scripts/animation-min.js"></script>
<script type="text/javascript" src="Scripts/datasource-min.js"></script>
<script type="text/javascript" src="Scripts/autocomplete/autocomplete-min.js"></script>


<!--begin custom header content for this example-->
<style type="text/css">
#originAutoComplete {
    width:25em; /* set width here or else widget will expand to fit its container */
    padding-bottom:2em;
}

#destinationAutoComplete {
    width:25em; /* set width here or else widget will expand to fit its container */
    padding-bottom:2em;
}
</style>
	</head>

	<body class="yui-skin-sam" onload="onLoad()">
<H1> Where do you want to go? </H1>
<form enctype="multipart/form-data" 
  action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get">
  <h3>Source Address </h3>
   	<div id="originAutoComplete">
		<input id="txtSourceAddress" name="txtSourceAddress" type="text">
		<div id="suggestionSourceContainer"></div>
	  </div>
	<h3> Destination Address </h3>
		<div id="destinationAutoComplete">
		<input id="txtDestinationAddress"  name="txtDestinationAddress" type="text">
		<div id="suggestionDestContainer"></div>
	</div>
			
	<script type="text/javascript">
 YAHOO.example.Origin = function() {
    // Use an XHRDataSource
    // the query is sent automatically to the following script as ?query=inputvalue
	var oDS = new YAHOO.util.XHRDataSource("http://localhost/BMTC/queryRoadAddresses.php");
    // Set the responseType
    oDS.responseType = YAHOO.util.XHRDataSource.TYPE_TEXT;
    // Define the schema of the delimited results. The results that are obtained from the php script swill be seperated by /n and fields sepereated by /t
    oDS.responseSchema = {
        recordDelim: "\n",
        fieldDelim: ":"
    };
    // Enable caching
    oDS.maxCacheEntries = 5;

    // Instantiate the AutoComplete
    var oAC = new YAHOO.widget.AutoComplete("txtSourceAddress", "suggestionSourceContainer", oDS);

    // funcction that will format the result
	oAC.formatResult = function(oResultData, sQuery, sResultMatch) {
		return  oResultData[0];
	}
	// this function defines how the display will come once the item has been selected
	 var myHandler = function(sType, aArgs) {
        var myAC = aArgs[0]; // reference back to the AC instance
      //  var elLI = aArgs[1]; // reference to the selected LI element
        //var oData = aArgs[2]; // object literal of selected item's result data
        
        // update City with the selected item's City
        //myCityField.value = oData[1];
		// update the state with teh selected items state
		//myStateField.value = oData[2];
		
		// this line will determine the display string in the input box
        myAC.getInputEl().value = oData[0]; 


    };

	// add the subscription to the myHandler function once the data has been seleected
	oAC.itemSelectEvent.subscribe(myHandler);


    return {
        oDS: oDS,
        oAC: oAC
    };

}();

 YAHOO.example.Destination = function() {
    // Use an XHRDataSource
    // the query is sent automatically to the following script as ?query=inputvalue
	var oDS = new YAHOO.util.XHRDataSource("http://localhost/BMTC/queryRoadAddresses.php");
    // Set the responseType
    oDS.responseType = YAHOO.util.XHRDataSource.TYPE_TEXT;
    // Define the schema of the delimited results. The results that are obtained from the php script swill be seperated by /n and fields sepereated by /t
    oDS.responseSchema = {
        recordDelim: "\n",
        fieldDelim: ":"
    };
    // Enable caching
    oDS.maxCacheEntries = 5;

    // Instantiate the AutoComplete
    var oAC = new YAHOO.widget.AutoComplete("txtDestinationAddress", "suggestionDestContainer", oDS);

    // funcction that will format the result
	oAC.formatResult = function(oResultData, sQuery, sResultMatch) {
		return  oResultData[0];
	}
	// this function defines how the display will come once the item has been selected
	 var myHandler = function(sType, aArgs) {
        var myAC = aArgs[0]; // reference back to the AC instance
      //  var elLI = aArgs[1]; // reference to the selected LI element
        //var oData = aArgs[2]; // object literal of selected item's result data
        
        // update City with the selected item's City
        //myCityField.value = oData[1];
		// update the state with teh selected items state
		//myStateField.value = oData[2];
		
		// this line will determine the display string in the input box
        myAC.getInputEl().value = oData[0]; 


    };

	// add the subscription to the myHandler function once the data has been seleected
	oAC.itemSelectEvent.subscribe(myHandler);


    return {
        oDS: oDS,
        oAC: oAC
    };

}();

</script>			
	<input type="submit" name="submit" value="Get Buses" />
  
 </form>
<table border=1>
    <tr><td>
    <?php $map->printMap(); ?>
    </td><td>
    <?php $map->printSidebar(); ?>
    </td></tr>
    </table>
</body>
</html>
