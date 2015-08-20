<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>BTMC bus route search � bmtcroutes.in</title>
<meta name="description" content="Bangalore Bus Route Search, BMTC Bus Route Search, BMTC Bus Route Search, BMTC, Bangalore Metropolitan Transport Corporation, Bangalore Routes, Bangalore Bus Routes, BMTC Bus Routes, BMTC Routes, Bus Route Information, Bus Route Search, Bangalore Bus Service, Bus Route Planner, Bus Route Numbers, Bangalore Transport,Bangalore public transport, BMTC Volvo, BMTC Routes, Bus Route Bangalore, Bus Planner Bangalore, Direct Bus, Indirect Bus" />
<meta name="keywords" content="Bangalore Bus Route Search, BMTC Bus Route Search, BMTC Bus Route Search, BMTC, Bangalore Metropolitan Transport Corporation, Bangalore Routes, Bangalore Bus Routes, BMTC Bus Routes, BMTC Routes, Bus Route Information, Bus Route Search, Bangalore Bus Service, Bus Route Planner, Bus Route Numbers, Bangalore Transport,Bangalore public transport, BMTC Volvo, BMTC Routes, Bus Route Bangalore, Bus Planner Bangalore, Direct Bus, Indirect Bus" />

<style type="text/css">

</style>

<link rel="stylesheet" type="text/css" href="Scripts/fonts-min.css" />
<link rel="stylesheet" type="text/css" href="Scripts/autocomplete/assets/skins/sam/autocomplete.css" />
<link rel="stylesheet" type="text/css" href="Scripts/bmtc.css" />
<script type="text/javascript" src="Scripts/browserDetect.js"></script>
<script type="text/javascript" src="Scripts/mapIconMaker.js"></script>

<script type="text/javascript" src="Scripts/jquery.js"></script>

<script>
var browserName=BrowserDetect.browser;
if(browserName=="Explorer")
{
	alert("Kindly use Firefox or Chrome...Internet Explorer is not supported for now.");

}
</script>
  <script  src="http://maps.googleapis.com/maps/api/js?sensor=false&libraries=geometry"></script>
  <script type="text/javascript" src="Scripts/reverseGeocoding.js"> </script>



</head>
<body>

<div id="maincontainer">
<br/>
<iframe src="//www.facebook.com/plugins/like.php?href=https%3A%2F%2Fwww.facebook.com%2Fpages%2Fbmtcroutesin%2F170127893076029&amp;send=false&amp;layout=standard&amp;width=450&amp;show_faces=false&amp;action=like&amp;colorscheme=light&amp;font&amp;height=80&amp;appId=242491975776903" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:800px; height:30px;padding-bottom:-10px;" allowTransparency="true"></iframe>

<div id="topsection-reverse" class="yui-skin-sam"><div class="innertube">
<H1> Do you want to know which is your nearest bus stop?</H1><br/>

<span class="info"><p>Just share your location when asked. You can also click on the map to register your location.</p></span>

</div></div>

<div id="contentwrapper">
<div id="contentcolumn-rev">
<div class="innertube">
<div id="map_canvas" style="width: 714px; height: 600px"></div>

	
</div>
</div>
</div>

<div id="leftcolumn-rev">
	<div class="innertube">
		<span id="location"></span>
		 <br />
	
		
		<span id="reverseSearchStatus">
		
		</span>
		
		 <div id="progress" style="display:none;">
			<img alt="Please Wait..." src="images/progress.gif" />
      
		</div>
		<div id="routeDetails"></div>


	</div>
</div>

<?php require_once "Footer.php"; ?>
</body>
</html>
