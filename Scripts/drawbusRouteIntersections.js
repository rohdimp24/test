/** make use of google v3 to draw the INTERSECTION POINTS ON THE map **/

var map = null;
var icon = null;
var bounds;

var map;
var infoWindow;
var pathArrayForTesting;
var directionsService;
var directionsDisplay;
var geoCoder;
var firstBusStops=[];
var secondBusStops=[];
var polylinePath1;
var polylinePath2;
var markersArray = [];
var marker_locations=[];

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


/**
 * This function will create the intersection lines.
 * it will plot the markers for the first route and then the markers for the second route
 */
function plotOnFence()
{

    //get the list of the points to be plotted
    clearOverlays();
	clearIntersections();
    removeLines();
    var startBus=document.getElementById("startBusNumber").value;
    var endBus=document.getElementById("endBusNumber").value;
    var busStops;
    var numStops;

    //find the stops for the first bus
    firstBusData=getBusPointsToDraw(startBus);
    busStops = firstBusData.getElementsByTagName("Routes");
    numStops=busStops[0].childElementCount;
	firstStopData='';
	lastStopdata=''
    for(i=0;i<numStops;i++)
    {
        splitData=busStops[0].childNodes[i].textContent.split(":");
        var stopPoint= new google.maps.LatLng( splitData[1],splitData[2]);
        firstBusStops.push(stopPoint);
        stopNum=i+1;
        createMarker(stopPoint,stopNum,splitData[0],splitData[1],splitData[2],'FF0000');
		if(i==0)
			firstStopData=splitData[0];
		if(i==(numStops-1))
			lastStopdata=splitData[0];
    }
    //draw the line
    polylinePath1 = new google.maps.Polyline({
        path: firstBusStops,
        strokeColor: 'BF0000',
        strokeOpacity: 1.0,
        strokeWeight: 4
    });

	document.getElementById("firstBusDetails").value=startBus+","+firstStopData+','+lastStopdata;



    //now for the second bus
    endBusData=getBusPointsToDraw(endBus);
    busStops = endBusData.getElementsByTagName("Routes");
    numStops=busStops[0].childElementCount;
    for(i=0;i<numStops;i++)
    {
        splitData=busStops[0].childNodes[i].textContent.split(":");
        var stopPoint= new google.maps.LatLng( splitData[1],splitData[2]);
        secondBusStops.push(stopPoint);
        stopNum=i+1;
        createMarker(stopPoint,stopNum,splitData[0],splitData[1],splitData[2],'2B65EC');
		if(i==0)
			firstStopData=splitData[0];
		if(i==(numStops-1))
			lastStopdata=splitData[0];
    }


	document.getElementById("secondBusDetails").value=endBus+","+firstStopData+','+lastStopdata;

    //draw the second line
    polylinePath2 = new google.maps.Polyline({
        path: secondBusStops,
        strokeColor: '#886668',
        strokeOpacity: 1.0,
        strokeWeight: 4
    });


    polylinePath1.setMap(map);
    polylinePath2.setMap(map);
	
	//getStartAndEndPoints(startBus,end


    map.panTo(bounds.getCenter());

}

//	createMarker(splitData[0],busStops[0].childNodes[i].textContent,stopPoint, 1);
// function to create tahe marker on the map
function createMarker(position, number,status,lat,lon,color) {

	var marker;
	//if the marker is alaresy in the map then it is the intersection. Chnage the color
	/*if(marker_locations.indexOf(position.toString())>0)
	{
		 marker = new google.maps.Marker({
        position: position,
        map: map,
        icon:"http://chart.apis.google.com/chart?chst=d_map_pin_letter&chld="+number+"|"+454545+"|000000"
		});
	}
	else
	*/
	//{

		//create the marker
		marker = new google.maps.Marker({
			position: position,
			map: map,
			icon:"http://chart.apis.google.com/chart?chst=d_map_pin_letter&chld="+number+"|"+color+"|000000"
		});
		marker_locations.push(position.toString());
	//}

    bounds.extend(position);
    markersArray.push(marker);	
	
    //add the event handler on the marker
    google.maps.event.addListener(marker, 'click', function() {
        var myHtml = '<strong>'+ status + '<br/>'+lat+' '+lon+'</strong><br/>';
        infoWindow.setContent(myHtml);
        infoWindow.open(map, marker);
    });
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
		
		


        removeLines();
    }
	
}


function clearIntersections()
{
	if(marker_locations)
	{
		markersArray.splice(0,marker_locations.length);
		for(var i=0;i<marker_locations.length;i++)
		{
			marker_locations.pop();
		}
		 
	}
}


function removeLines() {
    if (firstBusStops) {
        firstBusStops.length = 0;
    }
    firstBusStops = [];
    if (secondBusStops) {
        secondBusStops.length = 0;
    }
    secondBusStops = [];

    if(polylinePath1)
        polylinePath1.setMap(null);
    if(polylinePath2)
        polylinePath2.setMap(null);
}

var data = {};


function getInsectionBusPointsToDraw()
{
    /*clearOverlays();
     removeLines();
     var startBus=document.getElementById("startBusNumber").value;
     var endBus=document.getElementById("endBusNumber").value;

     getBusPointsToDraw(startBus,'#FFA500');

     getBusPointsToDraw(endBus,'#008000');
     */
    plotOnFence();

}



function getBusPointsToDraw(busNumber,polyLineColor)
{
    //var busNumber=document.getElementById("busNumber").value;

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
    //plotOnFence(xhr.responseXML,polyLineColor);
    return xhr.responseXML;
}

function getStartAndEndPoints(startBus,EndBus)
{
    //var busNumber=document.getElementById("busNumber").value;

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
    var url = "GetStartAndEndPoints.php?StartBusNumber=" + startBus+"&EndBusNumber="+endBus;
    // since we want to return some values...it has to be done in an synchromous manner. This is the correct way of doing it
    // chekc http://www.webmasterworld.com/javascript/3483187.htm
    xhr.open("GET", url, false);
    xhr.send(null);
    //plotOnFence(xhr.responseXML,polyLineColor);
    return xhr.responseXML;
}



