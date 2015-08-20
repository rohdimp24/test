
        // an XMLHttpRequest
        var xhr = null;

		function GetNormalizedDistance(distance)
		{
			var convertDistance=0;
			if(distance < 1)
			{	
				convertDistance=distance*1000;				
				return convertDistance.toFixed(0)+ " meters";
			}
			else
			{
				convertDistance=distance*1;
				
				return convertDistance.toFixed(2)+" KM";
			}


		}

		// function to escape specail characters
		function HtmlEncode(s)
		{
		  var el = document.createElement("div");
		  el.innerText = el.textContent = s;
		  s = el.innerHTML;
		  delete el;
		  return s;
		}

		function ClearDisplayArea()
		{
			
			document.getElementById("sourceStop").innerHTML = "";
			document.getElementById("destinationStop").innerHTML = "";
			document.getElementById("searchStatus").innerHTML = "";
			document.getElementById("routeDetails").innerHTML = "";


		}

		function displayResultString(str)
		{
			str += "<div class='wax-legend'>"+
				"<div class='legend-scale'>"+
				"<ul class='legend-labels'>"+
					"<li><span style='background:#006600'></span>Frequent</li>"+
					"<li><span style='background:rgba(31,255,0,1);'></span>High</li>"+
					"<li><span style='background:rgba(0,0,255,0.78);'></span>Medium</li>"+
					"<li><span style='background:darkorange'></span>Low</li>"+
					"<li><span style='background:red;'></span>Rare</li>"+
			  "</ul>"+
			"</div>"+
			"</div><br/>";
			return str;
		}

        function formatBusString(busString)
        {
            var BusesArray=busString.split(",");
            var busFormattedString='';
            for(i=0;i<BusesArray.length;i++)
            {
                tempArray=BusesArray[i].split(":");
                str=tempArray[0];
                busFormattedString=busFormattedString+str+",";
            }
            return busFormattedString;
        }


        function getJunctionIconsForList(firstJunctionName,secondJunctionName)
        {
            var iconJunc1="images/ChartApi/J1s.png";
            var iconJunc2="images/ChartApi/J2s.png";
            var iconJunc="images/ChartApi/Js.png";
            if(firstJunctionName==secondJunctionName)
                return iconJunc+":"+iconJunc;
            else
                return iconJunc1+":"+iconJunc2;
        }

        function renameBIASBusesToKIAS(busString)
        {
            var find = 'BIAS';
            var re = new RegExp(find, 'g');
            str = busString.replace(re, 'KIA');
            return str;
        }



        function formatBusFrequency(busString)
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
            return busFormattedString;
        }

		function getFormattedFrequecyForDirectBus(busNumber,frequency)
        {
            busNumber=renameBIASBusesToKIAS(busNumber);
            if(frequency==8)
                str="<span class='veryHighFreq'>"+busNumber+ "</span>";
            if(frequency==7)
                str="<span class='highFreq'>"+busNumber+ "</span>";
            if(frequency==5)
                str="<span class='mediumFreq'>"+busNumber+ "</span>";
            if(frequency==1)
                str="<span class='lowFreq'>"+busNumber+ "</span>";
            if(frequency==0)
                str="<span class='rareFreq'>"+busNumber+ "</span>";
            return str;
        }


        function getBusesForDisplayAfterTruncation(buses)
        {
            var tempBusesArray=buses.split(",");
            var displayBuses='';
            if(tempBusesArray.length>5)
            {
                displayBuses=tempBusesArray[0]+","+tempBusesArray[1]+","+tempBusesArray[2]+","+
                    tempBusesArray[3]+","+tempBusesArray[4];
            }
            else
                displayBuses=buses;

            return displayBuses;
        }

		


		var gXML=null;
		//var gIndirectRouteFindingXML;
		var gJunctionDetailsArray;
		var gJunctionRouteDetailsArray;
		var gTotalOptions;
		var gUserEnteredStartingAddress=null;
		var gUserEnteredDestinationAddress=null;
        var gDepotJunctionDetailsArray=new Array();
        var gDepotJunctionRouteDetailsArray=new Array();
        var gDisplayBusesForMarkers=new  Array();
        var gStartStopBuses=new Array();
        var gEndStopBuses=new Array();
        var gStartStop=null;
        var gEndStop=null;

		// this will be used to change the background of teh selected option
		function changeOption(optionNumber,errorCode)
		{
			//alert(optionNumber.toString());
			//return true;
			var correctNum=optionNumber-1;
            if(errorCode<9)
			    PlotMap(gXML,gJunctionDetailsArray[correctNum],gJunctionRouteDetailsArray[correctNum],gBusesToDisplayInToolTipArray[correctNum],gUserEnteredStartingAddress,gUserEnteredDestinationAddress);
            else
                plotIndirectDepotMap(gXML,gStartStop,gEndStop,gDepotJunctionDetailsArray[correctNum],
                    gDepotJunctionRouteDetailsArray[correctNum],gDisplayBusesForMarkers[correctNum],gUserEnteredStartingAddress,gUserEnteredDestinationAddress,
                    errorCode);

			for(i=0;i<gTotalOptions;i++)
			{
				if(i==correctNum)
				{

				//document.getElementsByName("optionDetails")[correctNum].className = "filledBackground";
				var id="optionDetails"+optionNumber.toString();
				var e=document.getElementById(id);
				e.setAttribute('class', 'selectedBackground');

                $("img","#"+id).each(function () {
                    $(this).css('display','inline');
                });

				}
				else
				{
					var temp=i+1;
					var id="optionDetails"+temp.toString();
					var e=document.getElementById(id);
					e.setAttribute('class', 'noSelectedBackground');
                    $("img","#"+id).each(function () {
                        $(this).css('display','none');
                    });
				}

			}
		}
		
		function redirectCall(sourceStopName,destStopName)
		{
			alert(sourceStopName+destStopName);
			//GetRouteInformation(sourceStopName,destStopName,sourceStopOffset,destStopOffset,xml,onlyIndirectRoutes);

		}

		/**
		* function displays the stop names
		**/
        function DisplayStopNames()
        {
			//clean up the global variable
			 gUserEnteredStartingAddress=null;
			 gUserEnteredDestinationAddress=null;


			// check for the browser
				var browserName=BrowserDetect.browser;
				if(browserName=="Explorer")
				{
					alert("Kindly use Firefox or Chrome...Internet Explorer is not supported for now.");
					return;
				}


			// need to perform more check on the text box...only numerical data cannot be enetered

			// first check that bothe the fields are filled up
			if(window.document.myForm.txtSourceAddress.value=='')
			{
				alert("Please provide the source address");
				
			}
			else if(window.document.myForm.txtDestinationAddress.value=='')
			{
				alert("Please provide the destination address");
				
			}
			else if(window.document.myForm.txtSourceAddress.value==window.document.myForm.txtDestinationAddress.value)
			{
				alert("Please enter destination different from source");
			}
			else
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
				var sourceAddress = document.getElementById("txtSourceAddress").value;
				var destinationAddress=document.getElementById("txtDestinationAddress").value;

				// construct URL
				var url = "GetBusStopsV2.php?source=" +sourceAddress+"&destination="+destinationAddress;
				// show progress
				document.getElementById("searchStatus").innerHTML = "";
               document.getElementById("progress").style.display = "block";

				//alert(url);
				// get quote
				xhr.onreadystatechange =
				function()
				{
					// only handle loaded requests
					if (xhr.readyState == 4)
					{
						if (xhr.status == 200)
						{
							// insert quote into DOM
						   var div = document.createElement("div");
						   var text = document.createTextNode(xhr.responseText);
						   div.appendChild(text);
						   //document.getElementById("quotes").appendChild(div);
							// alert(xhr.responseText);
							var xml = xhr.responseXML;
							var errorStatuses=xml.getElementsByTagName("ErrorCode");
							//alert(errorStatuses);
							var infoMessages=xml.getElementsByTagName("Info");
							var internetAddressSource="NA";
							var internetAddressDestination="NA";
							if(errorStatuses[0].firstChild.textContent=="IA")
								internetAddressSource=xml.getElementsByTagName("Address")[0].childNodes.item(1);//xml.getElementsByTagName("InternetAddress");
							if(errorStatuses[1].firstChild.textContent=="IA")
								internetAddressDestination=xml.getElementsByTagName("Address")[1].childNodes.item(1);//xml.getElementsByTagName("InternetAddress");


							
							ClearDisplayArea();
							ClearMap();
							// This means that Address is not valid
							if(errorStatuses[0].firstChild.textContent=="AM" || errorStatuses[1].firstChild.textContent=="AM")
							{
								if(errorStatuses[0].firstChild.textContent=="AM")
								{
									document.getElementById("sourceStop").innerHTML = "<b>"+sourceAddress+": "+infoMessages[0].firstChild.textContent+"</b>";
								}
								if(errorStatuses[1].firstChild.textContent=="AM")
								{
									document.getElementById("destinationStop").innerHTML = "<b>"+destinationAddress+": "+infoMessages[1].firstChild.textContent+"</b>";
								}

									document.getElementById("progress").style.display = "none";

							}
							//More than one matching address found in road database. Kindly add some more specifics
							else if(errorStatuses[0].firstChild.textContent=="MA" || errorStatuses[1].firstChild.textContent=="MA")
							{
								if(errorStatuses[0].firstChild.textContent=="MA")
								{
									document.getElementById("sourceStop").innerHTML = "<b>"+sourceAddress+": "+infoMessages[0].firstChild.textContent+"</b>";
								}
								if(errorStatuses[1].firstChild.textContent=="MA")
								{
									document.getElementById("destinationStop").innerHTML = "<b>"+destinationAddress+": "+infoMessages[1].firstChild.textContent+"</b>";
								}

									document.getElementById("progress").style.display = "none";

							}
							//All stops are more then 1 km far
							else if(errorStatuses[0].firstChild.textContent=="KM" || errorStatuses[1].firstChild.textContent=="KM")
							{
								if(errorStatuses[0].firstChild.textContent=="KM")
								{
									document.getElementById("sourceStop").innerHTML = "<b>"+sourceAddress+": "+infoMessages[0].firstChild.textContent+"</b>";
								}
								if(errorStatuses[1].firstChild.textContent=="KM")
								{
									document.getElementById("destinationStop").innerHTML = "<b>"+destinationAddress+": "+infoMessages[1].firstChild.textContent+"</b>";
								}
								
								document.getElementById("progress").style.display = "none";

							}
							
							else
							{
								var busStops = xml.getElementsByTagName("Result");
								var sourceStopName = busStops[0].firstChild.textContent;
								var sourceStopLatitude=busStops[0].childNodes[1].textContent;
								var sourceStopLongitude=busStops[0].childNodes[2].textContent;
								var sourceStopOffset=busStops[0].childNodes[3].textContent;								
								//var sourceStopBuses=busStops[0].childNodes[4].textContent;
								if(sourceStopOffset==0)
									document.getElementById("sourceStop").innerHTML = "<b>Your Source Stop is: "+ sourceStopName+"</b>";
								else
								{
									gUserEnteredStartingAddress=sourceAddress+":"+busStops[0].childNodes[5].textContent+":"+busStops[0].childNodes[6].textContent;
									if(internetAddressSource!="NA")
										suffix="<font color='BF0000'>..We searched for.." +internetAddressSource.firstChild.textContent+"</font>";
									else
										suffix="";								

									document.getElementById("sourceStop").innerHTML ="<b>Your Source Stop is: "+sourceStopName+" (The stop is "+
										GetNormalizedDistance(sourceStopOffset)+"  from "+sourceAddress+ suffix+")</b>";
									
											

								}
								var destStopName = busStops[1].firstChild.textContent;
								var destStopLatitude=busStops[1].childNodes[1].textContent;
								var destStopLongitude=busStops[1].childNodes[2].textContent;
								var destStopOffset=busStops[1].childNodes[3].textContent;
								if(destStopOffset==0)
									document.getElementById("destinationStop").innerHTML = "<b>Your Destination Stop is: "+ destStopName+"</b><br/><hr/>";
								else
								{
									if(internetAddressDestination!="NA")
										suffix="<font color='BF0000'>...We searched for.." +internetAddressDestination.firstChild.textContent+"</font>";
									else
										suffix="";
									gUserEnteredDestinationAddress=destinationAddress+":"+busStops[1].childNodes[5].textContent+":"+busStops[1].childNodes[6].textContent;
									document.getElementById("destinationStop").innerHTML ="<b>Your Destination Stop is: "+
										destStopName+" (The stop is "+ GetNormalizedDistance(destStopOffset)+"  from "+destinationAddress+ suffix+")</b><br/><hr/>";
								}
								
								//alert(xhr.responseText);
								gXML=xml;
								GetRouteInformation(sourceStopName,destStopName,sourceStopOffset,destStopOffset,0);
								//alert(ss);
							}
							
						}
						else
							alert("Error with call!");
					}
				}
				xhr.open("GET", url, true);
				xhr.send(null);
				}
        }

		/**
		 * Function will take care of all the depot cases
		**/
		function DisplayDepotRoutes(data,routeDetails,index)
		{
                gXML=data;
				var busRouteArray = new Array();
				var routeInfo=routeDetails.childNodes[2].textContent;
				var errorCode=routeDetails.childNodes[1].textContent;
				str="<b>Found depot buses</b>"+"<br/>"+routeInfo+"<br/>";
				//document.getElementById("searchStatus").innerHTML = "<b>Found depot buses</b>"+"<br/>"+routeInfo+"<br/>";
				document.getElementById("searchStatus").innerHTML = displayResultString(str)
				busRouteArray=routeDetails.childNodes[3];

				if(errorCode==10||errorCode==9)//depot is for end point
				{
					var startStop=busRouteArray.childNodes[0].textContent;
					var endStop=busRouteArray.childNodes[1].textContent;
                    gStartStop=startStop;
                    gEndStop=endStop;
					var depotArray=busRouteArray.childNodes[2].textContent.split(":");
					var depotName=depotArray[0];
                    var depotLat=depotArray[1];
                    var depotLon=depotArray[2];
                    var busesBetweenDepotAndStop=getBusesForDisplayAfterTruncation(busRouteArray.childNodes[3].textContent);
					var distancebetweenDepotAndStop=busRouteArray.childNodes[4].textContent;

                    var indirectBusRouteArray=busRouteArray.childNodes[5];
                    var indirectRouteErrorCode=indirectBusRouteArray.childNodes[0].textContent;

					var firstJunctionArray=indirectBusRouteArray.childNodes[3].textContent.split(":");
					var firstJunction=firstJunctionArray[0];
					var firstJunctionLat=firstJunctionArray[1];
					var firstJunctionLon=firstJunctionArray[2];
					
					var distJunction=indirectBusRouteArray.childNodes[4].textContent;

					var secJunctionArray=indirectBusRouteArray.childNodes[5].textContent.split(":");
					var secJunction=secJunctionArray[0];
					var secJunctionLat=secJunctionArray[1];
					var secJunctionLon=secJunctionArray[2];
					//s->J->D-E for 10
                    //s->D->J->E for 9
					var busesBetweenStopAndJunction=getBusesForDisplayAfterTruncation(indirectBusRouteArray.childNodes[2].textContent);
					var busesBetweenJunctionAndDepot=getBusesForDisplayAfterTruncation(indirectBusRouteArray.childNodes[6].textContent);

					var totalIndirectDistance=indirectBusRouteArray.childNodes[8].textContent;
					var totalRouteDistance=busRouteArray.childNodes[6].textContent;
                    if(errorCode==10)
					     referenceImage="images/error10.png";
                    else
                        referenceImage="images/error9.png";

                    junctionStringForPlotting= depotName+"^"+depotLat+"^"+depotLon+"^"+firstJunction+"^"+firstJunctionLat+"^"+firstJunctionLon+"^"+
                        secJunction+"^"+secJunctionLat+"^"+secJunctionLon;

                    gDepotJunctionDetailsArray.push(junctionStringForPlotting);
                    busBetweenDepotAndStopForRoutePlotting=busesBetweenDepotAndStop.split(",");
                    busBetweenStopAndJunctionForRoutePlotting=busesBetweenStopAndJunction.split(",");
                    busBetweenJunctionAndDepotForRoutePlotting=busesBetweenJunctionAndDepot.split(",");

                    if(errorCode==9)
                    {
                        displayBusesAtDepot=formatBusFrequency(busesBetweenDepotAndStop)+"^"+formatBusFrequency(busesBetweenJunctionAndDepot);
                        displayBusesAtJunction=formatBusFrequency(busesBetweenJunctionAndDepot)+"^"+formatBusFrequency(busesBetweenStopAndJunction);

                    }
                    else
                    {
                        displayBusesAtJunction=formatBusFrequency(busesBetweenStopAndJunction)+"^"+formatBusFrequency(busesBetweenJunctionAndDepot);
                        displayBusesAtDepot=formatBusFrequency(busesBetweenJunctionAndDepot)+"^"+formatBusFrequency(busesBetweenDepotAndStop);
                    }

                    gDisplayBusesForMarkers.push(displayBusesAtDepot+"^"+displayBusesAtJunction);

                    junctionStringForBusRouting=busBetweenDepotAndStopForRoutePlotting[0]+"^"+busBetweenStopAndJunctionForRoutePlotting[0]+
                        "^"+busBetweenJunctionAndDepotForRoutePlotting[0];

                    gDepotJunctionRouteDetailsArray.push(junctionStringForBusRouting);


                    if(index==0)
                    {
					    plotIndirectDepotMap(gXML,startStop,endStop,gDepotJunctionDetailsArray[0],
                            gDepotJunctionRouteDetailsArray[0],gDisplayBusesForMarkers[0],gUserEnteredStartingAddress,gUserEnteredDestinationAddress,
                            errorCode);
                    }
				}
				else if(errorCode==7 || errorCode==8)
				{
					var startStop=busRouteArray.childNodes[0].textContent;
					var endStop=busRouteArray.childNodes[1].textContent;
					var depotArray=busRouteArray.childNodes[2].textContent.split(":");
					var depotName=depotArray[0];
				    var depotBuses=busRouteArray.childNodes[3].textContent;
					var busesBetweenStartStopAndDepot=getBusesForDisplayAfterTruncation(busRouteArray.childNodes[4].textContent);
					var busesBetweenEndStopAndDepot=getBusesForDisplayAfterTruncation(busRouteArray.childNodes[5].textContent);
					
					var distanceBetweenDepotAndStartStop=busRouteArray.childNodes[6].textContent;
					var distanceBetweenDepotAndEndStop=busRouteArray.childNodes[7].textContent;
					var totalRouteDistance=busRouteArray.childNodes[8].textContent;
					var referenceImage="images/error7.png";
					if(errorCode==7)
						plotDepotMap(gXML,startStop,endStop,depotArray,null,depotBuses,busesBetweenStartStopAndDepot,
						busesBetweenEndStopAndDepot,errorCode);
					else
						plotDepotMap(gXML,startStop,endStop,null,depotArray,depotBuses,busesBetweenStartStopAndDepot,
						busesBetweenEndStopAndDepot,errorCode);
			
				}

				var res='';
				var firstLine='';
				var secondLine='';
				var thirdLine='';
				var fourthLine='';
				var distanceLine='';
				var optionName='';
				var optionNumber=index+1;
				gTotalOptions=gTotalOptions+1;
			//	optionName="<span class='options'><b>Option#"+optionNumber+"<a href=\'javascript:void(0)\' onClick=\'changeOption("+optionNumber+");return false;\'>Option#"+optionNumber+"</a>"+"</span></b><br/>";
				var divId="optionDetails"+optionNumber.toString();
				var initClass;
                var spanShowImages;
				if(optionNumber==1)
                {
					initClass="selectedBackground";
                    spanShowImages="showImages";
                }
				else
                {
					initClass="noSelectedBackground";
                    spanShowImages="hideImages";
                }

                //check for the junction icons
                junctionListIcons=getJunctionIconsForList(firstJunction,secJunction).split(":");

				optionName="<div class='"+initClass+"' id='"+divId+"'><span class='options'><b>"+
                    "<a href=\'javascript:void(0)\' onClick=\'changeOption("+optionNumber+","+errorCode+");return false;\'>Option#"+optionNumber+"</a>"+"</span></b><br/>";
				//create teh first line
				if(errorCode==9)
				{
					firstLine="<li>From <b>"+startStop+"</b> (Starting Point)"+
                        "<img class="+spanShowImages +" src='images/ChartApi/As.png' />"+ "take one of the <b>"+
                        formatBusFrequency(renameBIASBusesToKIAS(busesBetweenDepotAndStop))+"</b> and go to <b>"+depotName+"("+
                        GetNormalizedDistance(distancebetweenDepotAndStop)+")</b></b>" +
                        "<img class="+spanShowImages +" src='images/ChartApi/Ds.png' /></li>" ;

                    secondLine="<li>Now from <b>"+depotName+"</b><img class="+spanShowImages +" src='images/ChartApi/Ds.png' /> take one of the <b>"+
                        formatBusFrequency(renameBIASBusesToKIAS(busesBetweenJunctionAndDepot))+"</b> buses to go to <b>"+firstJunction+"</b>. " +
                        "<img class="+spanShowImages +" src='"+junctionListIcons[0]+"' /></li>";

                    if(indirectRouteErrorCode==3)
                        thirdLine="<li>You need to walk to  <b>"+secJunction+"</b><img class="+spanShowImages +" src='"+junctionListIcons[1]+"' /> which is "+ GetNormalizedDistance(distJunction)+ " from "+
                            "<b>"+firstJunction+"</b><img class="+spanShowImages +" src='"+junctionListIcons[0]+"' /></li>";
                    else
                        thirdLine='';

                    fourthLine="<li>Now from "+"<b>"+secJunction+"</b> <img class="+spanShowImages +" src='"+junctionListIcons[1]+"' /> take one of the <b>"+
                        formatBusFrequency(renameBIASBusesToKIAS(busesBetweenStopAndJunction))+"</b> to reach the destination stop <b>"+endStop+
                        "</b> <img class="+spanShowImages +" src='images/ChartApi/Bs.png' />. ("+GetNormalizedDistance(totalIndirectDistance)+")</li></ul>";


                }
               else if(errorCode==10)
                {
                    firstLine="<li>From <b>"+startStop+"</b> (Starting Point)" +
                        "<img class="+spanShowImages +" src='images/ChartApi/As.png' />"+" take one of the <b>"+
                        formatBusFrequency(renameBIASBusesToKIAS(busesBetweenStopAndJunction))+"</b> and go to <b>"+firstJunction+
                        "</b><img class="+spanShowImages +" src='"+junctionListIcons[0]+"' /></li>" ;
                    if(indirectRouteErrorCode==3)
                        secondLine="<li>You need to walk to  <b>"+secJunction+"</b> <img class="+spanShowImages +" src='"+junctionListIcons[1]+"' />which is "+ GetNormalizedDistance(distJunction)+ " from "+ "<b>"+firstJunction+"</b><img class="+spanShowImages +" src='"+junctionListIcons[0]+"' /></li>";
                    else
                        secondLine='';
                    thirdLine="<li>Now from "+"<b>"+secJunction+"</b><img class="+spanShowImages +" src='"+junctionListIcons[1]+"' /> take one of the <b>"+
                        formatBusFrequency(renameBIASBusesToKIAS(busesBetweenJunctionAndDepot))+"</b> buses to go to <b>"+depotName+"</b>." +
                        "<img class="+spanShowImages +" src='images/ChartApi/Ds.png' /> </li>";

                    fourthLine="<li>Now from "+"<b>"+depotName+"</b><img class="+spanShowImages +" src='images/ChartApi/Ds.png' />take one of the <b>"+
                        formatBusFrequency(renameBIASBusesToKIAS(busesBetweenDepotAndStop))+"</b> to reach the destination stop <b>"+endStop+
                        "</b> <img class="+spanShowImages +" src='images/ChartApi/Bs.png' />. ("+GetNormalizedDistance(distancebetweenDepotAndStop)+")</li></ul>";


                }

				else if (errorCode==8 || errorCode==7)
				{
					firstLine="<li>From <b>"+startStop+"</b> (Starting Point)" +
                        "<img class="+spanShowImages +" src='images/ChartApi/As.png' /> take one of the <b>"+formatBusFrequency(busesBetweenStartStopAndDepot)+
                        "</b> and go to <b>"+depotName+"</b><img class="+spanShowImages +" src='images/ChartApi/Ds.png' />("+GetNormalizedDistance(distanceBetweenDepotAndStartStop)+").</li>" ;
					secondLine='';
                    thirdLine="";

					fourthLine="<li>Now from "+"<b>"+depotName+"</b> " +
                        "<img class="+spanShowImages +" src='images/ChartApi/Ds.png' />take one of the <b>"+formatBusFrequency(busesBetweenEndStopAndDepot)+"</b> to reach the destination stop <b>"+endStop+"</b> <img class="+spanShowImages +" src='images/ChartApi/Bs.png' />. ("+GetNormalizedDistance(distanceBetweenDepotAndEndStop)+")</li></ul>"
					
				}
				

				distanceLine="The approximate route distance="+"<b>"+ GetNormalizedDistance(totalRouteDistance)+"</b><br/></div>";
				//imageLine="<img src="+referenceImage+"></img>";
				res=res+optionName+firstLine+secondLine+thirdLine+fourthLine+distanceLine+"<hr/>";
				return res;
				
		}


		
		/// function to get the route information
		// this will also have an information for the depot.
		// we can also have just the gif telling this
		// this function needs to be changed a bit to accomodate for the depots
		function GetRouteInformation(sourceStopName,destStopName,sourceStopOffset,destStopOffset,onlyIndirectRoutes)
		{
			
			if(onlyIndirectRoutes==1)
			{
				document.getElementById("searchStatus").innerHTML = "<b>Searching for Indirect Routes</b>";
				document.getElementById("routeDetails").innerHTML = "";
				document.getElementById("progress").style.display = "block";
				ClearMap();
			}
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
           

            // construct URL
			
            var url = "GetBusRouteDetailsV2.php?sourceStopName=" + sourceStopName+"&destStopName="+destStopName+"&sourceOffset="+sourceStopOffset+"&destOffset="+destStopOffset+"&onlyIndirectRoutes="+onlyIndirectRoutes;
			//alert(url);
            // get quote
            xhr.onreadystatechange =
            function()
            {
                // only handle loaded requests
                if (xhr.readyState == 4)
                {
                    if (xhr.status == 200)
                    {
						//return xhr.responseText;
						//alert(xhr.responseText);
						var div = document.createElement("div");
                        var text = document.createTextNode(xhr.responseText);
                        div.appendChild(text);
                        //document.getElementById("routeDetails").appendChild(div);
						 // hide progress
						document.getElementById("progress").style.display = "none";

						var routeXml = xhr.responseXML;
						var routeDetails = routeXml.getElementsByTagName("Route");
						//alert(routeDetails.length);
						var i=0;
						var res='';
						var flag=0;
						var junctionDetails;
						var directBusToPlot='';
						// first check for the walking thing
						
						var junctionDetailsArray = new Array();
						var junctionRouteDetailsArray = new Array();
						var directBusRouteToPlot;
						gTotalOptions=0;

                        // this is the indirect route
                        gDepotJunctionDetailsArray=new Array();
                        gDepotJunctionRouteDetailsArray=new Array();
                        gDisplayBusesForMarkers=new Array();
                        gEndStop=null;
                        gStartStop=null;

                        gBusesToDisplayInToolTipArray=new Array();
						for(i=0;i<routeDetails.length;i++)
						{	
							if(routeDetails[i].firstChild.textContent=="Y")
							{
								// direct route found
								var busRoute=routeDetails[i].childNodes[2].textContent;
								var busFrequency=routeDetails[i].childNodes[3].textContent;
								var directDistance=routeDetails[i].childNodes[4].textContent;
								var routeNumber=i+1;
								if(i==0)
									directBusRouteToPlot=busRoute;
								if(i==0)
									res=res+"<b>Total Distance  "+GetNormalizedDistance(directDistance)+"</b><br/>";
								res=res+ "<b>Direct Bus #"+routeNumber+": "+getFormattedFrequecyForDirectBus(busRoute,busFrequency)+"</b><br/>";
								
								document.getElementById("searchStatus").innerHTML =displayResultString("<b>Direct Buses Found</b>");
								

								flag=1;
							}
							else
							{
								flag=0;
								// get the error code
								var errorCode=routeDetails[i].childNodes[1].textContent;
								if(errorCode==409||errorCode==410||errorCode==411||errorCode==412)
								{
								// this will take care of the condition id even indirect route is not found
									document.getElementById("searchStatus").innerHTML="<b>Sorry!! we couldnot find any direct or indirect route for you...</b>";
									return;
								}
                                if(errorCode==413)
                                {
                                    // this will take care of the condition id even indirect route is not found
                                    document.getElementById("searchStatus").innerHTML="<b>Sorry!! we couldnot find any indirect route for you...</b>";
                                    return;
                                }
								// ignore this particular entry
//								if(errorCode==6)
//								{
//									continue;
//								}
								if(errorCode==5)
								{
								// this is for the walking distance
									document.getElementById("searchStatus").innerHTML="<b>The Distance is Walkable</b>";
									var walkableArray=routeDetails[i].childNodes[2].textContent.split(":");
									var walkSourceName=walkableArray[0];
									var walkSourceLat=walkableArray[1];
									var walkSourceLon=walkableArray[2];
									var walkDestName=walkableArray[3];
									var walkDestLat=walkableArray[4];
									var walkDestLon=walkableArray[5];
									var walkDistance=walkableArray[6];								

									document.getElementById("searchStatus").innerHTML="<b>The Distance is Walkable ("+GetNormalizedDistance(walkableArray[6])+")</b>";

									PlotWalkableMap(walkSourceName,walkSourceLat,walkSourceLon,walkDestName,walkDestLat,walkDestLon,walkDistance);
									return;
								}
								if(errorCode==1 || errorCode==2|| errorCode==3 ||errorCode==4)
								{
									// this is the indirect route

									var busRouteArray = new Array();
								   busRouteArray=routeDetails[i].childNodes[2];

                                    var startStop=busRouteArray.childNodes[0].textContent;
                                    var startBuses=busRouteArray.childNodes[1].textContent;
                                    var displayStartBuses=getBusesForDisplayAfterTruncation(startBuses);
                                    /*var tempStartBusesArray=startBuses.split(",");
                                    var displayStartBuses='';
                                    if(tempStartBusesArray.length>5)
                                    {
                                        displayStartBuses=tempStartBusesArray[0]+","+tempStartBusesArray[1]+","+tempStartBusesArray[2]+","+tempStartBusesArray[3]+","+tempStartBusesArray[4];
                                    }
                                    else
                                        displayStartBuses=startBuses;
*/
                                    var firstJunctionArray=busRouteArray.childNodes[2].textContent.split(":");
                                    var firstJunction=firstJunctionArray[0];
                                    var firstJunctionLat=firstJunctionArray[1];
                                    var firstJunctionLon=firstJunctionArray[2];
                                    // the distance between the two junctions
                                    var distJunction=busRouteArray.childNodes[3].textContent;
                                    var secJunctionArray=busRouteArray.childNodes[4].textContent.split(":");
                                    var secJunction=secJunctionArray[0];
                                    var secJunctionLat=secJunctionArray[1];
                                    var secJunctionLon=secJunctionArray[2];
                                    var destBuses=busRouteArray.childNodes[5].textContent;
                                    var displayDestBuses=getBusesForDisplayAfterTruncation(destBuses);
                                    /*var tempDestBusesArray=destBuses.split(",");
                                    var displayDestBuses='';
                                    if(tempDestBusesArray.length>5)
                                    {
                                        displayDestBuses=tempDestBusesArray[0]+","+tempDestBusesArray[1]+","+tempDestBusesArray[2]+","+tempDestBusesArray[3]+","+tempDestBusesArray[4];
                                    }
                                    else
                                        displayDestBuses=destBuses;

                                    */
                                    var endStop=busRouteArray.childNodes[6].textContent;
                                    //the total distance.
                                    var distance=busRouteArray.childNodes[7].textContent;


                                //if(i==0)
                                //{
                                    junctionDetailsArray[i]=firstJunction+"^"+firstJunctionLat+"^"+firstJunctionLon+"^"+secJunction+"^"+secJunctionLat+"^"+secJunctionLon;
                                    startBusForRoute=startBuses.split(",");
                                    destBusForRoute=destBuses.split(",");
                                    if(errorCode==2)
                                    {
                                        // this is the case when you have the direct bus but it doesnot reach to the destination
                                        junctionRouteDetailsArray[i]=startBusForRoute[0]+"^"+destBusForRoute[0]+"^0";
                                    }
                                    else
                                    {
                                        junctionRouteDetailsArray[i]=startBusForRoute[0]+"^"+destBusForRoute[0]+"^1";
                                    }

                                //}

									//res=res+ startStop+":"+startBuses+":"+firstJunction+":"+distJunction+":"+secJunction+":"+destBuses+":"+endStop+":"+distance+"<br/>";
									var firstLine='';
									var secondLine='';
									var thirdLine='';
									var distanceLine='';
									var optionName='';
									var optionNumber=i+1;
									gTotalOptions=gTotalOptions+1;
								//	optionName="<span class='options'><b>Option#"+optionNumber+"<a href=\'javascript:void(0)\' onClick=\'changeOption("+optionNumber+");return false;\'>Option#"+optionNumber+"</a>"+"</span></b><br/>";
									var divId="optionDetails"+optionNumber.toString();
									var initClass;
                                    var spanShowImages;
                                    if(optionNumber==1)
                                    {
                                        initClass="selectedBackground";
                                        spanShowImages="showImages";
                                    }
                                    else
                                    {
                                        initClass="noSelectedBackground";
                                        spanShowImages="hideImages";
                                    }

                                    junctionListIcons=getJunctionIconsForList(firstJunction,secJunction).split(":");
									optionName="<div class='"+initClass+"' id='"+divId+"'><span class='options'><b>"+
                                        "<a href=\'javascript:void(0)\' onClick=\'changeOption("+optionNumber+","+errorCode+");return false;\'>Option#"+optionNumber+"</a>"+"</span></b><br/>";
									if(errorCode==1 || errorCode==3)
                                    {
									    firstLine="<ul><li>From <b>"+startStop+"</b> (Starting Point)<img class="+spanShowImages +" src='images/ChartApi/As.png' /> " +
                                        "take one of the <b>"+formatBusFrequency(renameBIASBusesToKIAS(displayStartBuses))+"</b> and go to <b>"+firstJunction+"</b>" +
                                        "<img class="+spanShowImages +" src='"+junctionListIcons[0]+"' /></li>" ;
                                    }
									//if(distJunction==0)
									if(errorCode==1)
									{
										secondLine="<li>Now from "+"<b>"+firstJunction+"</b> <img class="+spanShowImages +" src='"+junctionListIcons[0]+"' /> take one of the <b>"+formatBusFrequency(renameBIASBusesToKIAS(displayDestBuses))+"" +
                                            "</b> buses to go to <b>"+endStop+"</b> (Your Destination)" +
                                            "<img class="+spanShowImages +" src='images/ChartApi/Bs.png' /></li></ul>";
										
									}
                                    else if(errorCode==4)
                                    {

                                        firstLine="<li>You can walk "+GetNormalizedDistance(distJunction)+" from "+"<b>"+startStop+"</b> (Starting Point) <img class="+spanShowImages +" src='images/ChartApi/As.png' />" +
                                            " to reach <b>"+secJunction +"</b>"+"<img class="+spanShowImages +" src='images/ChartApi/Js.png' /></li>";
                                        secondLine="<li>Now from "+"<b>"+secJunction+"</b> <img class="+spanShowImages +" src='images/ChartApi/Js.png' /> take one of the <b>"+formatBusFrequency(renameBIASBusesToKIAS(displayDestBuses))+"" +
                                            "</b> buses to go to <b>"+endStop+"</b> (Your Destination)" +
                                            "<img class="+spanShowImages +" src='images/ChartApi/Bs.png' /></li></ul>";
                                    }
									else if(errorCode==2)
									{
                                        firstLine="<ul><li>From <b>"+startStop+"</b> (Starting Point)<img class="+spanShowImages +" src='images/ChartApi/As.png' /> " +
                                            "take one of the <b>"+formatBusFrequency(renameBIASBusesToKIAS(displayStartBuses))+"</b> and go to <b>"+firstJunction+"</b>" +
                                            "<img class="+spanShowImages +" src='images/ChartApi/Js.png' /></li>" ;
										secondLine="<li>You can walk "+GetNormalizedDistance(distJunction)+" from "+"<b>"+firstJunction+"</b>" +
                                            " to reach <b>"+endStop +"</b>(Your Destination)"+"<img class="+spanShowImages +" src='images/ChartApi/Bs.png' /></li></ul>";
									}
									else
									{
										secondLine="<li>You need to walk to  <b>"+secJunction+"</b><img class="+spanShowImages +" src='"+junctionListIcons[1]+"' /> which is "+ GetNormalizedDistance(distJunction)+ "" +
                                            " from "+ "<b>"+firstJunction+"</b><img class="+spanShowImages +" src='"+junctionListIcons[0]+"' /></li>";
										thirdLine= "<li>Now from <b>"+secJunction+"</b><img class="+spanShowImages +" src='"+junctionListIcons[1]+"' /> take one of the <b>"+formatBusFrequency(renameBIASBusesToKIAS(displayDestBuses))+"</b>" +
                                            " buses to go to <b>"+endStop+ "</b>(Your Destination)<img class="+spanShowImages +" src='images/ChartApi/Bs.png' /</li></ul>";
									}
									distanceLine="The approximate route distance="+"<b>"+ GetNormalizedDistance(distance)+"</b><br/></div>";
									res=res+optionName+firstLine+secondLine+thirdLine+distanceLine+"<hr/>";
									if(onlyIndirectRoutes==0)
									{
										document.getElementById("searchStatus").innerHTML = displayResultString("<b>No Direct Buses Found. Here are few Indirect routes for you:</b>");
									}
									else
									{
										document.getElementById("searchStatus").innerHTML = displayResultString("<b>Found few indirect routes for you:</b>");
									}

                                    if(errorCode==1||errorCode==2)
                                    {
                                        tempFirstJunctionBuses=formatBusFrequency(renameBIASBusesToKIAS(displayStartBuses))+formatBusFrequency(renameBIASBusesToKIAS(displayDestBuses));
                                        tempSecondJunctionBuses='NA';
                                        tempJunctionBuses=tempFirstJunctionBuses+"^"+tempSecondJunctionBuses;
                                        gBusesToDisplayInToolTipArray.push(tempJunctionBuses);

                                    }

                                    else  if(errorCode==3||errorCode==4)
                                    {
                                        tempFirstJunctionBuses=formatBusFrequency(renameBIASBusesToKIAS(displayStartBuses));
                                        tempSecondJunctionBuses=formatBusFrequency(renameBIASBusesToKIAS(displayDestBuses));
                                        tempJunctionBuses=tempFirstJunctionBuses+"^"+tempSecondJunctionBuses;
                                        gBusesToDisplayInToolTipArray.push(tempJunctionBuses);
                                    }

								}
								//depot cases..avoid the 411, 409..etc error code
								if(errorCode > 6 && errorCode <400)
								{
									document.getElementById("searchStatus").innerHTML = "<b>Found depot buses</b>";
									flag=2;
									res=res+DisplayDepotRoutes(gXML,routeDetails[i],i);

								}
							}
						}


						// need to pass the route xml also so that the junctions can be plotted
							if(flag==1)
							{
								//alert(directBusToPlot);
								// we can also show the link here in case number of direct buses returned was less
								//direct buses;
                                // Added on 29/jan/2014..show only if the distance is >1 KM
								if(directDistance>1 && routeDetails.length<3)
								{
									//alert("original"+sourceStopName);
									var mess="<br/><span class='extraInformation'><b>It seems we could find only few direct routes. Would you like us to check for indirect routes too?</b></span><br>";   	
									res=res+ mess+ "<span class='options'><b><a href=\"javascript:void(0)\" onclick=\"GetRouteInformation('"+sourceStopName+"','"+destStopName+"','"+sourceStopOffset+"','"+destStopOffset+"',1);\">"+
										" OK "+"</a></span></b>"+"<br/>";
									
								}

								PlotMap(gXML,0,directBusRouteToPlot,0,gUserEnteredStartingAddress,gUserEnteredDestinationAddress);
							}
							else if(flag==0)
							{
								//indirect bus
								//gXML=xml;
								gJunctionDetailsArray=junctionDetailsArray;
								gJunctionRouteDetailsArray=junctionRouteDetailsArray;


								PlotMap(gXML,junctionDetailsArray[0],junctionRouteDetailsArray[0],gBusesToDisplayInToolTipArray[0],gUserEnteredStartingAddress,gUserEnteredDestinationAddress);
							}

							else if(flag==2)
							{
								//alert(gXML);
								//do nothing

							}

							document.getElementById("routeDetails").innerHTML=res;
                    }
                    else
                        alert("Error with call!");
                }
            }
            xhr.open("GET", url, true);
            xhr.send(null);
		}



