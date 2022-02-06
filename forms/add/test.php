<?php
$car=array(0=>0, 10=>0);
//var_dump($car);
//var_dump(empty($car));
$car=array_filter($car);
//var_dump($car);
$car='car';
$bike='bike';
$car.=$bike.='can go';
//var_dump($car);
$too=array();
$too[10]='car';
$too[20]['cae']='car';
//var_dump($too);

//echo "cars are ad $too[10]<br>";
//$car_2=htmlspecialchars("1\200000// is the/cr \<car>");
//echo ($car_2);
//$too=array_diff($too,[$too[10]]);
//var_dump($too);
//echo(substr())
?>