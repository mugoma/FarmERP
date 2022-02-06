<?php

/*
if (preg_match('(registration.php)', $_SERVER['PHP_SELF'])) {
    Header("Location: error.php?err=01");
    exit();
}*/
// Include config file
require_once(realpath(dirname(__FILE__) . '/..'.'/..') ."/"."config.php");
redirecttologin($_SERVER['PHP_SELF']);
checkpermissions(array(3,4));


// Define variables and initialize with empty values
$quantity= $product_from=$product_to=$notes=$product_to_id=$product_to_name=$product_from_name=$product_from_id="";
$quantity_err = $product_from_err = $product_to_err="";

$product_from_sql="SELECT  erp_product.name ,building.name AS building_name,building.id , erp_grow_product.name,erp_product_quantity_current.id,erp_product_quantity_current.quantity  FROM erp_product_quantity_current 
LEFT JOIN erp_product ON erp_product.id=erp_product_quantity_current.product_id AND erp_product_quantity_current.grows='false'
LEFT JOIN erp_grow_product ON erp_grow_product.product_quantity_current_id=erp_product_quantity_current.id
LEFT JOIN erp_farm_building building ON building.id=erp_grow_product.building_id
WHERE erp_product_quantity_current.quantity > 0";
$product_to_sql="SELECT  erp_product.name , erp_product.id, erp_product.grows , quantity.quantity FROM erp_product 
    JOIN erp_product_quantity_current quantity ON quantity.product_id=erp_product.id 
    WHERE erp_product.active='true';";

$product_from_query=pg_query($link, $product_from_sql);
$product_from_list=pg_fetch_all($product_from_query);

$product_to_query=pg_query($link, $product_to_sql);
$product_to_list=pg_fetch_all($product_to_query);

$building_sql="SELECT * FROM erp_farm_building WHERE active='true'";
$building_query=pg_query($link, $building_sql);
$building_list=pg_fetch_all($building_query);

// Processing form data when form is submitted

