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
$product= $amount=$quantity=$notes=$name=$product_id=$cost_per_unit=$quantity_id="";
$product_err = $amount_err=$quantity_err=$notes_err=$cost_per_unit_err=$building_id_err="";

$product_sql="SELECT id, name, grows FROM erp_product WHERE (purchase ='true' AND grows='true')";
$product_querry=pg_query($link, $product_sql);
$product_list=pg_fetch_all($product_querry);

$building_sql="SELECT * FROM erp_farm_building WHERE active='true'";
$building_query=pg_query($link, $building_sql);
$building_list=pg_fetch_all($building_query);
// Processing form data when form is submitted


if($_SERVER["REQUEST_METHOD"] == "POST"){

    if((empty(test_input($_POST["amount"])) && test_input($_POST["amount"])!='0') || test_input($_POST['amount']) < 0){
        $amount_err = "Please enter a valid amount";
    }else{
        $amount = test_input($_POST["amount"])+0;
    };
    
    if(empty(test_input($_POST["quantity"])) || test_input($_POST['quantity']) < 0){
        $quantity_err = "Please enter a valid quantity"; 
    }else{
        $quantity = test_input($_POST["quantity"])+0;
    };
    if((empty(test_input($_POST["cost_per_unit"]) && test_input($_POST["cost_per_unit"])!='0')) || test_input($_POST['cost_per_unit']) < 0){
        $cost_per_unit_err = "Please enter a valid cost per unit"; 
    }else{
        $cost_per_unit = test_input($_POST["cost_per_unit"])+0;
    };
    if(!empty($amount) && !empty($quantity) && !empty($cost_per_unit) && ($cost_per_unit*$quantity)!=$amount){
        $amount_err="Total cost does not add up";
    }

    if(empty(test_input($_POST["product_id"])) || test_input($_POST["product_id"])+0 < 0){
        $product_err = "Please select a valid product" ;     
    }else{
        $id='';
        $product = test_input($_POST["product_id"])+0;

        foreach ($product_list as $value) {
            if ($value['id']==$_POST["product_id"]){
                $id=true;
                $product_id=$value['id'];
                $name=$value['name'];
            break;
            }
        }
        if (empty($id)) {
            $product_err = 'The selected product does not exists';
        }
    };
    if(empty($_POST['building_id']) || !in_array($_POST['building_id'],array_column($building_list,'id'))){
        $building_id_err="Please select a valid building";

    }else{
        $building_id=$_POST['building_id'];
    }
    $notes = test_input($_POST["notes"]);
    
    // Check input errors before inserting in database
    if(
        empty($product_err) 
        && empty($amount_err) 
        && empty($cost_per_unit_err) 
        && empty($quantity_err)
        ){
        pg_query($link, 'BEGIN;');
        $sql_1 = "INSERT INTO erp_purchase (product_id, amount, quantity, notes, cost_per_unit, added_by) VALUES ($1, $2,$3,$4,$5, $6)";
        $sql_2 = "INSERT INTO erp_product_quantity_records (quantity,transaction_type, product_quantity_current_id, added_by,previous_quantity) VALUES ($1, 'Increase',$2,$3,$4)";
        $sql_3 = $sql_4 = $grows= '';

        $sql_5="INSERT INTO erp_cashbook (folio, amount, transaction_type,added_by) VALUES($1, $2, 'Cr',$3);";
        $param_product_quantity_name='';
        $param_check_exists_row = strtolower($name)."(".date("Y-m-d").")";

        foreach($product_list as $product_int){
            if ($product_int['id'] == $product && $product_int['grows']=='t'&& pg_num_rows($quantity_id_query = pg_query($link, "SELECT table1.product_quantity_current_id AS id FROM erp_grow_product table1 WHERE lower(table1.name)=lower('$param_check_exists_row') AND table1.building_id='$building_id'"))==1 ) {
                $grows=false;
                $sql_3 = "UPDATE erp_product_quantity_current SET  quantity=quantity+$1 WHERE id = $2 RETURNING id";
                $param_product_quantity_name = strtolower($name)."(".date("Y-m-d").")";
            break;
            }elseif ($product_int['id'] == $product && $product_int['grows']=='t') {
                $grows=true;
                $sql_3 = "INSERT INTO erp_product_quantity_current (quantity, product_id,grows) VALUES ($1, $2,'true') RETURNING id";
                $sql_4 = "INSERT INTO erp_grow_product (product_id,quantity, name, building_id, product_quantity_current_id) VALUES ($1, $2, $3,$4,$5) RETURNING id";
                $sql_6="INSERT INTO erp_farm_building_occupancy_record (farm_building_id,grow_product_id) VALUES($1,$2);";
                $param_product_quantity_name = strtolower($name)."(".date("Y-m-d").")";
            break;
            }
        }

        if(
            pg_prepare($link,'stmt_insert_1', $sql_1) 
            && pg_prepare($link,'stmt_insert_2', $sql_2) 
            && pg_prepare($link,'stmt_insert_3', $sql_3) 
            && ($grows==false ||  pg_prepare($link,'stmt_insert_4', $sql_4))
            && ($grows==false ||  pg_prepare($link,'stmt_insert_6', $sql_6))
            && pg_prepare($link,'stmt_insert_5', $sql_5)
            ){            
            // Set parameters
            $param_product_id = $product;
            $param_amount = $amount;
            $param_cost_per_unit = $cost_per_unit;
            $param_quantity= $quantity;
            $param_notes=$notes;
            $param_name=$name;
            $param_farm_building_id=$building_id;
            $execute_3=$execute_4=$execute_6=true;

            $quantity_prev=0;
            $result_row='';

            if ($grows==false){
                $quantity_id=pg_fetch_assoc($quantity_id_query);
                $quantity_id=$quantity_id['id'];

                $quantity_prev=pg_fetch_assoc(pg_query($link, "SELECT quantity FROM erp_product_quantity_current WHERE id =$quantity_id;"))['quantity'];

                $execute_3=pg_execute($link, 'stmt_insert_3',array($param_quantity,  $quantity_id) );

            }elseif($grows==true) {
                $execute_3=pg_execute($link, 'stmt_insert_3', array($param_quantity, $product_id));

                $execute_4=pg_execute($link, 'stmt_insert_4', array($param_product_id, $param_quantity, $param_product_quantity_name,$building_id, $result_row=pg_fetch_assoc($execute_3,0)['id']            ));
                $param_grow_product_id=pg_fetch_assoc($execute_4,0)['id'];
                $execute_6=pg_execute($link, 'stmt_insert_6', array($param_farm_building_id,$param_grow_product_id));
            }
            $result_row=(empty($result_row))?pg_fetch_assoc($execute_3,0)['id']:$result_row;
            //$product_quantity_id=$result_row['id'];

            $array_insert_1=array($param_product_id, $param_amount, $param_quantity,$param_notes, $param_cost_per_unit,$session_username);
            $array_insert_2=array($param_quantity,$result_row,$session_username,$quantity_prev);

            $execute_1=pg_execute($link, 'stmt_insert_1',$array_insert_1);
            $execute_2=pg_execute($link, 'stmt_insert_2',$array_insert_2);

            $execute_5=pg_execute($link, 'stmt_insert_5', array("Product Purchase ($param_product_quantity_name)", $param_amount, $session_username));

            // Attempt to execute the prepared statement
            if(
                $execute_1 
                && $execute_2 
                && $execute_3 
                && ($grows==false || $execute_4)
                &&($execute_5) 
                && pg_query($link, 'COMMIT;')){
                $product= $amount=$quantity=$notes=$cost_per_unit="";

                DisplaySuccessMessage();
            } else{
                pg_query($link,"ROLLBACK;");
                echo "Something went wrong. Please try again later.";
            };
        };
    };
    
    // Close connection
    pg_close($link);
};

