<?
$addr="jfwtc";
$addressString="http://maps.google.com/maps/api/geocode/json?address=".urlencode($addr).",+Bangalore,+Karnataka,+India&sensor=false";
echo $addressString;
$geocode=file_get_contents($addressString);
echo $geocode;

?>