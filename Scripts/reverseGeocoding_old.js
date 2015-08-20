
    var map;
    var geocoder;
    var address;
	var addressCoordinates;
	
    function initialize() {
      map = new GMap2(document.getElementById("map_canvas"));
      map.setCenter(new GLatLng(12.97556,77.60827), 15);
	  map.addControl(new GSmallMapControl());	
	  //map.setUIToDefault();
      GEvent.addListener(map, "click", getAddress);
      geocoder = new GClientGeocoder();
		
	 // define the icon for the junction1
		iconJunc1 = new GIcon();
        //iconDest.image = "images/pinup.png";
		iconJunc1.image="images/pinupred.png";
        iconJunc1.iconSize = new GSize(20, 29);
        iconJunc1.iconAnchor = new GPoint(15, 29);
        iconJunc1.infoWindowAnchor = new GPoint(15, 3);

    }
    
    function getAddress(overlay, latlng) {
      if (latlng != null) {
        address = latlng;
        geocoder.getLocations(latlng, showAddress);
		/*marker = new GMarker(latlng);
        map.addOverlay(marker);
		// var infoWindowHtml = stopName;
					
		GEvent.addListener(marker, "click", function() {
		marker.openInfoWindowHtml("sss", {maxWidth:400});
		});
		
		*/
		PlotStopsInVicinity(latlng);

      }
    }

    function showAddress(response) {
      map.clearOverlays();
      if (!response || response.Status.code != 200) {
        alert("Status Code:" + response.Status.code);
      } else {
        place = response.Placemark[0];
        point = new GLatLng(place.Point.coordinates[1],
                            place.Point.coordinates[0]);
        marker = new GMarker(point);
        map.addOverlay(marker);
		var info=  "<b>orig latlng:</b>" + response.name + "<br/>"+ 
        '<b>latlng:</b>' + place.Point.coordinates[1] + "," + place.Point.coordinates[0] + '<br>' +
        '<b>Status Code:</b>' + response.Status.code + '<br>' +
        '<b>Status Request:</b>' + response.Status.request + '<br>' +
        '<b>Address:</b>' + place.address + '<br>' +
        '<b>Accuracy:</b>' + place.AddressDetails.Accuracy + '<br>' +
        '<b>Country code:</b> ' + place.AddressDetails.Country.CountryNameCode;

		GEvent.addListener(marker, "click", function() {
		marker.openInfoWindowHtml(info, {maxWidth:400});
		});
       /* marker.openInfoWindowHtml(
        '<b>orig latlng:</b>' + response.name + '<br/>' + 
        '<b>latlng:</b>' + place.Point.coordinates[1] + "," + place.Point.coordinates[0] + '<br>' +
        '<b>Status Code:</b>' + response.Status.code + '<br>' +
        '<b>Status Request:</b>' + response.Status.request + '<br>' +
        '<b>Address:</b>' + place.address + '<br>' +
        '<b>Accuracy:</b>' + place.AddressDetails.Accuracy + '<br>' +
        '<b>Country code:</b> ' + place.AddressDetails.Country.CountryNameCode);
		*/
		//addressCoordinates=point;
		//PlotStopsInVicinity(point);
		
      }
    }


	  /*
     * Creates a marker for the given business and point
     */
    function createMarker(stopName,stopBuses,point,iconType) {
        //var infoWindowHtml = generateInfoWindowHtml(biz)
		
		/*var splitBuses=stopBuses.split(",");
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
		}*/

		var marker = new GMarker(point);
					// var infoWindowHtml = stopName;
					 map.addOverlay(marker);
					GEvent.addListener(marker, "click", function() {
					marker.openInfoWindowHtml(stopName, {maxWidth:400});
					});

		
		/*var infoWindowHtml = stopName+"<br/>"+busString.substring(0,len);

        var marker = new GMarker(point, iconType);
        map.addOverlay(marker);
		// required to collect data for the proper centering of the map
		bounds.extend(marker.getPoint());
        
		GEvent.addListener(marker, "click", function() {
            marker.openInfoWindowHtml(infoWindowHtml, {maxWidth:400});
        });
        // automatically open first marker
        if (markerNum == 0)
            marker.openInfoWindowHtml(infoWindowHtml, {maxWidth:400});*/
		//return marker;
    }

	function ClearMap()
	{
		map.clearOverlays();
		map.setCenter(new GLatLng(12.97556,77.60827),13);
	}


		/**
		* function displays the stop names
		**/
        function PlotStopsInVicinity(point)
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

				// get symbol
				var sourceAddressLat = point.y;
				var sourceAddressLong = point.x;

			
				// construct URL
				var url = "GetBusStopsUsingMap.php?sourceLat=" +sourceAddressLat+"&sourceLong="+sourceAddressLong;

				xhr.open("GET", url, false);
				xhr.send(null);
				//return xhr.responseXML;
				plotPoints(xhr.responseXML);

		}


		function plotPoints(xml)
		{

			
			var errorStatuses=xml.getElementsByTagName("ErrorCode");
			//alert(errorStatuses);
			var infoMessages=xml.getElementsByTagName("Info");

				// This means that Address is not valid
			if(errorStatuses[0].firstChild.textContent=="NA")
			{
				alert("no bus stop found");
			}

			else
			{
				// check this http://you.arenot.me/2010/06/29/google-maps-api-v3-0-multiple-markers-multiple-infowindows/ this should help the problem of the marker window
					
			   var markers = [];
				var busStops = xml.getElementsByTagName("Stop");
				for(i=0;i<busStops.length;i++)
				{
					var stopName = busStops[i].firstChild.textContent;
					var stopLatitude=busStops[i].childNodes[1].textContent;
					var stopLongitude=busStops[i].childNodes[2].textContent;
					var stopOffset=busStops[i].childNodes[3].textContent;
					var stopBuses=busStops[i].childNodes[4].textContent;
					var stopPoint=new GLatLng(stopLatitude, stopLongitude);
						
					//alert(stopPoint);
					createMarker(stopName,stopBuses,stopPoint,iconJunc1);
					
					/* var marker = new GMarker(stopPoint);
					// var infoWindowHtml = stopName;
					 map.addOverlay(marker);
					GEvent.addListener(marker, "click", function() {
					marker.openInfoWindowHtml("kkk", {maxWidth:400});
					});
*/

					// marker.openInfoWindowHtml(infoWindowHtml);
					
					//alert( "sdsds");
					
					//var infowindow = new google.maps.InfoWindow();

					//var marker;

					 /* marker = new google.maps.Marker({
						position: new google.maps.LatLng(stopLatitude, stopLongitude),
						map: map
					  });

					  google.maps.event.addListener(marker, 'click', (function(marker, i) {
						return function() {
						  infowindow.setContent(stopLongitude);
						  infowindow.open(map, marker);
						}
					  })(marker, i));
					*/






				}

			}				
				
        }
		


    