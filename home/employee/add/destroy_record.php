<?php
session_start();
if(!$_SESSION["loggedin"]){
    $_SESSION["login_redirect"]=true;
    header("location: /auth/login.php?next=".$_SERVER['PHP_SELF']);
};
/*
if (preg_match('(registration.php)', $_SERVER['PHP_SELF'])) {
    Header("Location: error.php?err=01");
    exit();
}*/
// Include config file
require_once(realpath(dirname(__FILE__) . '/..'.'/..'.'/..') ."/"."config.php");
 
// Define variables and initialize with empty values
$quantity= $product_id=$product_name= $grow_product=$notes="";
$quantity_err = $product_err = "";

$product_query=pg_query($link, "SELECT  erp_product_quantity_current.name AS product_name, erp_product_quantity_current.quantity AS quantity, erp_product.id AS product_id, erp_grow_product.id   AS  grow_product_id FROM erp_product_quantity_current 
LEFT JOIN erp_product ON lower(erp_product.name)=lower(erp_product_quantity_current.name)
LEFT JOIN erp_grow_product ON lower(erp_grow_product.name)=lower(erp_product_quantity_current.name)
WHERE erp_product_quantity_current.quantity > 0;");

$product_list=pg_fetch_all($product_query);
// Processing form data when form is submitted


if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(empty($_POST['quantity']) || !is_int($_POST['quantity']+0)){
        $quantity_err='Please Enter A Valid Quantity';
    }else{
        $product_present=false;
        $product_name=test_input($_POST['product_name'] );
        $quantity=test_input($_POST['quantity']);

        foreach($product_list as $value){

            if(($value['product_name'] )==$product_name){
                $product_present=true;
                $product_id=$value['product_id'] ?? $value['grow_product_id'];
                $grow_product=!empty($value['product_id'])?false:true;
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
        pg_query($link, 'BEGIN;');
        $sql_1 = "INSERT INTO erp_product_quantity_records(name, quantity, transaction_type) VALUES($1, $2, 'Decrease');";
        $sql_2="UPDATE  erp_product_quantity_current SET quantity = quantity - $1 WHERE lower(erp_product_quantity_current.name) = lower($2);";
        $sql_3 = "INSERT INTO  erp_destroyed_products_record  (quantity, notes";
        if($grow_product){
            $sql_3.=", grow_product_id)";
        }else{
            $sql_3.=", product_id)";

        }

        $sql_3.= " VALUES($1, $2, $3);";

        if((pg_prepare($link,'stmt_insert_1', $sql_1)) && pg_prepare($link,'stmt_insert_2', $sql_2) && pg_prepare($link, 'stmt_update_1', $sql_3)){
            // Bind variables to the prepared statement as parameters
            // Set parameters
            $param_name = $product_name;
            $param_notes = $notes;
            $param_quantity = $quantity;
            $param_product_id= $product_id;
            // Attempt to execute the prepared statement
            $execute_1=pg_execute($link, 'stmt_insert_1', array(strtolower($param_name), $param_quantity));
            $execute_2=pg_execute($link, 'stmt_insert_2', array($param_quantity,strtolower($param_name)));
            $execute_3=pg_execute($link, 'stmt_update_1', array($param_quantity,$param_notes, $product_id));
            if($execute_1 && $execute_2 && $execute_3  && pg_query($link, 'COMMIT;')){
                $quantity= $product_id=$product_name= $grow_product=$notes="";
                $product_query=pg_query($link, "SELECT  erp_product_quantity_current.name AS product_name, erp_product_quantity_current.quantity AS quantity, erp_product.id AS product_id, erp_grow_product.id   AS  grow_product_id FROM erp_product_quantity_current 
                LEFT JOIN erp_product ON lower(erp_product.name)=lower(erp_product_quantity_current.name)
                LEFT JOIN erp_grow_product ON lower(erp_grow_product.name)=lower(erp_product_quantity_current.name)
                WHERE erp_product_quantity_current.quantity > 0;");

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
        <title>Add Product Purchase Record</title>
        <?php require_once (realpath(dirname(__FILE__) . '/..'. '/..'.'/..') ."/"."include/header.php")?>
        <style type="text/css">
            body{ font: 14px sans-serif; }
        </style>
    </head>
    <body>
        <?php require_once(realpath(dirname(__FILE__) . '/..'. '/..') ."/"."employee/nav.php")?>
        <main>
            <div class="wrapper container">
                <h2>Add Product Destruction Record</h2>
                <p>Please fill this form.</p>
                <form  method="post">
                    <div class="form-group">
                        <label for='product'>Product</label>
                        <select class="form-control <?php echo (!empty($product_err)) ? 'is-invalid' : ''; ?>" id="process" name="product_name"  required>
                            <option value='' disabled='disabled' <?php echo (!empty($product_id)) ? '' : 'selected'; ?>>Please select a product</option>";

                            <?php
                                foreach($product_list as $product_int){
                                        echo "<option value='".$product_int['product_name']."' data-max='".$product_int['quantity']."'>".ucfirst($product_int['product_name'])."</option>";
   
                                }
                            ?>
                        </select>
                        <span class="invalid-feedback"><?php echo $product_err ; ?></span>

                    </div>
                    <div class="form-group">
                        <label for="quantity">Quantity</label>
                        <input type="number" class="form-control <?php echo (!empty($quantity_err)) ? 'is-invalid' : ''; ?>" id="quantity" name='quantity' value="<?php echo (!empty($quantity)) ? $quantity : '0'; ?>"  min='1'>
                        <span class="invalid-feedback"><?php echo $quantity_err ; ?></span>

                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-primary" value="Submit">
                        <input type="reset" class="btn btn-default" value="Reset">
                    </div>
                </form>
            </div> 
            <div class="container">
                <h2>Current Quantity</h2>
                <?php foreach($product_list as $product_int){?>
                    <p><strong><?php echo ucfirst($product_int['product_name'])?>: </strong><?php echo $product_int['quantity']?></p>
                <?php } ?>
            </div>
        </main>
        <?php require_once(realpath(dirname(__FILE__) . '/..'. '/..'. '/..') ."/"."include/footer.php")?>

    </body>
</html>