?>
 
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Add Product Purchase Record<?=" | Forms | ".SOFTWARE_NAME ?></title>
        <?php require_once(realpath(dirname(__FILE__) .'/..'.'/..') ."/"."include/header.php")?>
        <style type="text/css">
            body{ font: 14px sans-serif; }
        </style>
    </head>
    <body>
        <?php require_once (realpath(dirname(__FILE__) . '/..'.'/..') ."/"."nav.php");?>
        <main>
            <div class="wrapper container">
                <h2>Add Product Purchase Record</h2>
                <p>Please fill this form.</p>
                <form  method="post">
                    <div class="form-group">
                            <label for="product">Product *</label>
                            <select class="form-control select_multiple <?= (!empty($product_err)) ? 'is-invalid' : ''; ?>" id="product" name="product_id">
                                <?php
                                    $x=0;  foreach($product_list as $product_int) { ?>
                                <option value='<?=$product_int['id']?>' <?= ($product_int['id'] == $product || (!$product && $x==0))?"selected":""; ?> > <?= $product_int['name'] ?> </option>
                                        
                                       <?php $x++;} ?>
                            </select>
                        <span class="invalid-feedback"><?= $product_err; ?></span>
                    </div>
                    <div class="form-group">
                            <label for="building ">Farm Building *</label>
                            <select class="form-control select_multiple <?= (!empty($product_err)) ? 'is-invalid' : ''; ?>" id="building" name="building_id">
                                <?php
                                    $x=0;  foreach($building_list as $product_int) { ?>
                                <option value='<?=$product_int['id']?>' <?= ($product_int['id'] == $product || (!$product && $x==0))?"selected":""; ?> > <?= $product_int['name'] ?> </option>
                                        
                                       <?php $x++;} ?>
                            </select>
                        <span class="invalid-feedback"><?= $building_id_err; ?></span>
                    </div>
                    <div class="form-row">
                        <div class='col col-sm-12 col-lg-4'>
                            <div class="form-group ">
                                <label for='cost_per_unit'>Cost Per Unit *</label>
                                <input type="number" name="cost_per_unit" class="form-control <?= (!empty($cost_per_unit_err)) ? 'is-invalid' : ''; ?>" value="<?= $cost_per_unit; ?>" id='cost_per_unit' onchange="get_total_price()" step="0.01">
                                <span class="invalid-feedback"><?= $cost_per_unit_err ?></span>
                            </div>
                        </div>

                        <div class='col col-sm-12 col-lg-4'>
                            <div class="form-group ">
                                <label for='quantity'>Quantity *</label>
                                <input type="number" name="quantity" class="form-control <?= (!empty($quantity_err)) ? 'is-invalid' : ''; ?>" value="<?= $quantity; ?>" id='quantity' onchange="get_total_price()" step="0.1">
                                <span class="invalid-feedback"><?= $quantity_err ?></span>
                            </div>
                        </div>

                        <div class='col col-sm-12 col-lg-4'>
                            <div class="form-group ">
                                <label for='amount'>Total Cost</label>
                                <input type="number" name="amount" class="form-control <?= (!empty($amount_err)) ? 'is-invalid' : ''; ?>" value="<?= $amount; ?>" id='amount' readonly>
                                <span class="invalid-feedback"><?= $amount_err ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for='notes'>Notes</label>
                        <textarea name="notes" class="form-control  <?= (!empty($notes_err)) ? 'is-invalid' : ''; ?>" value="<?= $notes; ?>" id='notes'></textarea>
                        <span class="invalid-feedback"><?= $notes_err ?></span>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-primary" value="Submit">
                        <input type="reset" class="btn btn-default" value="Reset">
                    </div>
                </form>
                <?=$required_reminder?>

            </div> 

        </main>
        <?php require_once(realpath(dirname(__FILE__) . '/..'. '/..') ."/"."include/footer.php")?>


    </body>
</html>