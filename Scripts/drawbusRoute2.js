/** make use of google v3 to draw the routes also shows the driving directions **/


/*function addLoadEvent(func) {
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
*/
 var map = null;
 var icon = null;
 var bounds;
//addLoadEvent(getZillowRequest);
//addLoadEvent(handleTruliaCallback_CSS);
//addLoadEvent(initialize);

	
	var map;
	var infoWindow;
	var pathArrayForTesting;
	var directionsService;
	var directionsDisplay;
	var geoCoder;
	var stopPoints=[];
	var polylinePath;
	 function initialize()
	 {
        var dMap = document.getElementById("map");
		directionsService = new google.maps.DirectionsService();
		directionsDisplay = new google.maps.DirectionsRenderer({'draggable':true});


        map = new google.maps.Map(dMap,  {
                zoom: 13,
                center: new google.maps.LatLng(12.97556,77.60827),
                mapTypeId : google.maps.MapTypeId.ROADMAP
        });
		
		infoWindow = new google.maps.InfoWindow();
		/*var fence= GetGeoFenceFromDB();
		//alert(fence);
		// create teh polygon from the encoded string
        var shape=new google.maps.Polygon( {map:map, paths: google.maps.geometry.encoding.decodePath(fence), strokeColor: '#000088', strokeOpacity: 0.7, strokeWeight: 2, fillColor:'#000088', fillOpacity:0.2} );
		
		// this way the map will be centered
		var paths=google.maps.geometry.encoding.decodePath(fence);
		pathArrayForTesting=paths;
		//alert(paths.toString());
		*/
	    bounds = new google.maps.LatLngBounds();
	    
		/*for (i = 0; i < paths.length; i++)
		{
			bounds.extend(paths[i]);
		}*/
		
		clearOverlays();
		//alert(bounds.getCenter());
		map.panTo(bounds.getCenter());
		directionsDisplay.setMap(map);
		//geocoder = new GClientGeocoder();


	}

	google.maps.event.addDomListener(window, 'load', initialize);
	
	

	
	// function to plot the markers on the map withing the geo fence
	function plotOnFence(data)
	{
		clearOverlays();
		//get the list of the points to be plotted 
	

	//create the source node
		var busStops = data.getElementsByTagName("Routes");
		var numStops=busStops[0].childElementCount;
		
		for(i=0;i<numStops;i++)
		{
			splitData=busStops[0].childNodes[i].textContent.split(":");
			var stopPoint= new google.maps.LatLng( splitData[1],splitData[2]);
			stopPoints.push(stopPoint);
			//createMarker(splitData[0],busStops[0].childNodes[i].textContent,stopPoint, 1);
			stopNum=i+1;
			createMarker(stopPoint,stopNum,splitData[0],splitData[1],splitData[2]);
		}
		
		calcRoute(stopPoints[0],stopPoints[numStops-1]);

		/*for(i=0;i<numStops-1;i++)
		{
			start=stopPoints[i];
			end=stopPoints[i+1];
			alert(start);
			calcRoute(start,end);
		}*/


		polylinePath = new google.maps.Polyline({
			path: stopPoints,
			strokeColor: '#FF00FF',
			strokeOpacity: 1.0,
			strokeWeight: 4
		  });

		  polylinePath.setMap(map);
		
		//create the marker
		
		map.panTo(bounds.getCenter());

	 }


//drwa the driving route based on the source and destination
function calcRoute(start,end) {
  //var start = document.getElementById('start').value;
  //var end = document.getElementById('end').value;
  var request = {
      origin:start,
      destination:end,
		  durationInTraffic:false, //required to control the route otherwise it is not allowing some routes which might be little time taking due to traffic
	  
      travelMode: google.maps.DirectionsTravelMode.DRIVING,
		  optimizeWaypoints:false
  };
  directionsService.route(request, function(response, status) {
    if (status == google.maps.DirectionsStatus.OK) {
      directionsDisplay.setDirections(response);
    }
  });
}



//	createMarker(splitData[0],busStops[0].childNodes[i].textContent,stopPoint, 1);
// function to create tahe marker on the map
	function createMarker(position, number,status,lat,lon) {
		 
		 
		 //create the marker
		  var marker = new google.maps.Marker({
			position: position,
			map: map,
			icon:"http://chart.apis.google.com/chart?chst=d_map_pin_letter&chld="+number+"|FF0000|000000"
		  });
			
			bounds.extend(position);
			markersArray.push(marker);
		  //add the event handler on the marker
		  google.maps.event.addListener(marker, 'click', function() {
			var myHtml = '<strong>'+ status + '<br/>'+lat+' '+lon+'</strong><br/>';
			infoWindow.setContent(myHtml);
			infoWindow.open(map, marker);
		  });
		}
	

var markersArray = [];

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
	 removeLines();
  }
}


function removeLines() {
      if (stopPoints) {
           stopPoints.length = 0;
      }
      stopPoints = [];
	  if(polylinePath)
		polylinePath.setMap(null);
    }

var data = {};
function saveWayPoints()
{

	var w=[],wp;
	var rleg = directionsDisplay.directions.routes[0].legs[0];
	data.start = {'lat': rleg.start_location.lat(), 'lng':rleg.start_location.lng()}
	data.end = {'lat': rleg.end_location.lat(), 'lng':rleg.end_location.lng()}
	var wp = rleg.via_waypoints	
	for(var i=0;i<wp.length;i++)w[i] = [wp[i].lat(),wp[i].lng()]	
	data.waypoints = w;
	//alert(data);
	var str = JSON.stringify(data)
	console.log(str);
	document.getElementById('waypoints').value=str;
	/*
	var jax = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
	jax.open('POST','process.php');
	jax.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
	jax.send('command=save&mapdata='+str)
	jax.onreadystatechange = function(){ if(jax.readyState==4) {
		if(jax.responseText.indexOf('bien')+1)alert('Updated');
		else alert(jax.responseText)
	}}
	*/
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
		

		// since we want to return some values...it has to be done in an synchromous manner. This is the correct way of doing it 
		// chekc http://www.webmasterworld.com/javascript/3483187.htm
		xhr.open("GET", url, false);		
		xhr.send(null);
		plotOnFence(xhr.responseXML);
      }

