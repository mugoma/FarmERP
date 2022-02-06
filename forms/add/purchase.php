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
$product= $amount=$quantity=$notes=$name=$product_id=$cost_per_unit="";
$product_err = $amount_err=$quantity_err=$notes_err=$cost_per_unit_err="";

$product_querry=pg_query($link, "SELECT product.id, product.name, quantity.quantity, quantity.id AS quantity_id FROM erp_product product 
        JOIN erp_product_quantity_current quantity ON product.id=quantity.product_id
    WHERE (product.purchase ='true' AND product.grows='false' AND product.active='true')");

$product_list=pg_fetch_all($product_querry);
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

    if(empty(test_input($_POST["product_id"])) || test_input($_POST["product_id"])+0 <= 0){
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
        $sql_3 = "";

        $sql_5="INSERT INTO erp_cashbook (folio, amount, transaction_type,added_by) VALUES($1, $2, 'Cr',$3);";
        $param_product_quantity_name='';
        //$param_check_exists_row = strtolower($name)."(".date("Y-m-d").")";

        $sql_3 = "UPDATE erp_product_quantity_current SET  quantity=quantity + $1 WHERE id = $2";
        $param_product_quantity_name = strtolower($name);

        if(
            pg_prepare($link,'stmt_insert_1', $sql_1) 
            && pg_prepare($link,'stmt_insert_2', $sql_2) 
            && pg_prepare($link,'stmt_insert_3', $sql_3) 
            && pg_prepare($link,'stmt_insert_5', $sql_5)
            ){            
            // Set parameters
            $param_product_id = $product;
            $param_amount = $amount;
            $param_cost_per_unit = $cost_per_unit;
            $param_quantity= $quantity;
            $param_notes=$notes;
            $param_name=$name;
            $quantity_product_array=array_column($product_list, 'quantity_id', 'id');
            $prev_quantity=(array_column($product_list,'quantity', 'quantity_id'))[$quantity_product_array[$param_product_id]];
            $array_insert_1=array($param_product_id, $param_amount, $param_quantity,$param_notes, $param_cost_per_unit,$session_username);
            $array_insert_2=array($param_quantity, $quantity_product_array[$param_product_id],$session_username,$prev_quantity );
            $execute_1=pg_execute($link, 'stmt_insert_1',$array_insert_1);
            $execute_2=pg_execute($link, 'stmt_insert_2',$array_insert_2);
            $execute_5=pg_execute($link, 'stmt_insert_5', array("Product Purchase($param_product_quantity_name)", $param_amount, $session_username));
            $execute_3=pg_execute($link, 'stmt_insert_3',array($param_quantity, $quantity_product_array[$param_product_id]) );
            //var_dump($quantity_product_array[$param_product_id]);
            //var_dump($quantity);
            // Attempt to execute the prepared statement
            if(
                $execute_1 
                && $execute_2 
                && $execute_3 
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
                                    $x=0;
                                    foreach($product_list as $product_int){
                                        if ($product_int['id'] == $product) {
                                            echo "<option value='".$product_int['id']."' selected >".$product_int['name']."</option>";
                                        }elseif (!$product && $x==0) {
                                            echo "<option value='".$product_int['id']."' selected >".$product_int['name']."</option>";
                                        }
                                        else {
                                            echo "<option value='".$product_int['id']."'>".$product_int['name']."</option>";
                                        }
                                        $x++;
                                        
                                    }
                                ?>
                            </select>
                        <span class="invalid-feedback"><?= $product_err; ?></span>
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