$product_from_present=$product_to_present=false;
$grow_product_from=$grow_product_to='';
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(empty($_POST['quantity']) || !is_int($_POST['quantity']+0)){
        $quantity_err='Please enter a valid quantity.';
    }else{

        $product_from=test_input($_POST['product_from'] );
        $quantity=test_input($_POST['quantity']);

        foreach($product_from_list as $value){

            if(($value['id'] )==$product_from){
                $product_from_present=true;
                $product_from_name=$value['name'];
                $product_from_id=$value['id']+0;
                $product_from_quantity=$value['quantity']+0;
                if ($value['quantity']<$quantity){
                    $quantity="";
                    $quantity_err='Quantity Being Changed Is Greater Than Quantity In Store';
                }
            break;
            }            
        }
    }
    $product_to=test_input($_POST['product_to']);

    foreach($product_to_list as $value){
        if(($value['id'] )==$product_to){
            $product_to_present=true;
            $product_to_name=$value['name'];
            $product_to_quantity=$value['quantity'];
            $grow_product_to=($value['grows'])=='t'? true : false ;
            $building_id=(!empty($value['building_id']))?  $value['building_id']: 0 ;
        break;
        }            
    }  
    $product_to_id=(array_column($product_from_list, 'id', 'name'))[strtolower($product_to_name)];  
    foreach($product_to_list as $value){
        if(strtolower($value['name'] )==strtolower($product_from)){
            $grow_product_from=!empty($value['grows'])?true:false;
        break;
        }            
    }

    if($product_from_present==false){
        $product_from_err='The Submitted Current Product Does Not Exist';
    }
    if($product_to_present==false){
        $product_to_err='The Submitted Final Product Does Not Exist';
    }
    if(array_key_exists('building_id', $_POST)  && !in_array($_POST['building_id'],array_column($building_list, 'id'))){
        $building_id_err="Please select a valid building";
    }elseif(!$grow_product_to && array_key_exists('building_id', $_POST) ){
        $building_id="";

    }elseif($grow_product_to && !array_key_exists('building_id', $_POST)){
        $building_id_err="Please select a valid building.";
    } else{
        $building_id=test_input($_POST['building_id']);
    }
    $notes=test_input($_POST['notes']);

    // Check input errors before inserting in database
    if(empty($quantity_err) && empty($product_from_err) && empty($product_to_err)){
        // Prepare an insert statement
        pg_query($link, 'BEGIN;');
        $param_check_exists_row=strtolower($product_to_name)."(".date("Y-m-d").")";
        $sql_1 = "INSERT INTO erp_product_change_record (quantity, notes, product_from, product_to, added_by) VALUES ($1, $2, $3, $4. $5);";
        $sql_2 = "INSERT INTO  erp_product_quantity_records(quantity, transaction_type, product_quantity_current_id, previous_quantity,added_by) VALUES  ($1,'Increase',$2,$3,$4), ($1, 'Decrease',$5,$6,$4)";
        $sql_3 = "UPDATE  erp_product_quantity_current SET quantity = quantity - $1 WHERE erp_product_quantity_current.id = $2;";
        $sql_4=($grow_product_to && $exists_row=pg_num_rows(pg_query($link, "SELECT table1.product_quantity_current_id AS id FROM erp_grow_product table1 WHERE lower(table1.name)=lower('$param_check_exists_row') AND table1.building_id='$building_id'"))!=1 )? 
            "INSERT INTO erp_product_quantity_current (grows,quantity,product_id, grow_product_id) VALUES ('true', $1,$2,$3) RETURNING id;"
            :"UPDATE  erp_product_quantity_current 
                SET quantity = quantity + $1 
                WHERE erp_product_quantity_current.id = $2 RETURNING id;";
        $sql_5=($grow_product_to && $exists_row!=1)? "INSERT INTO erp_grow_product (product_id,quantity, name, building_id) VALUES ($1, $2, $3) RETURNING id;":"";

        if(pg_prepare($link,'stmt_insert_1', $sql_1) 
            && pg_prepare($link,'stmt_insert_2', $sql_2) 
            && pg_prepare($link, 'stmt_update_1', $sql_3) 
            && pg_prepare($link, 'stmt_4', $sql_4) 
            && (pg_prepare($link, 'stmt_insert_3',$sql_5 )|| $grow_product_to==false)){
            // Bind variables to the prepared statement as parameters
            // Set parameters
            $param_product_from_name=strtolower($product_from_name);
            $param_product_from_id=$product_from_id;
            $param_notes = $notes;
            $param_quantity = $quantity+0;
            $param_product_to_name= ($grow_product_to)?strtolower($product_to_name)."(".date("Y-m-d").")":$product_to_name;
            $param_product_to_id= $product_to_id;
            // Attempt to execute the prepared statement
            $execute_5=($grow_product_to && $exists_row!=1)?pg_execute($link, 'stmt_insert_3', array(array_column($product_to_list, 'id', 'name')[($product_to_name)], $param_quantity, $param_product_to_name)) :($grow_product_to=false);
            $result_id=($grow_product_to && $exists_row!=1)?pg_fetch_row($execute_5):"";
            $param_product_to_grow_id=($grow_product_to)?$result_id[0]+0:"";

            $array_execute_4=($grow_product_to && $exists_row!=1)?array($param_quantity, $param_product_to_id,$param_product_to_grow_id):array($param_quantity );//array($param_quantity,($grow_product_to)?$param_product_to_name:$param_product_to_id);
            $execute_4=pg_execute($link, 'stmt_4', $array_execute_4);
            $result_id=pg_fetch_row($execute_4);
            $product_to_quantity_id=$result_id[0]+0;

            $execute_1=pg_execute($link, 'stmt_insert_1', array($param_quantity, $param_notes, $param_product_from_name, $param_product_to_name, $session_username));
            $execute_2=pg_execute($link, 'stmt_insert_2', array($param_quantity, $product_to_quantity_id,($grow_product_to)?0:$product_to_quantity,$session_username,$product_to_quantity_id,$product_from_quantity));
            $execute_3=pg_execute($link, 'stmt_update_1', array($param_quantity,$param_product_from_id));
            
            if($execute_1 && $execute_2 && $execute_3  && $execute_4 && $execute_5&& pg_query($link, 'COMMIT;')){
                $quantity= $product_from=$product_to=$notes=$product_to_id=$product_to_name="";
                $product_to_query=pg_query($link, $product_to_sql);
                $product_from_query=pg_query($link, $product_from_sql);
                $product_from_list=pg_fetch_all($product_from_query);                
                $product_to_list=pg_fetch_all($product_to_query);;
                DisplaySuccessMessage();
            } else{
                pg_query($link, "ROLLBACK;");
                echo "Something went wrong. Please try again later.";
            };
        }
    };
    
    // Close connection
    pg_close($link);
};

