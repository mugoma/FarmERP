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
$product= $amount=$quantity=$notes=$name=$product_quantity_id=$product_id="";
$product_err = $amount_err=$quantity_err=$notes_err="";
$product_query_sql="SELECT erp_product.id AS product_id, erp_product_quantity_current.id AS product_quantity_id, erp_product_quantity_current.name, erp_product_quantity_current.quantity, erp_product.grows FROM erp_product_quantity_current
    JOIN erp_product ON erp_product_quantity_current.product_id=erp_product.id
    WHERE (erp_product.sale=true  AND erp_product_quantity_current.quantity>0)";

$product_query=pg_query($link, $product_query_sql);

$product_list=pg_fetch_all($product_query);
// Processing form data when form is submitted


if($_SERVER["REQUEST_METHOD"] == "POST"){

    if((empty(test_input($_POST["amount"])) && test_input($_POST["amount"])!='0') || test_input($_POST['amount']) < 0){
        $amount = "Please enter a valid amount";
    }else{
        $amount = test_input($_POST["amount"])+0;
    };
    
    if((empty(test_input($_POST["quantity"])) && test_input($_POST["quantity"])!='0') || test_input($_POST['quantity']) < 0){
        $quantity = "Please enter a valid quantity"; 
    }else{
        $quantity = test_input($_POST["quantity"])+0;
    };

    if(empty(test_input($_POST["product_id"])) || test_input($_POST["product_id"])+0 < 0){
        $product_err = "Please select a valid product";     
    }else{
        $id='';
        $product = test_input($_POST["product_id"])+0;

        foreach ($product_list as $value) {
            if ($value['product_quantity_id']==$_POST["product_id"]){
                $id=true;
                $name=$value['name'];
                $product_quantity_id=$value['product_quantity_id'];
                $product_id=$value['product_id'];
                if ($value['quantity']<$quantity){
                    $quantity="";
                    $quantity_err='Quantity Being Changed Is Greater Than Quantity In Store';
                }
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
        && empty($quantity_err)
        ){
        pg_query($link, 'BEGIN;');
        $sql_1 = "INSERT INTO erp_sales (product_id, amount, quantity, notes) VALUES ($1, $2,$3,$4);";
        $sql_2 = "INSERT INTO erp_product_quantity_records (name,quantity,transaction_type) VALUES ($1, $2,'Decrease');";
        $sql_3 = "UPDATE  erp_product_quantity_current SET quantity = quantity - $1 WHERE id=$2 ;";
        $sql_4="INSERT INTO erp_cashbook (folio, amount, transaction_type) VALUES($1, $2, 'Dr');";

        if(
            pg_prepare($link,'stmt_insert_1', $sql_1) 
            && pg_prepare($link,'stmt_insert_2', $sql_2) 
            && pg_prepare($link,'stmt_update_1', $sql_3)
            && pg_prepare($link,'stmt_insert_3', $sql_4)
            ){            
            // Set parameters
            $param_product_id = $product_id;
            $param_product_quantity_id = $product_quantity_id;
            $param_product_quantity_name= $name;
            $param_amount = $amount;
            $param_quantity= $quantity;
            $param_notes=$notes;
            $param_name=$name;
            $array_insert_1=array($param_product_id, $param_amount, $param_quantity,$param_notes);
            $array_insert_2=array($param_product_quantity_name, $param_quantity);
            $execute_1=pg_execute($link, 'stmt_insert_1',$array_insert_1);
            $execute_2=pg_execute($link, 'stmt_insert_2',$array_insert_2);
            $execute_3=pg_execute($link, 'stmt_update_1',array($param_quantity, $param_product_quantity_id));
            $execute_4=pg_execute($link, 'stmt_insert_3',array($param_product_quantity_name, $param_amount));

            // Attempt to execute the prepared statement
            if(
                $execute_1 
                && $execute_2 
                && $execute_3 
                && $execute_4 
                && pg_query($link, 'COMMIT;')){
                $product= $amount=$quantity=$notes="";

                $product_query=pg_query($link, $product_query_sql);

                $product_list=pg_fetch_all($product_query);

            } else{
                pg_query($link, 'ROLLBACK;');
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
        <title>Add Product Purchase</title>
        <?php require_once (realpath(dirname(__FILE__) . '/..'. '/..'.'/..') ."/"."include/header.php")?>
        <style type="text/css">
            body{ font: 14px sans-serif; }
        </style>
    </head>
    <body>
        <?php require_once(realpath(dirname(__FILE__) . '/..'. '/..') ."/"."employee/nav.php")?>
        <main>
            <div class="wrapper container">
                <h2>Add Product Sale</h2>
                <p>Please fill this form.</p>
                <form  method="post">
                    <div class="form-group ">
                            <label for="product">Product</label>
                            <select class="form-control <?= (!empty($product_err)) ? 'is-invalid' : ''; ?>" id="product" name="product_id">
                                <?php
                                    $x=0;
                                    foreach($product_list as $product_int){
                                        if ($product_int['product_quantity_id'] == $product) {
                                            echo "<option value='".$product_int['product_quantity_id']."' selected >".ucfirst($product_int['name'])."</option>";
                                        }elseif (!$product && $x==0) {
                                            echo "<option value='".$product_int['product_quantity_id']."' selected >".ucfirst($product_int['name'])."</option>";
                                        }
                                        else {
                                            echo "<option value='".$product_int['product_quantity_id']."'>".ucfirst($product_int['name'])."</option>";
                                        }
                                        $x++;
                                        
                                    }
                                ?>
                            </select>
                        <span class="invalid-feedback"><?= $product_err; ?></span>
                    </div>
                    <div class='form-row'>
                        <div class='col col-sm-12 col-lg-6'>
                
                            <div class="form-group">
                                <label for='amount'>Cost</label>
                                <input type="text" name="amount" class="form-control  <?= (!empty($amount_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $amount; ?>" id='name'>
                                <span class="invalid-feedback"><?=$amount_err ?></span>
                            </div>
                        </div>
                        <div class='col col-sm-12 col-lg-6'>
                            <div class="form-group <?php echo (!empty($quantity_err)) ? 'has-error' : ''; ?>">
                                <label for='quantity'>Quantity</label>
                                <input type="text" name="quantity" class="form-control" value="<?php echo $quantity; ?>" id='quantity'>
                                <span class="help-block"><?= $quantity_err ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group <?php echo (!empty($notes_err)) ? 'has-error' : ''; ?>">
                        <label for='notes'>Notes</label>
                        <textarea name="notes" class="form-control" value="<?php echo $notes; ?>" id='notes'></textarea>
                        <span class="help-block"><?= $notes_err ?></span>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-primary" value="Submit">
                        <input type="reset" class="btn btn-default" value="Reset">
                    </div>
                </form>
                <div class="container">
                    <h2>Current Quantity</h2>
                    <?php foreach($product_list as $product_int){?>
                        <p><strong><?php echo ucfirst($product_int['name'])?>: </strong><?php echo $product_int['quantity']?></p>
                    <?php } ?>
                </div>
            </div> 

        </main>
        <?php require_once(realpath(dirname(__FILE__) . '/..'. '/..'. '/..') ."/"."include/footer.php")?>


    </body>
</html>