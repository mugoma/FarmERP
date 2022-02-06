<?php
// Include config file
require_once(realpath(dirname(__FILE__) . '/..'.'/..') ."/"."config.php");
redirecttologin($_SERVER['PHP_SELF']);
checkpermissions(array(2,3));


$name=$symbol=$status="";
$name_err=$symbol_err="";
if($_SERVER["REQUEST_METHOD"] == "GET"){
    if(empty(test_input($_REQUEST["symbol"]))){
        $symbol_err = "Please enter a valid symbol.";     
    }elseif(strlen(test_input($_REQUEST["symbol"])) >10){
        $symbol_err = "The symbol entered is too long.";     
    }else{
        $symbol=$_REQUEST['symbol'];
    }
    if(empty(test_input($_REQUEST["name"]))){
        $name_err = "Please enter a valid name.";     
    }elseif(strlen(test_input($_REQUEST["name"])) >100){
        $name_err = "The name entered is too long.";     
    }else{
        $name = test_input($_REQUEST["name"]);
        pg_prepare($link, 'stmt_name_exists',"SELECT name FROM erp_unit_of_measure WHERE (lower(name)=lower($1) or lower(symbol)=lower($2))");
        $names=pg_execute($link, 'stmt_name_exists', array($name, $symbol));

        if (pg_num_rows($names)!=0){
            $name_err.='A product with that name/symbol already exists';;
        }
    };
    if (empty($name_err) && empty($symbol_err)){
        if (pg_prepare($link, 'stmt', "INSERT INTO erp_unit_of_measure (name, symbol) VALUES($1, $2)")){
            if(pg_execute($link, 'stmt', array($name, $symbol))){
                $name=$symbol="";
                $status='Form Submitted Successfully';

            }else{
                $status='Internal Server error';

            }

        }else{
            $status='Internal Server error';

        }

    }else{
        $status='Form Data Error';

    }

    
}
$response_array=array();
$response_array['name']=$name;
$response_array['symbol']=$symbol;
$response_array['name_err']=$name_err;
$response_array['symbol_err']=$symbol_err;
$response_array['status']=$status;
echo json_encode($response_array);
?>