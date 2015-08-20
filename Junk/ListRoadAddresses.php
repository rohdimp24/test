<?php

require_once 'loginMySQL.php';
// The script to add the new languages to the database

// connect to the database
$db_server = mysql_connect($db_hostname, $db_username, $db_password);
if (!$db_server) die("Unable to connect to MYSQL: " . mysql_error());

mysql_select_db($db_database)
or die("Unable to select database: " . mysql_error());


/* get querystring parameter */

/*protect from sql injections */

/* query the database */
$query = "SELECT address FROM RoadAddresses Union SELECT StopName FROM Stops order by address";
$result = mysql_query($query);
$num_rows = mysql_num_rows($result);

// show maximum of 10 suggestions
/* loop through and return matching entries */
for ($x = 0; $x <$num_rows; $x++) {

//if($x==10)
//	break;
$row = mysql_fetch_row($result);
$output = "\"".$row[0]."\",";
echo $output;
}


//



?>