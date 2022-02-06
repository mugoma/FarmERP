<?php
/*
$car=array();
$car[]=array('pink'=>'sing');
var_dump($car);

foreach($car as $toor){
    if($toor['pink']){
        echo 'hooray';
    }
}*/

//for ($i=2,$x=0; $i < (10); $i+=2, $x+=3) {
    //echo ' This is i: '.$i.'this is x: '.$x;
//}


$car=array('pink'=>array('color'=>'true'),'orrage'=>array('color'=>'true'), 'to'=>array('color'=>'false'));
//$car[]=array('black'=>'sing');
$name='pink';
//echo$car[strtolower($name)];
//var_dump($car);

//foreach($car as $the){
    //unset($car[$the]);
    //echo var_dump($the);
    //$car=\array_diff($car, [$the] );
//    if(empty($the) && $the !='0'){
//        $car=\array_diff($car, [$the]);
//    }
//}
//$car=\array_diff($car, ['talk'] );
//var_dump($car);
//var_dump('0');

$amount='0'+0;
//echo (array_column($car,'color')[1]);
$a='';
//echo ;

//$car=('a' or 'b');
//echo $car;

$json="{'car','week'}";
//var_dump("True");
//;
$col='cake';
$col=($col=='t')?"True":$col;
//var_dump($col);
//phpinfo();
$message = "Line 1\r\nLine 2\r\nLine 3";

// In case any of our lines are larger than 70 characters, we should use wordwrap()
$message = wordwrap($message, 70, "\r\n");

// Send
mail('mugoma99@gmail.com', 'My Subject', $message);
$car=2.1*5;
var_dump($car);
echo $car==10.5

?>