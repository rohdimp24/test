<html>
  <head>
    <meta charset="utf-8" />
    <title>Google Maps JavaScript API Example: 	Reverse Geocoder</title>
  <!-- <script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=ABQIAAAA1c1sWAqiVfYVo2H2uZO3DRSdbZxIVjTSMKDiD-iCCeLYxJJn_BTfNn4DtNyckPujCTOcXysH3Glq9g"
        type="text/javascript"></script>-->
		<script src="http:/maps.google.com/maps?file=api&v=2&key=AIzaSyA8cO41gmy6lhwSOR-4XASAils_eAaug6k" type="text/javascript"></script>
		
		<script src="http://maps.googleapis.com/maps/api/js?sensor=false&libraries=geometry"></script>
		  <script type="text/javascript" src="Scripts/json2.js"></script>
   <script type="text/javascript" src="Scripts/drawbusRoute2.js"></script>

  </head>
  <div>
	<form>
		<input type="text" id="busNumber" />
		<input type="submit" id="submit" onClick="getBusPointsToDraw();return false;"/>
		<input type="button" value="Save Waypoints" onClick="saveWayPoints()">
		<input type="text" id="waypoints" size='200' />
		

	</form>

  </div>

  <body>
    <div id="map" style="width: 1200px; height: 760px"></div>

  </body>
</html>
