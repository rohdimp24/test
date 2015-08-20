	var YWSID = "nYpPIFyOGulOjVWMvG7sgw"; // common required parameter (api key)
    var URBANKEY="ubwecy934e8hkzvq6mehnedv"; // urbammapping
    var map = null;
    /*var iconSrc = "images/icon_greenA.png";
	var iconDest="images/icon_greenB.png";
	var iconJunc1="images/pinupred.png";
	var iconJunc2="images/pinupblue.png";
	var iconOriginalSource="images/arrow.png";
	var iconOriginalDestination="images/arrow.png";  
	var iconDepot1="images/DepotPurpleMarker.png";  
	var iconDepot2="images/SecondDepotMarker.png"; 
	var iconDepot="images/DepotPurpleMarker.png";
	*/
	var iconSrc = "images/ChartApi/A.png";
	var iconDest="images/ChartApi/B.png";
	var iconJunc1="images/ChartApi/J1.png";
	var iconJunc2="images/ChartApi/J2.png";
    var iconJunc="images/ChartApi/J.png";
	var iconOriginalSource="images/arrow.png";
	var iconOriginalDestination="images/arrow.png";  
	var iconDepot1="images/ChartApi/D1.png"; 
	var iconDepot2="images/ChartApi/D2.png"; 
	var iconDepot="images/ChartApi/D.png";
	var bounds=null;
	var infoWindow=null;
	var polylinePath=null;
	var polyLines=[];
    
	//var markerForOriginalSource=null;
    
	//var markerForOriginalDestination=null;
    /*
     * Creates the map object and calls setCenterAndBounds
     * to instantiate it.
     */
    function load_method() 
    {
		//create a map object
		map = new google.maps.Map(document.getElementById("map"),  {
                zoom: 13,
				center : new google.maps.LatLng(12.975453182742744,77.60597766987303),
                mapTypeId : google.maps.MapTypeId.ROADMAP
				
        });
		
		infoWindow = new google.maps.InfoWindow();
       
		bounds = new google.maps.LatLngBounds();
		//clearOverlays();
		//alert(bounds.getCenter());
		//map.panTo(bounds.getCenter());
		
    }

   google.maps.event.addDomListener(window, 'load', load_method);

	


    function getJunctionIcons(firstJunctionName,secondJunctionName)
    {
        if(firstJunctionName==secondJunctionName)
            return iconJunc+":"+iconJunc;
        else
           return iconJunc1+":"+iconJunc2;
    }


	
	function PlotWalkableMap(walkSourceName,walkSourceLat,walkSourceLon,walkDestName,walkDestLat,walkDestLon,walkDistance)
	{
		clearOverlays();
		var points=[];
		var sourcePoint= new google.maps.LatLng( walkSourceLat,walkSourceLon);
		createMarker(walkSourceName,'',sourcePoint, 1,iconSrc);
		points.push(sourcePoint);

		var destPoint=new google.maps.LatLng( walkDestLat,walkDestLon);
		createMarker(walkDestName,'',destPoint, 2,iconDest);
		points.push(destPoint);

		/*map.setZoom(map.getBoundsZoomLevel(bounds));
		map.setCenter(bounds.getCenter());
		map.addOverlay(new GPolyline(points,"#FF0000"));
		*/
		polylinePath = new google.maps.Polyline({
			path: points,
			strokeColor: '#FF00FF',
			strokeOpacity: 1.0,
			strokeWeight: 4
		  });

		  polylinePath.setMap(map);
		 polyLines.push(polylinePath);
		
		//create the marker
		
		map.panTo(bounds.getCenter());
	
	}


	function plotDepotMap(data,startStop,endStop,firstDepotArray,secondDepotArray,busesWithFrequencyForDepot,
                          busesBetweenStartStopAndStartDepot,busesBetweenEndStopAndEndDepot,errorCode)
	{

		clearOverlays();
		
		//create the source node
		var busStops = data.getElementsByTagName("Result");
		var sourceStopName = busStops[0].firstChild.textContent;
		var sourceStopLatitude=busStops[0].childNodes[1].textContent;
		var sourceStopLongitude=busStops[0].childNodes[2].textContent;
		var sourceStopOffset=busStops[0].childNodes[3].textContent;
		var sourceBuses=busStops[0].childNodes[4].textContent;
		var sourcePoint= new google.maps.LatLng( sourceStopLatitude,sourceStopLongitude);		
		createMarker(sourceStopName,sourceBuses,sourcePoint, 1,iconSrc);
		
		//create the destination node
		var destStopName = busStops[1].firstChild.textContent;
		var destStopLatitude=busStops[1].childNodes[1].textContent;
		var destStopLongitude=busStops[1].childNodes[2].textContent;
		var destStopOffset=busStops[1].childNodes[3].textContent;
		var destBuses=busStops[1].childNodes[4].textContent;
		var destPoint=new google.maps.LatLng( destStopLatitude,destStopLongitude);
		createMarker(destStopName,destBuses,destPoint, 2,iconDest);	
		
		if(errorCode==7)
		{
			//the depot first Nide
			var firstDepotName=firstDepotArray[0];
			var firstDepotLatitude=firstDepotArray[1];
			var firstDepotLongitude=firstDepotArray[2];
			var firstDepotBuses=busesWithFrequencyForDepot;
			var firstDepotPoint=new google.maps.LatLng(firstDepotLatitude,firstDepotLongitude);
			createMarker(firstDepotName,firstDepotBuses,firstDepotPoint,3,iconDepot);
            //now the bus route..Between the start Stop and the first depot
            drawPolyLine(sourceStopName,firstDepotName,busesBetweenStartStopAndStartDepot,'#FF0000');
            //drw line beteen the second depot and end
            drawPolyLine(firstDepotName,destStopName,busesBetweenEndStopAndEndDepot,'#FF00FF');
		}
		if(errorCode==8)
		{
			//the depot second Nide
			var secondDepotName=secondDepotArray[0];
			var secondDepotLatitude=secondDepotArray[1];
			var secondDepotLongitude=secondDepotArray[2];
			var secondDepotBuses=busesWithFrequencyForDepot;
			var secondDepotPoint=new google.maps.LatLng(secondDepotLatitude,secondDepotLongitude);
			createMarker(secondDepotName,secondDepotBuses,secondDepotPoint,4,iconDepot);
            //now the bus route..Between the start Stop and the second depot
            drawPolyLine(sourceStopName,secondDepotName,busesBetweenStartStopAndStartDepot,'#FF0000');
            //drw line beteen the second depot and end
            drawPolyLine(secondDepotName,destStopName,busesBetweenEndStopAndEndDepot,'#FF00FF');
		}

		map.panTo(bounds.getCenter());

	}


    //for the cases 9&10
    function plotIndirectDepotMap(data,startStop,endStop,depotJunctionArray,busRouteDetailsArray,displayBusesForMarkers,originalStartStop,
                                  originalDestinationStop,errorCode)
	{

        if(originalStartStop!=null)
            createAddressMarker("Source Address",originalStartStop,iconOriginalSource);

        if(originalDestinationStop!=null)
            createAddressMarker("Destination Address",originalDestinationStop,iconOriginalSource);


        clearOverlays();
		
		//create the source node

        displayBusesArray=displayBusesForMarkers.split("^");

		var busStops = data.getElementsByTagName("Result");
		var sourceStopName = busStops[0].firstChild.textContent;
		var sourceStopLatitude=busStops[0].childNodes[1].textContent;
		var sourceStopLongitude=busStops[0].childNodes[2].textContent;
		//var sourceBuses=busStops[0].childNodes[4].textContent;
        var sourceBuses;
        if(errorCode==9)
            sourceBuses=displayBusesArray[0];
        else
            sourceBuses=displayBusesArray[2];
		var sourcePoint= new google.maps.LatLng( sourceStopLatitude,sourceStopLongitude);

		createMarker(sourceStopName,sourceBuses.slice(0, - 1),sourcePoint, 1,iconSrc);
		
		//create the destination node
		var destStopName = busStops[1].firstChild.textContent;
		var destStopLatitude=busStops[1].childNodes[1].textContent;
		var destStopLongitude=busStops[1].childNodes[2].textContent;
		var destStopOffset=busStops[1].childNodes[3].textContent;
		//var destBuses=busStops[1].childNodes[4].textContent;
        var destBuses;
        if(errorCode==9)
            destBuses=displayBusesArray[3];
        else
            destBuses=displayBusesArray[1];
		var destPoint=new google.maps.LatLng( destStopLatitude,destStopLongitude);
		createMarker(destStopName,destBuses.slice(0, - 1),destPoint, 2,iconDest);	

        var localDepotJunctionArray=depotJunctionArray.split("^");
        //var depotData=localDepotJunctionArray[0].split(":");
		//the depot point
		var depotName = localDepotJunctionArray[0];
		var depotLatitude=localDepotJunctionArray[1];
		var depotLongitude=localDepotJunctionArray[2];
		//var depotBuses=depotData[3];
        var depotPoint=new google.maps.LatLng( depotLatitude,depotLongitude);

        busesArray=busRouteDetailsArray.split("^");
        busBetweenDepotAndStopForRoutePlotting=busesArray[0];
        busBetweenStopAndJunctionForRoutePlotting=busesArray[1];
        busBetweenJunctionAndDepotForRoutePlotting=busesArray[2];



        var depotBuses='';
        if(errorCode==9)
            depotBuses=displayBusesArray[0]+displayBusesArray[1];
        else
            depotBuses=displayBusesArray[0]+displayBusesArray[1];
        createMarker(depotName,depotBuses.slice(0, - 1),depotPoint, 2,iconDepot);
        //note: 11/13/2013 in case of the other cases the junction is not showing all the buses but juct the ones that are coming from the source stop .
		//I think we should show all of the buses.
		//in case then we need to do the same for the depot also
		//the depot first Nide

      //  var firstJunctionData=localDepotJunctionArray[1].split(":");
		var firstJunctionName=localDepotJunctionArray[3];
		var firstJunctionLatitude=localDepotJunctionArray[4];
		var firstJunctionLongitude=localDepotJunctionArray[5];
		//var firstDepotBuses=firstDepotArray[3];
		var firstJunctionPoint=new google.maps.LatLng(firstJunctionLatitude,firstJunctionLongitude);
        var firstJunctionBuses;
        var secondJunctionBuses;

        var secondJunctionName=localDepotJunctionArray[6];

        var secondJunctionLatitude=localDepotJunctionArray[7];
        var secondJunctionLongitude=localDepotJunctionArray[8];
        //var secondDepotBuses=secondJunctionData[3];
        var secondJunctionPoint=new google.maps.LatLng(secondJunctionLatitude,secondJunctionLongitude);

        if(errorCode==10)
        {
            if(firstJunctionName==secondJunctionName)
            {
                firstJunctionBuses=displayBusesArray[0]+displayBusesArray[2];
                secondJunctionBuses=displayBusesArray[0]+displayBusesArray[2];
            }
           else
            {
                firstJunctionBuses=displayBusesArray[2];
                secondJunctionBuses=displayBusesArray[0];
            }
        }
        else
        {

            if(firstJunctionName==secondJunctionName)
            {
                firstJunctionBuses=displayBusesArray[2]+displayBusesArray[3];
                secondJunctionBuses=displayBusesArray[2]+displayBusesArray[3];
            }
            else
            {
                firstJunctionBuses=displayBusesArray[2];
                secondJunctionBuses=displayBusesArray[0];
            }


        }

        junctionIcons=getJunctionIcons(firstJunctionName,secondJunctionName).split(":");

        createMarker(firstJunctionName,firstJunctionBuses.slice(0, - 1),firstJunctionPoint,3,junctionIcons[0]);



		//the depot second Nide
        //var secondJunctionData=localDepotJunctionArray[2].split(":");

		createMarker(secondJunctionName,secondJunctionBuses.slice(0, - 1),secondJunctionPoint,4,junctionIcons[1]);



        if(errorCode==10)
        {
            //now the bus route..Between the start Stop and the firsr junction
            drawPolyLine(sourceStopName,firstJunctionName,busBetweenStopAndJunctionForRoutePlotting,'#FF0000');
            //drae line between the second junction and depot
            drawPolyLine(secondJunctionName,depotName,busBetweenJunctionAndDepotForRoutePlotting,'#0000FF');
            //draw line between the depot and the end
            drawPolyLine(endStop,depotName,busBetweenDepotAndStopForRoutePlotting,'#00FF00');

        }
        else
        {
            //now the bus route..Between the start Stop and the depot
            drawPolyLine(sourceStopName,depotName,busBetweenDepotAndStopForRoutePlotting,'#FF0000');
            //drae line between the depot and firstJucntio
            drawPolyLine(depotName,firstJunctionName,busBetweenJunctionAndDepotForRoutePlotting,'#0000FF');
            //draw line between the second junction and the end
            drawPolyLine(secondJunctionName,endStop,busBetweenStopAndJunctionForRoutePlotting,'#00FF00');
        }

		map.panTo(bounds.getCenter());

	}
						


	function drawPolyLine(sourceStopName,destinationStopName,busesBetweenSourceAndDestination,color)
	{
		var points=[];
		// get the list of the points
		//alert(sourceStopName+destStopName+busRoute);
		var plottingData=getBusPointsToDraw(sourceStopName,destinationStopName,busesBetweenSourceAndDestination);
		//var plottingData=plottingDataRaw.responseXML;
		var plottingMarkersLatitudes=plottingData.getElementsByTagName("Latitude");
		var plottingMarkersLongitudes=plottingData.getElementsByTagName("Longitude");
		var numberOfMarkers=plottingData.getElementsByTagName("Route");
		for(var i=0;i<numberOfMarkers.length;i++)
		{
			tracePoint=new google.maps.LatLng(plottingMarkersLatitudes[i].firstChild.textContent,plottingMarkersLongitudes[i].firstChild.textContent);
			points.push(tracePoint);

		}

		//map.addOverlay(new GPolyline(points,"#FF0000"));
		polylinePath = new google.maps.Polyline({
				path: points,
				strokeColor: color,
				strokeOpacity: 1.0,
				strokeWeight: 4
			});

			polylinePath.setMap(map);
			polyLines.push(polylinePath);

	}



    /*
     * Called on the form submission: updates the map by
     * placing markers on it at the appropriate places
     */
    function PlotMap(data,junctionData,busRoute,junctionBusesForToolTip,originalSource,originalDestination)
    
	{
		//map.clearOverlays();
		handleResults(data,junctionData,busRoute,junctionBusesForToolTip,originalSource,originalDestination);
		//updateMap();
		//map.setZoom(map.getBoundsZoomLevel(bounds));
		//map.setCenter(bounds.getCenter());
		//map.addOverlay(new GPolyline(points,"#FF0000"));
		map.panTo(bounds.getCenter());
    }

	
	gArrBusRoutesCache=new Array();

    function clearBusRoutesCache()
    {
        gArrBusRoutesCache.empty();
    }
    /*
     * If a sucessful API response is received, place
     * markers on the map.  If not, display an error.
     */
    function handleResults(data,junctionData,busRoute,junctionBusesForToolTip,originalSource,originalDestination)
    
	{
		clearOverlays();
		// create the originalSource and destination Markers
		if(originalSource!=null)
			createAddressMarker("Source Address",originalSource,iconOriginalSource);
		
		if(originalDestination!=null)
			createAddressMarker("Destination Address",originalDestination,iconOriginalSource);			
		
		//create the source node
		var busStops = data.getElementsByTagName("Result");
		var sourceStopName = busStops[0].firstChild.textContent;
		var sourceStopLatitude=busStops[0].childNodes[1].textContent;
		var sourceStopLongitude=busStops[0].childNodes[2].textContent;
		var sourceStopOffset=busStops[0].childNodes[3].textContent;
		var sourceBuses=busStops[0].childNodes[4].textContent;
		var sourcePoint= new google.maps.LatLng( sourceStopLatitude,sourceStopLongitude);		
		createMarker(sourceStopName,sourceBuses,sourcePoint, 1,iconSrc);
		
		//create the destination node
		var destStopName = busStops[1].firstChild.textContent;
		var destStopLatitude=busStops[1].childNodes[1].textContent;
		var destStopLongitude=busStops[1].childNodes[2].textContent;
		var destStopOffset=busStops[1].childNodes[3].textContent;
		var destBuses=busStops[1].childNodes[4].textContent;
		var destPoint=new google.maps.LatLng( destStopLatitude,destStopLongitude);
		createMarker(destStopName,destBuses,destPoint, 2,iconDest);		

		
		// i think we should plot the neighbouring points too that are obtained in the stops section
	 	//var sourceAdjucntStops=data.getElementsByTagName("Stops");
		//var stop1=sourceAdjucntStops[0].getElementsByTagName("Stop");
		
		// in case there is a junction then add that also
		if(junctionData!=0)
		{
			var FirstJunctionPoints=[];
			var junctionArray=new Array()
			junctionArray=junctionData.split("^");
			var firstJunctionName=junctionArray[0];
			var firstJunctionLat=junctionArray[1];
			var firstJunctionLon=junctionArray[2];
			var firstJunctionPoint= new google.maps.LatLng( firstJunctionLat,firstJunctionLon);

			var secJunctionName=junctionArray[3];

            junctionIcons=getJunctionIcons(firstJunctionName,secJunctionName).split(":");

            junctionBusesForToolTipArray=junctionBusesForToolTip.split("^");
            createMarker(firstJunctionName,junctionBusesForToolTipArray[0].slice(0, - 1),firstJunctionPoint, 1,junctionIcons[0]);
			if(firstJunctionName!=secJunctionName)
			{
				var secJunctionLat=junctionArray[4];
				var secJunctionLon=junctionArray[5];
				var secJunctionPoint= new google.maps.LatLng( secJunctionLat,secJunctionLon);
				//createMarker(secJunctionName,busStops[1].childNodes[4].textContent,secJunctionPoint, 1,junctionIcons[1]);
				createMarker(secJunctionName,junctionBusesForToolTipArray[1].slice(0, - 1),secJunctionPoint, 1,junctionIcons[1]);
			}
			//points.push(secJunctionPoint);
			
		  // add the points
		  
			var busRoutsForPlotting=busRoute.split("^");
			// draw the route between the first junction and the source stop
			var firstjunctionPlottingData=getBusPointsToDraw(sourceStopName,firstJunctionName,busRoutsForPlotting[0]);
			var firstJunctionPlottingMarkersLatitudes=firstjunctionPlottingData.getElementsByTagName("Latitude");
			var firstJunctionPlottingMarkersLongitudes=firstjunctionPlottingData.getElementsByTagName("Longitude");
			var numberOfMarkersFromFirstJunction=firstjunctionPlottingData.getElementsByTagName("Route");
			for(var i=0;i<numberOfMarkersFromFirstJunction.length;i++)
			{
			tracePoint=new google.maps.LatLng(firstJunctionPlottingMarkersLatitudes[i].firstChild.textContent,firstJunctionPlottingMarkersLongitudes[i].firstChild.textContent);
			FirstJunctionPoints.push(tracePoint);
			}
			// this should be done only if the last parameter in the busRoute is 1
			if(busRoutsForPlotting[2]==1)
			{
				var SecondJunctionPoints=[];
				var secondjunctionPlottingData=getBusPointsToDraw(secJunctionName,destStopName,busRoutsForPlotting[1]);
				var secondJunctionPlottingMarkersLatitudes=secondjunctionPlottingData.getElementsByTagName("Latitude");
				var secondJunctionPlottingMarkersLongitudes=secondjunctionPlottingData.getElementsByTagName("Longitude");
				var numberOfMarkersFromSecondJunction=secondjunctionPlottingData.getElementsByTagName("Route");
				for(var i=0;i<numberOfMarkersFromSecondJunction.length;i++)
				{
				tracePoint=new google.maps.LatLng(secondJunctionPlottingMarkersLatitudes[i].firstChild.textContent,secondJunctionPlottingMarkersLongitudes[i].firstChild.textContent);
				SecondJunctionPoints.push(tracePoint);
				}
				//map.addOverlay(new GPolyline(SecondJunctionPoints,"#571B7e"));
				polylinePath = new google.maps.Polyline({
					path: SecondJunctionPoints,
					strokeColor: '#571B7e',
					strokeOpacity: 1.0,
					strokeWeight: 4
				});

				polylinePath.setMap(map);
				polyLines.push(polylinePath);
			}
			//map.addOverlay(new GPolyline(FirstJunctionPoints,"#FF0000"));
			// is it required?????11/13/2013
			polylinePath = new google.maps.Polyline({
					path: FirstJunctionPoints,
					strokeColor: '#FF0000',
					strokeOpacity: 1.0,
					strokeWeight: 4
				});

				polylinePath.setMap(map);
				polyLines.push(polylinePath);

			    //todo: We need to draw a dashed line to connect the junctions ..this is especially important in case of
                // errror code 2
		}
		else
		{
			var points=[];
			// get the list of the points
			//alert(sourceStopName+destStopName+busRoute);
			var plottingData=getBusPointsToDraw(sourceStopName,destStopName,busRoute);
			//var plottingData=plottingDataRaw.responseXML;
			var plottingMarkersLatitudes=plottingData.getElementsByTagName("Latitude");
			var plottingMarkersLongitudes=plottingData.getElementsByTagName("Longitude");
			var numberOfMarkers=plottingData.getElementsByTagName("Route");
			for(var i=0;i<numberOfMarkers.length;i++)
			{
				tracePoint=new google.maps.LatLng(plottingMarkersLatitudes[i].firstChild.textContent,plottingMarkersLongitudes[i].firstChild.textContent);
				points.push(tracePoint);

			}

			//map.addOverlay(new GPolyline(points,"#FF0000"));
			polylinePath = new google.maps.Polyline({
					path: points,
					strokeColor: '#FF0000',
					strokeOpacity: 1.0,
					strokeWeight: 4
				});

				polylinePath.setMap(map);
				polyLines.push(polylinePath);

		}
			
	}

	
    
	function getBusPointsToDraw(sourceStop,destinationStop,busNumberWithFrequency)
	{
    // instantiate XMLHttpRequest object
		try
		{
			xhr = new XMLHttpRequest();
		}
		catch (e)
		{
			xhr = new ActiveXObject("Microsoft.XMLHTTP");
		}

		// handle old browsers
		if (xhr == null)
		{
			alert("Ajax not supported by your browser!");
			return;
		}

        var busNumberArray=busNumberWithFrequency.split(":");
        var busNumber=busNumberArray[0];

        key=sourceStop+"^"+destinationStop+"^"+busNumber;
        //http://stackoverflow.com/questions/1098040/checking-if-an-associative-array-key-exists-in-javascript
//        if(key in gArrBusRoutesCache)
//        {
//            return gArrBusRoutesCache[key];
//        }

        if(cacheLookup(key)!=null)
            return cacheLookup(key);

		// construct URL
		var url = "GetBusRouteMarkerDetailsV2.php?sourceStop=" + sourceStop+"&destinationStop="+destinationStop+"&busNumber="+busNumber;

		// since we want to return some values...it has to be done in an synchromous manner. This is the correct way of doing it 
		// chekc http://www.webmasterworld.com/javascript/3483187.htm
		xhr.open("GET", url, false);		
		xhr.send(null);

        gArrBusRoutesCache.push({
            key: key,
            value: xhr.responseXML
        });

		return xhr.responseXML;
      }


    function getBusPointsToDraw2(sourceStop,destinationStop,busNumberWithFrequency)
    {
        var busNumberArray=busNumberWithFrequency.split(":");
        var busNumber=busNumberArray[0];

        key=sourceStop+"^"+destinationStop+"^"+busNumber;
        if(cacheLookup(key)!=null)
            return cacheLookup(key);

        //alert(instrumentId+","+instrumentSerialNumber+","+instrumentFamilyName);
        $.ajax({

                type:"GET",
                url:"GetBusRouteMarkerDetailsV2.php",
                async:false, // This makes it possible
                data:{sourceStop:sourceStop,destinationStop:destinationStop,busNumber:busNumber},
                success:function (data) {
                    gArrBusRoutesCache.push({
                        key: key,
                        value: data
                    });
                    console.log(data);
                    return data;
                },
                error: function(request, status, error) {

                }
            }
        );
        return false;

    }




    function cacheLookup(key)
    {
        for(i=0;i<gArrBusRoutesCache.length;i++)
        {
            if(gArrBusRoutesCache[i].key==key)
                return gArrBusRoutesCache[i].value;
        }
        return null;
    }