?>
 
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Add Product Change Record<?=" | Forms | ".SOFTWARE_NAME ?></title>
        <?php require_once(realpath(dirname(__FILE__) .'/..'.'/..') ."/"."include/header.php")?>
        <style type="text/css">
            body{ font: 14px sans-serif; }
        </style>
    </head>
    <body>
        <?php require_once (realpath(dirname(__FILE__) . '/..'.'/..') ."/"."nav.php");?>
        <main>
            <div class="wrapper container">
                <h2>Add Product Change Record</h2>
                <p>Please fill this form.</p>
                <form  method="post">
                    <div class='form-row'>
                        <div class='col'>
                            <div class="form-group">
                                <label for='product-from'> Current Product *</label>
                                <select class="form-control select_multiple <?= (!empty($product_err)) ? 'is-invalid' : ''; ?>" id="product-from" name="product_from"  required>
                                    <option value='' disabled='disabled' <?= (!empty($product_id)) ? '' : 'selected'; ?>>Please select the current product</option>";

                                    <?php
                                        foreach($product_from_list as $product_int){
                                                echo "<option value='".($product_int['id']) ."'>".ucfirst($product_int['name'])."</option>";
                                        }
                                    ?>
                                </select>
                                <span class="invalid-feedback"><?= $product_from_err ; ?></span>

                            </div>
                        </div>
                        <div class='col'>
                    
                            <div class="form-group">
                                <label for='product-to'> Final Product *</label>
                                <select class="form-control select_multiple <?= (!empty($product_to_err)) ? 'is-invalid' : ''; ?>" id="product-to" name="product_to"  required>
                                    <option value='' disabled='disabled' <?= (!empty($product_id)) ? '' : 'selected'; ?>>Please select the final product</option>";

                                    <?php
                                        foreach($product_to_list as $product_int){
                                                echo "<option value='".$product_int['id']."'>".ucfirst($product_int['name'])."</option>";
                                        }
                                    ?>
                                </select>
                                <span class="invalid-feedback"><?= $product_to_err ; ?></span>

                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                            <label for="building ">Farm Building</label>
                            <select class="form-control select_multiple <?= (!empty($product_err)) ? 'is-invalid' : ''; ?>" id="building" name="building_id">
                                <?php
                                    $x=0;  foreach($building_list as $product_int) { ?>
                                <option value='<?=$product_int['id']?>' <?= ($product_int['id'] == $building_id || (!$building_id && $x==0))?"selected":""; ?> > <?= $product_int['name'] ?> </option>
                                        
                                       <?php $x++;} ?>
                            </select>
                            <small id="passwordHelpBlock" class="form-text text-muted">
                                Only select this field if the final product is calssified as'grows'.
                            </small>
                        <span class="invalid-feedback"><?= $building_id_err; ?></span>
                    </div>
                    <div class="form-group">
                        <label for="quantity">Quantity *</label>
                        <input type="number" class="form-control <?= (!empty($quantity_err)) ? 'is-invalid' : ''; ?>" id="quantity" name='quantity' value="<?= (!empty($quantity)) ? $quantity : '0'; ?>"  min='1'>
                        <span class="invalid-feedback"><?= $quantity_err ; ?></span>

                    </div>
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea name="notes" class="form-control" id="notes"><?= $notes?></textarea>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-primary" value="Submit">
                        <input type="reset" class="btn btn-default" value="Reset">
                    </div>
                </form>
                <?=$required_reminder?>

            </div> 
            <div class="container">
                <h2>Current Quantity</h2>
                <?php foreach($product_from_list as $product_int){?>
                    <p><strong><?= ucfirst($product_int['name'])?>: </strong><?= $product_int['quantity']?></p>
                <?php } ?>
            </div>
        </main>
        <?php require_once(realpath(dirname(__FILE__) . '/..'. '/..') ."/"."include/footer.php")?>

    </body>
</html>