<?php
$fh=fopen("ErrorCode10.txt",'r');
while(($str = fgets($fh))!=null)
{

	echo $str."<br/>";
}
?>