var markersArray = [];

    /*
     * Creates a marker for the given business and point
     */
    function createMarker(stopName,stopBuses,point, markerNum,iconType) 
   
	{
        //var infoWindowHtml = generateInfoWindowHtml(biz)
		
		var splitBuses=formatBusFrequencyString(stopBuses).split(",");
		var busString='';
		var len=0;
		if(splitBuses.length>5)
		{
			for(i=0;i<splitBuses.length;i++)
			{
				busString=busString+splitBuses[i]+",";
				if(i%5==4)
					busString=busString+"<br>";
			}
			len=busString.length-2;
		}
		else
		{
			busString=formatBusFrequencyString(stopBuses);
			len=busString.length;
		}

		///11/13/2013 can we also show the buses as per the frequency
		var infoWindowHtml = "<div class='infoWindow' style='overflow:hidden'><span style='font-size:1.2em;font-weight:bold;padding-bottom:10px'>"+stopName+"</span><br/>"+busString.substring(0,len)+"</div>";

       // var marker = new GMarker(point, iconType);
        //map.addOverlay(marker);
		//icon:"http://chart.apis.google.com/chart?chst=d_map_pin_letter&chld="+markerNum+"|FF0000|000000"
		 var marker = new google.maps.Marker({
			position: point,
			map: map,
			icon:iconType
		  });
		
		bounds.extend(point);
		markersArray.push(marker);
        
        
		google.maps.event.addListener(marker, "click", function() {
			infoWindow.setContent(infoWindowHtml);
			infoWindow.open(map, marker);           
        });
        // automatically open first marker
       // if (markerNum == 0)
         //   marker.openInfoWindowHtml(infoWindowHtml, {maxWidth:400});
		//return marker;
    }



	/** create the marker when the source and destination is not in accordance with the stop name
	**/
	function createAddressMarker(addressType,address,iconType)
	{

		var addressDetails=address.split(":");
		var point=new google.maps.LatLng(addressDetails[1],addressDetails[2]);
		 var marker = new google.maps.Marker({
			position: point,
			map: map,
			icon:iconType
		  });
		
		//var marker = new GMarker(point, iconType);
        //map.addOverlay(marker);
		// required to collect data for the proper centering of the map
		//bounds.extend(marker.getPoint());
		bounds.extend(point);
		markersArray.push(marker);
        
		
		var infoWindowHtml = "<div class='infoWindow' style='overflow:hidden'><span style='font-size:1.2em;font-weight:bold;padding-bottom:10px'>"+addressDetails[0]+"</span><br/>"+"</div>";
		//var infoWindowHtml = "(<b>"+addressType+"</b>)<br/>"+addressDetails[0]+"<br/>";
		google.maps.event.addListener(marker, "click", function() {
            infoWindow.setContent(infoWindowHtml);
			infoWindow.open(map, marker); 
        });

	}


