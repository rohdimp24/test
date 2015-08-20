<?
require_once 'loginMySQL.php';
// The script to add the new languages to the database

// connect to the database
$db_server = mysql_connect($db_hostname, $db_username, $db_password);
if (!$db_server) die("Unable to connect to MYSQL: " . mysql_error());

mysql_select_db($db_database)
or die("Unable to select database: " . mysql_error());

// for each stop found also get the bus number .. also I want BIAS and G series to be considered last so order by
	$busQuery="Select * from BusDetails where BusNumber='V500K'"; 
	echo $busQuery;
	$busResult= mysql_query($busQuery);
	$busRowsnum = mysql_num_rows($busResult);
	$buses="";
	
	for($j=0;$j<$busRowsnum;$j++)
	{
		
		$busRow=mysql_fetch_row($busResult);
		$buses = $buses.$busRow[0].",";
	}
	echo $buses;

	?>