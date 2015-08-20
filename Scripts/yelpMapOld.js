	var YWSID = "nYpPIFyOGulOjVWMvG7sgw"; // common required parameter (api key)
    var URBANKEY="ubwecy934e8hkzvq6mehnedv"; // urbammapping
    var map = null;
    var icon = null;
    
	//var markerForOriginalSource=null;
    
	//var markerForOriginalDestination=null;


function addLoadEvent(func) {
  var oldonload = window.onload;
  if (typeof window.onload != 'function') {
    window.onload = func;
  } else {
    window.onload = function() {
      oldonload();
      func();
    }
  }
}

addLoadEvent(load_method);

    /*
     * Creates the map object and calls setCenterAndBounds
     * to instantiate it.
     */
    function load_method() 
    {
		 map = new GMap2(document.getElementById("map"));
		
		// center the map initiallya at MG road
        map.setCenter(new GLatLng(12.97556,77.60827),13);
       // map.addControl(new GLargeMapControl());
	    GEvent.addListener(map, "load", function() {updateMap();});  
		map.addControl(new GSmallMapControl());
        map.addControl(new GMapTypeControl());
        map.setMapType(G_NORMAL_MAP);
       // alert("rohit");
		if (window.attachEvent) window.attachEvent("onresize", function() { map.checkResize()} );
        else if (window.addEventListener) window.addEventListener("resize", function() { map.checkResize()}, false);

        // setup our marker icon
        icon = new GIcon();
        icon.image="images/icon_greenA.png";
		//icon.image="images/letter_a.png";
		//icon.shadow = "images/marker_shadow.png";
		//icon.shadow="images/icon_greenA.png";
        icon.iconSize = new GSize(20, 29);
        icon.shadowSize = new GSize(38, 29);
        icon.iconAnchor = new GPoint(15, 29);
        icon.infoWindowAnchor = new GPoint(15, 3);
		//define icon for the destination
		iconDest = new GIcon();
       	iconDest.image="images/icon_greenB.png";
       	//iconDest.image="images/letter_b.png";
		iconDest.iconSize = new GSize(20, 29);
        iconDest.iconAnchor = new GPoint(15, 29);
        iconDest.infoWindowAnchor = new GPoint(15, 3);

		// define the icon for the junction1
		iconJunc1 = new GIcon();
        iconJunc1.image="images/pinupred.png";
		//iconJunc1.image="images/letter_j.png";
        iconJunc1.iconSize = new GSize(20, 29);
        iconJunc1.iconAnchor = new GPoint(15, 29);
        iconJunc1.infoWindowAnchor = new GPoint(15, 3);

		// define the icon for the junction2
		iconJunc2 = new GIcon();
        iconJunc2.image="images/pinupblue.png";
        //iconJunc2.image="images/letter_j_pink.png";        
		iconJunc2.iconSize = new GSize(20, 29);
        iconJunc2.iconAnchor = new GPoint(15, 29);
        iconJunc2.infoWindowAnchor = new GPoint(15, 3);

		// define the icon for the junction2
		iconOriginalSource = new GIcon();
        //iconOriginalSource.image="images/purple.png";
        iconOriginalSource.image="images/arrow.png";        
		iconOriginalSource.iconSize = new GSize(39, 34);
        iconOriginalSource.iconAnchor = new GPoint(15, 29);
        iconOriginalSource.infoWindowAnchor = new GPoint(15, 3);

		iconOriginalDestination = new GIcon();
		//iconOriginalDestination.image="images/yellow.png";
        iconOriginalDestination.image="images/arrow.png";        
		iconOriginalDestination.iconSize = new GSize(39, 34);
        iconOriginalDestination.iconAnchor = new GPoint(15, 29);
        iconOriginalDestination.infoWindowAnchor = new GPoint(15, 3);
	

		
		bounds = new GLatLngBounds();
    }

   
    function updateMap()
	{
		//var map=map.getBounds();
		
		//map.panTo(new GLatLng(map.getBounds().getSouthWest().lat(),map.getBounds().getSouthWest().lon())); 
		
		//alert("moved");
	}
    
	function ClearMap()
	{
	
		/*if(markerForOriginalSource!=null)
			
		{
			markerForOriginalSource.hide();
			map.removeOverlay(markerForOriginalSource);

		}
			
		if(markerForOriginalDestination!=null)
			
		{
			markerForOriginalDestination.hide();
			alert(markerForOriginalDestination.isHidden());
			map.removeOverlay(markerForOriginalDestination);
		
		
		}
				
		
		markerForOriginalSource=null;
		*/
		map.clearOverlays();
		map.setCenter(new GLatLng(12.97556,77.60827),13);
		
		
	}



	function PlotWalkableMap(walkSourceName,walkSourceLat,walkSourceLon,walkDestName,walkDestLat,walkDestLon,walkDistance)
	{
		var points=[];
		var sourcePoint= new GLatLng( walkSourceLat,walkSourceLon);
		createMarker(walkSourceName,'',sourcePoint, 1,icon);
		points.push(sourcePoint);

		var destPoint=new GLatLng( walkDestLat,walkDestLon);
		createMarker(walkDestName,'',destPoint, 2,iconDest);
		points.push(destPoint);

		map.setZoom(map.getBoundsZoomLevel(bounds));
		map.setCenter(bounds.getCenter());
		map.addOverlay(new GPolyline(points,"#FF0000"));
	
	}



    /*
     * Called on the form submission: updates the map by
     * placing markers on it at the appropriate places
     */
    function PlotMap(data,junctionData,busRoute,originalSource,originalDestination)
    
	{
		map.clearOverlays();
		handleResults(data,junctionData,busRoute,originalSource,originalDestination);
		//updateMap();
		map.setZoom(map.getBoundsZoomLevel(bounds));
		map.setCenter(bounds.getCenter());
		//map.addOverlay(new GPolyline(points,"#FF0000"));
    }

	
		

    /*
     * If a sucessful API response is received, place
     * markers on the map.  If not, display an error.
     */
    function handleResults(data,junctionData,busRoute,originalSource,originalDestination) 
    
	{
		map.clearOverlays();
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
		var sourcePoint= new GLatLng( sourceStopLatitude,sourceStopLongitude);		
		createMarker(sourceStopName,sourceBuses,sourcePoint, 1,icon);
		
		//create the destination node
		var destStopName = busStops[1].firstChild.textContent;
		var destStopLatitude=busStops[1].childNodes[1].textContent;
		var destStopLongitude=busStops[1].childNodes[2].textContent;
		var destStopOffset=busStops[1].childNodes[3].textContent;
		var destBuses=busStops[1].childNodes[4].textContent;
		var destPoint=new GLatLng( destStopLatitude,destStopLongitude);
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
			var firstJunctionPoint= new GLatLng( firstJunctionLat,firstJunctionLon);
			createMarker(firstJunctionName,sourceBuses,firstJunctionPoint, 1,iconJunc1);
			//points.push(firstJunctionPoint);
			
			var secJunctionName=junctionArray[3];
			var secJunctionLat=junctionArray[4];
			var secJunctionLon=junctionArray[5];
			var secJunctionPoint= new GLatLng( secJunctionLat,secJunctionLon);
			createMarker(secJunctionName,busStops[1].childNodes[4].textContent,secJunctionPoint, 1,iconJunc2);
			//points.push(secJunctionPoint);
			
		  // add the points
		  
			var busRoutsForPlotting=busRoute.split("^");
			// draw the route between the first junction and the 
			var firstjunctionPlottingData=getBusPointsToDraw(sourceStopName,firstJunctionName,busRoutsForPlotting[0]);
			var firstJunctionPlottingMarkersLatitudes=firstjunctionPlottingData.getElementsByTagName("Latitude");
			var firstJunctionPlottingMarkersLongitudes=firstjunctionPlottingData.getElementsByTagName("Longitude");
			var numberOfMarkersFromFirstJunction=firstjunctionPlottingData.getElementsByTagName("Route");
			for(var i=0;i<numberOfMarkersFromFirstJunction.length;i++)
			{
			tracePoint=new GLatLng(firstJunctionPlottingMarkersLatitudes[i].firstChild.textContent,firstJunctionPlottingMarkersLongitudes[i].firstChild.textContent);
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
				tracePoint=new GLatLng(secondJunctionPlottingMarkersLatitudes[i].firstChild.textContent,secondJunctionPlottingMarkersLongitudes[i].firstChild.textContent);
				SecondJunctionPoints.push(tracePoint);
				}
				map.addOverlay(new GPolyline(SecondJunctionPoints,"#571B7e"));
			}
			map.addOverlay(new GPolyline(FirstJunctionPoints,"#FF0000"));
			
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
				tracePoint=new GLatLng(plottingMarkersLatitudes[i].firstChild.textContent,plottingMarkersLongitudes[i].firstChild.textContent);
				points.push(tracePoint);

			}

			map.addOverlay(new GPolyline(points,"#FF0000"));

		}
			
	

		

	
		

    }


	    /*
     * Formats and returns the Info Window HTML 
     * (displayed in a balloon when a marker is clicked)
     */
    function generateInfoWindowHtml(biz) 
    
	{
        var text = '<div class="marker">';

        // image and rating
        text += '<img class="businessimage" src="'+biz.photo_url+'"/>';

        // div start
        text += '<div class="businessinfo">';
        // name/url
        text += '<a href="'+biz.url+'" target="_blank">'+biz.name+'</a><br/>';
        // stars
        text += '<img class="ratingsimage" src="'+biz.rating_img_url_small+'"/>&nbsp;based&nbsp;on&nbsp;';
        // reviews
        text += biz.review_count + '&nbsp;reviews<br/><br />';
        // categories
        text += formatCategories(biz.categories);
        // neighborhoods
        if(biz.neighborhoods.length)
            text += formatNeighborhoods(biz.neighborhoods);
        // address
        text += biz.address1 + '<br/>';
        // address2
        if(biz.address2.length) 
            text += biz.address2+ '<br/>';
        // city, state and zip
        text += biz.city + ',&nbsp;' + biz.state + '&nbsp;' + biz.zip + '<br/>';
        // phone number
        if(biz.phone.length)
            text += formatPhoneNumber(biz.phone);
        // Read the reviews
        text += '<br/><a href="'+biz.url+'" target="_blank">Read the reviews �</a><br/>';
        // div end
        text += '</div></div>'
        return text;
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
		// construct URL
		var url = "GetBusRouteMarkerDetails.php?sourceStop=" + sourceStop+"&destinationStop="+destinationStop+"&busNumber="+busNumber;
		//alert(url);
		// get quote
		/*xhr.onreadystatechange =
		function()
		{
			// only handle loaded requests
			if (xhr.readyState == 4)
			{
				if (xhr.status == 200)
				{
					return xhr.responseText;
					




				}
				else
					alert("Error with Ajax call!");
			}
		}*/

		// since we want to return some values...it has to be done in an synchromous manner. This is the correct way of doing it 
		// chekc http://www.webmasterworld.com/javascript/3483187.htm
		xhr.open("GET", url, false);		
		xhr.send(null);
		return xhr.responseXML;
      }

  
    /*
     * Creates a marker for the given business and point
     */
    function createMarker(stopName,stopBuses,point, markerNum,iconType) 
   
	{
        //var infoWindowHtml = generateInfoWindowHtml(biz)
		
		var splitBuses=stopBuses.split(",");
		var busString='';
		var len=0;
		if(splitBuses.length>5)
		{
			for(i=0;i<splitBuses.length;i++)
			{
				busString=busString+splitBuses[i]+",";
				if(i%8==7)
					busString=busString+"<br>";
			}
			len=busString.length-2;
		}
		else
		{
			busString=stopBuses;
			len=busString.length;
		}

		
		var infoWindowHtml = stopName+"<br/>"+busString.substring(0,len);

        var marker = new GMarker(point, iconType);
        map.addOverlay(marker);
        
		// required to collect data for the proper centering of the map
		bounds.extend(marker.getPoint());
        
		GEvent.addListener(marker, "click", function() {
            marker.openInfoWindowHtml(infoWindowHtml, {maxWidth:400});
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
		var point=new GLatLng(addressDetails[1],addressDetails[2]);
		var marker = new GMarker(point, iconType);
        map.addOverlay(marker);
		// required to collect data for the proper centering of the map
		bounds.extend(marker.getPoint());
		var infoWindowHtml = "(<b>"+addressType+"</b>)<br/>"+addressDetails[0]+"<br/>";
		GEvent.addListener(marker, "click", function() {
            marker.openInfoWindowHtml(infoWindowHtml, {maxWidth:400});
        });

	}