function ClearMap()
{
	clearOverlays();
}

function clearOverlays() 
{
  if (markersArray)
	{
		for (var i = 0; i < markersArray.length; i++ ) 
		{
		//thsi will remove from teh map
		  markersArray[i].setMap(null);
		}
	//now use the splice function to remove the entries from the aarray also
   	 markersArray.splice(0,markersArray.length);
	 
  }

 //remove the polylines
  if(polyLines)
	{
	 for(var j=0;j<polyLines.length;j++)
		{
					
			polyLines[j].setMap(null);
		}
	   polyLines.splice(0,polyLines.length);
	}
}


function formatBusFrequencyString(busString)
{
    var BusesArray=busString.split(",");
    var busFormattedString='';
    for(i=0;i<BusesArray.length;i++)
    {
        tempArray=BusesArray[i].split(":");
        str=tempArray[0];
        if(tempArray[1]==8)
            str="<span class='veryHighFreq'>"+tempArray[0]+"</span>";
        if(tempArray[1]==7)
            str="<span class='highFreq'>"+tempArray[0]+"</span>";
        if(tempArray[1]==5)
            str="<span class='mediumFreq'>"+tempArray[0]+"</span>";
        if(tempArray[1]==1)
            str="<span class='lowFreq'>"+tempArray[0]+"</span>";
        if(tempArray[1]==0)
            str="<span class='rareFreq'>"+tempArray[0]+"</span>";

        busFormattedString=busFormattedString+str+",";
    }
    return renameBIASBusesToKIAS(busFormattedString);
}


function renameBIASBusesToKIAS(busString)
{
    var find = 'BIAS';
    var re = new RegExp(find, 'g');
    str = busString.replace(re, 'KIA');
    return str;
}