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
$product=$quantity=$notes=$name=$product_id="";
$product_err =$quantity_err=$notes_err="";

$product_querry=pg_query($link, "SELECT id, name, grows FROM erp_product WHERE (produced ='true')");

$product_list=pg_fetch_all($product_querry);
// Processing form data when form is submitted


if($_SERVER["REQUEST_METHOD"] == "POST"){

    
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
        && empty($quantity_err)
        ){
        pg_query($link, 'BEGIN;');
        $sql_1 = "INSERT INTO erp_production_record (product_id, quantity, notes) VALUES ($1, $2,$3)";
        $sql_2 = "INSERT INTO erp_product_quantity_records (name,quantity,transaction_type) VALUES ($1, $2,$3)";
        $sql_3 = $sql_4 = $grows= '';

        $param_product_quantity_name='';
        foreach($product_list as $product_int){
            if ($product_int['id'] == $product && $product_int['grows']=='t') {
                $grows=true;
                $sql_3 = "INSERT INTO erp_product_quantity_current (name,quantity, product_id) VALUES ($1, $2, $3)";
                $sql_4 = "INSERT INTO erp_grow_product (product_id,quantity, name) VALUES ($1, $2, $3)";
                $param_product_quantity_name = strtolower($name)."(".date("Y-m-d").")";
            break;
            }elseif($product_int['id'] == $product && $product_int['grows']=='f'){
                $grows=false;
                $sql_3 = "UPDATE erp_product_quantity_current SET  quantity=quantity+$1 WHERE lower(name) = lower($2)";
                $param_product_quantity_name = strtolower($name);
            break;
            }
        }

        if(
            pg_prepare($link,'stmt_insert_1', $sql_1) 
            && pg_prepare($link,'stmt_insert_2', $sql_2) 
            && pg_prepare($link,'stmt_insert_3', $sql_3) 
            && ($grows==false ||  pg_prepare($link,'stmt_insert_4', $sql_4))
            && pg_prepare($link,'stmt_insert_5', $sql_5)
            ){            
            // Set parameters
            $param_product_id = $product;
            $param_quantity= $quantity;
            $param_notes=$notes;
            $param_name=$name;
            $param_trans_type = 'Increase';
            $array_insert_1=array($param_product_id, $param_quantity,$param_notes);
            $array_insert_2=array($param_product_quantity_name, $param_quantity, $param_trans_type);
            $execute_1=pg_execute($link, 'stmt_insert_1',$array_insert_1);
            $execute_2=pg_execute($link, 'stmt_insert_2',$array_insert_2);
            $execute_3='';
            $execute_4='';
            if ($grows==false){
                $execute_3=pg_execute($link, 'stmt_insert_3',array($param_quantity, $param_product_quantity_name) );
            }elseif($grows==true) {
                $execute_3=pg_execute($link, 'stmt_insert_3', array($param_product_quantity_name, $param_quantity, $product_id));
                $execute_4=pg_execute($link, 'stmt_insert_4', array($param_product_id, $param_quantity, $param_product_quantity_name));
            }
            // Attempt to execute the prepared statement
            if(
                $execute_1 
                && $execute_2 
                && $execute_3 
                && ($grows==false || $execute_4)
                && pg_query($link, 'COMMIT;')){
                $product=$quantity=$notes="";

            } else{
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
                <h2>Add Production Record</h2>
                <p>Please fill this form.</p>
                <form  method="post">
                    <div class="form-group <?php echo (!empty($product_err)) ? 'has-error' : ''; ?>">
                            <label for="product">Product</label>
                            <select class="form-control" id="product" name="product_id">
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
                        <span class="help-block"><?php echo $product_err; ?></span>
                    </div>
                    <div class="form-group <?php echo (!empty($quantity_err)) ? 'has-error' : ''; ?>">
                        <label for='quantity'>Quantity</label>
                        <input type="text" name="quantity" class="form-control" value="<?php echo $quantity; ?>" id='quantity'>
                        <span class="help-block"><?php echo $quantity_err ?></span>
                    </div>
                    <div class="form-group <?php echo (!empty($notes_err)) ? 'has-error' : ''; ?>">
                        <label for='notes'>Notes</label>
                        <textarea name="notes" class="form-control" value="<?php echo $notes; ?>" id='notes'></textarea>
                        <span class="help-block"><?php echo $notes_err ?></span>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-primary" value="Submit">
                        <input type="reset" class="btn btn-default" value="Reset">
                    </div>
                </form>
            </div> 

        </main>
        <?php require_once(realpath(dirname(__FILE__) . '/..'. '/..'. '/..') ."/"."include/footer.php")?>


    </body>
</html>