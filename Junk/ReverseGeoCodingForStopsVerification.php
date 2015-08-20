<?php
header("Content-type: text/xml");

require_once 'Excel/reader.php';

//echo $numRows."<br/>";
//echo $numCols;

if(isset($_GET))
{
	$startIndex=$_GET["startIndex"];
	$endIndex=$_GET["endIndex"];
	updateDB($startIndex,$endIndex);
}
//update the products based on the Excel	
function updateDB($startIndex,$endIndex)
{
	//need to copy the code that does the excel reading
	$analysisData = new Spreadsheet_Excel_Reader();
	// Set output Encoding.
	$analysisData->setOutputEncoding('CP1251');
	$inputFileName = 'ReverseGeoCodingForStopsVerification.xls';
	$analysisData->read($inputFileName);
	error_reporting(E_ALL ^ E_NOTICE);
	$numRows=$analysisData->sheets[0]['numRows'];
	$numCols=$analysisData->sheets[0]['numCols'];
	//echo $numRows.",".$numCols;
	$strRoute='<Routes>';
	for ($i=$startIndex;$i<=$endIndex;$i++) 
	{
		$stopId=$analysisData->sheets[0]['cells'][$i][1];
		$StopName=$analysisData->sheets[0]['cells'][$i][2];
		$Buses=$analysisData->sheets[0]['cells'][$i][3];
		$latitude=$analysisData->sheets[0]['cells'][$i][4];
		$longitude=$analysisData->sheets[0]['cells'][$i][5];
		$strRoute=$strRoute.'<Route>';
		$routeDetails=htmlentities(trim($StopName)).":".$latitude.":".$longitude;
		//echo $routeDetails."<br/>";
		$strRoute=$strRoute.'<RouteDetails>'.$routeDetails.'</RouteDetails>';
		$strRoute=$strRoute.'</Route>';		
	}
	$strRoute=$strRoute.'</Routes>';
	echo $strRoute;
}


