<?php

$str="John, F Welch Technology center, Bangalore, Karnataka, India";
$new_str = preg_replace('/Bangalore, Karnataka, India$/', '', $str);
echo $str;
echo "<br/>";
//echo preg_replace('/,$/','',$new_str.trim());
$ss=rtrim(trim($new_str), ",");
echo $ss;



?>