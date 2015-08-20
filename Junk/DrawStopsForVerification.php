<html>
  <head>
    <meta charset="utf-8" />
    <title>Google Maps JavaScript API Example: 	Reverse Geocoder</title>
   <script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=ABQIAAAA1c1sWAqiVfYVo2H2uZO3DRSdbZxIVjTSMKDiD-iCCeLYxJJn_BTfNn4DtNyckPujCTOcXysH3Glq9g"
        type="text/javascript"></script>
   <script type="text/javascript" src="Scripts/drawStopsForVerification.js"></script>

  </head>
  <div>
	<form>
		StartIndex<input type="text" id="startIndex" name="startIndex" />
		End Index<input type="text" id="endIndex" name="endIndex"/>
		<input type="submit" id="submit" onClick="getStopPointsToDraw();return false;"/>
	</form>

  </div>

  <body>
    <div id="map" style="width: 1200px; height: 700px"></div>
  </body>
</html>
