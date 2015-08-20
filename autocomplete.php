<?php

require_once 'loginDetailsV2.php';
// The script to add the new languages to the database

// connect to the database
$db_server = mysql_connect($db_hostname, $db_username, $db_password);
if (!$db_server) die("Unable to connect to MYSQL: " . mysql_error());

mysql_select_db($db_database)
or die("Unable to select database: " . mysql_error());

/* get querystring parameter */
$param = trim($_GET['query']);
if(strlen($param)>0)
{

	/*protect from sql injections */

	/* query the database */
	$query = "SELECT address FROM newRoadAddresses WHERE Address LIKE '%".$param."%' Union SELECT StopName FROM Stops WHERE StopName LIKE '%".$param."%'";
	$result = mysql_query($query);
	$num_rows = mysql_num_rows($result);

	$output='';
	// show maximum of 10 suggestions
	/* loop through and return matching entries */
	if($num_rows>10)
		$num_rows=10;
	for ($x = 0; $x <$num_rows; $x++) {

	$row = mysql_fetch_row($result);
	if($x==($num_rows-1))
		$output .= $row[0];
	else
		$output .= $row[0].";";

	}
	//$tagArrayString=implode(";",$tagArray);
	echo $output;
}
?>
