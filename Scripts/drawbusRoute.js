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
		//busNumber=document.getElementById("busNumber").value;
		//alert(busNumber);
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
		//var data=getBusPointsToDraw('V500D');
		//handleResults(data);
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
    function handleResults(data)     
	{
		map.clearOverlays();
		
		
		//create the source node
		var busStops = data.getElementsByTagName("Routes");
		var numStops=busStops[0].childElementCount;
		var stopPoints=[];
		for(i=0;i<numStops;i++)
		{
			splitData=busStops[0].childNodes[i].textContent.split(":");
			var stopPoint= new GLatLng( splitData[1],splitData[2]);
			stopPoints.push(stopPoint);
			createMarker(splitData[0],busStops[0].childNodes[i].textContent,stopPoint, 1,icon);
		}

		map.addOverlay(new GPolyline(stopPoints,"#571B7e"));

    }



    
	function getBusPointsToDraw()
	{
		var busNumber=document.getElementById("busNumber").value;

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

		// construct URL
		var url = "GetBusRoute.php?BusNumber=" + busNumber;
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
		handleResults(xhr.responseXML);
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