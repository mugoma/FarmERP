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
$quantity= $product_id=$product_name= $grow_product=$notes=$prev_quantity="";
$quantity_err = $product_err = "";
$product_sql="SELECT  erp_product_quantity_current.id,erp_product_quantity_current.date
        erp_product_quantity_current.name AS product_name, erp_product_quantity_current.date_added,
        erp_product.grows,erp_product_quantity_current.quantity AS quantity,
        erp_product.id AS product_id, erp_grow_product.id   AS  grow_product_id 
    FROM erp_product_quantity_current 
    LEFT JOIN erp_product ON erp_product.id=erp_product_quantity_current.product_id
    LEFT JOIN erp_grow_product ON erp_grow_product.id=erp_product_quantity_current.grow_product_id
    WHERE erp_product_quantity_current.quantity > 0;";

$product_query=pg_query($link, $product_sql);

$product_list=pg_fetch_all($product_query);
// Processing form data when form is submitted


if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(empty($_POST['quantity']) || !is_int($_POST['quantity']+0)){
        $quantity_err='Please enter a valid quantity.';
    }else{
        $product_present=false;
        $product_name=test_input($_POST['product_id'] );
        $quantity=test_input($_POST['quantity']);

        foreach($product_list as $value){

            if(($value['id'] )==$product_name){
                $product_present=true;
                $product_quantity_name=$value['name'];
                $product_id=$value['product_id'] ?? $value['grow_product_id'];
                $grow_product=!empty($value['product_id'])?false:true;
                $prev_quantity=$value['quantity'];
                if ($value['quantity']<$quantity){
                    $quantity="";
                    $quantity_err='Quantity Being Destroyed Is Greater Than Quantity In Store';
                }
            break;
            }            
        }
    }
    if($product_present==false){
        $product_err='The Submitted Product, Does Not Exist';
    }
    $notes=test_input($_POST['notes']);

    // Check input errors before inserting in database
    if(empty($quantity_err) && empty($product_err)){
        // Prepare an insert statement
        $prev_quantity=pg_fetch_assoc(pg_query($link, "SELECT id FROM erp_product_quantity_current WHERE id='$product_name'"))[0]['quantity'];
        pg_query($link, 'BEGIN;');

        $sql_1 = "INSERT INTO erp_product_quantity_records(quantity, transaction_type, product_quantity_current_id,added_by, previous_quantity) VALUES($1, $2, 'Decrease', $3,$4, $5);";
        $sql_2="UPDATE  erp_product_quantity_current SET quantity = quantity - $1 WHERE id = $2;";
        $sql_3 = "INSERT INTO  erp_destroyed_products_record  (quantity, added_by, notes";
        if($grow_product){
            $sql_3.=", grow_product_id)";
        }else{
            $sql_3.=", product_id)";

        }

        $sql_3.= " VALUES($1, $2, $3, $4);";

        if((pg_prepare($link,'stmt_insert_1', $sql_1)) && pg_prepare($link,'stmt_insert_2', $sql_2) && pg_prepare($link, 'stmt_update_1', $sql_3)){
            // Bind variables to the prepared statement as parameters
            // Set parameters
            $param_id = $product_name;
            $param_name = $product_name;
            $param_notes = $notes;
            $param_quantity = $quantity;
            $param_product_id= $product_id;
            $param_prev_quantity=$prev_quantity;
            // Attempt to execute the prepared statement
            $execute_1=pg_execute($link, 'stmt_insert_1', array( $param_quantity, $param_id, $session_username, $prev_quantity));
            $execute_2=pg_execute($link, 'stmt_insert_2', array($param_quantity,$param_id));
            $execute_3=pg_execute($link, 'stmt_update_1', array($param_quantity,$session_username,$param_notes, $product_id));
            if($execute_1 && $execute_2 && $execute_3  && pg_query($link, 'COMMIT;')){
                $quantity= $product_id=$product_name= $grow_product=$notes="";
                DisplaySuccessMessage();
                $product_query=pg_query($link,$product_sql);

                $product_list=pg_fetch_all($product_query);

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
        <title>Add Product Destruction Record<?=" | Forms | ".SOFTWARE_NAME ?></title>
        <?php require_once(realpath(dirname(__FILE__) .'/..'.'/..') ."/"."include/header.php")?>
        <style type="text/css">
            body{ font: 14px sans-serif; }
        </style>
    </head>
    <body>
        <?php require_once (realpath(dirname(__FILE__) . '/..'.'/..') ."/"."nav.php");?>
        <main>
            <div class="wrapper container">
                <h2>Add Product Destruction Record</h2>
                <p>Please fill this form.</p>
                <form  method="post">
                    <div class="form-group">
                        <label for='product'>Product *</label>
                        <select class="form-control <?= (!empty($product_err)) ? 'is-invalid' : ''; ?>" id="process" name="product_name"  required>
                            <option value='' disabled='disabled' <?= (!empty($product_id)) ? '' : 'selected'; ?>>Please select a product</option>";

                            <?php  foreach($product_list as $product_int){?>
                                        <option value='<?=$product_int['id']?>' data-max='<?=$product_int['quantity']?>'><?=ucfirst($product_int['product_name']).($product_int['date'])?$product_int['date']:"";?></option>";
   
                                <?php }  ?>
                        </select>
                        <span class="invalid-feedback"><?= $product_err ; ?></span>

                    </div>
                    <div class="form-group">
                        <label for="quantity">Quantity *</label>
                        <input type="number" step="0.1" class="form-control <?= (!empty($quantity_err)) ? 'is-invalid' : ''; ?>" id="quantity" name='quantity' value="<?= (!empty($quantity)) ? $quantity : '0'; ?>"  min='1'>
                        <span class="invalid-feedback"><?= $quantity_err ; ?></span>

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
                <?php foreach($product_list as $product_int){?>
                    <p><strong><?= ucfirst($product_int['product_name'])?>: </strong><?= $product_int['quantity']?></p>
                <?php } ?>
            </div>
        </main>
        <?php require_once(realpath(dirname(__FILE__) . '/..'. '/..') ."/"."include/footer.php")?>

    </body>
</html>