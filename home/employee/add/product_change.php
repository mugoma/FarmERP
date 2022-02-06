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
$quantity= $product_from=$product_to=$notes=$product_to_id=$product_to_name=$product_from_name=$product_from_id="";
$quantity_err = $product_from_err = $product_to_err="";

$product_from_sql="SELECT  erp_product_quantity_current.name , erp_product_quantity_current.id,erp_product_quantity_current.quantity  FROM erp_product_quantity_current 
WHERE erp_product_quantity_current.quantity > 0;";
$product_to_sql="SELECT  erp_product.name , erp_product.id, erp_product.grows  FROM erp_product 
WHERE erp_product.active='true';";
$product_from_query=pg_query($link, $product_from_sql);

$product_from_list=pg_fetch_all($product_from_query);

$product_to_query=pg_query($link, $product_to_sql);

$product_to_list=pg_fetch_all($product_to_query);
// Processing form data when form is submitted

$product_from_present=$product_to_present=false;
$grow_product_from=$grow_product_to='';
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(empty($_POST['quantity']) || !is_int($_POST['quantity']+0)){
        $quantity_err='Please Enter A Valid Quantity';
    }else{

        $product_from=test_input($_POST['product_from'] );
        $quantity=test_input($_POST['quantity']);

        foreach($product_from_list as $value){

            if(($value['id'] )==$product_from){
                $product_from_present=true;
                $product_from_name=$value['name'];
                $product_from_id=$value['id']+0;
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
            //$product_to_id=$value['id'] +0;
            $grow_product_to=($value['grows'])=='t'? true : false ;
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
    $notes=test_input($_POST['notes']);

    // Check input errors before inserting in database
    if(empty($quantity_err) && empty($product_from_err) && empty($product_to_err)){
        // Prepare an insert statement
        pg_query($link, 'BEGIN;');
        $sql_1 = "INSERT INTO erp_product_change_record (quantity, notes, product_from, product_to) VALUES ($1, $2, $3, $4);";
        $sql_2 = "INSERT INTO  erp_product_quantity_records(name, quantity, transaction_type) VALUES  ($1, $2, 'Increase'), ($3, $2, 'Decrease')";
        $sql_3 = "UPDATE  erp_product_quantity_current SET quantity = quantity - $1 WHERE erp_product_quantity_current.id = $2;";
        $sql_4=($grow_product_to)? 
            "INSERT INTO erp_product_quantity_current (quantity,name) VALUES ($1, $2);"
            :"UPDATE  erp_product_quantity_current 
                SET quantity = quantity + $1 
                WHERE erp_product_quantity_current.id = $2;";
        $sql_5=($grow_product_to)? "INSERT INTO erp_grow_product (product_id,quantity, name) VALUES ($1, $2, $3);":"";
        //$sql_1.=($grow_product_from)?'grow_product_from_id,':'product_from_id,';
        //$sql_1.=($grow_product_to)?'grow_product_to_id':'product_to_id';

        //$sql_1.= " ) VALUES($1, $2, $3, $4);";

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
            $execute_1=pg_execute($link, 'stmt_insert_1', array($param_quantity, $param_notes, $param_product_from_name, $param_product_to_name));
            $execute_2=pg_execute($link, 'stmt_insert_2', array($param_product_to_name,$param_quantity, $param_product_from_name));
            $execute_3=pg_execute($link, 'stmt_update_1', array($param_quantity,$param_product_from_id));
            $array_execute_4=array($param_quantity,($grow_product_to)?$param_product_to_name:$param_product_to_id);
            $execute_4=pg_execute($link, 'stmt_4', $array_execute_4);
            $execute_5=($grow_product_to==false || pg_execute($link, 'stmt_insert_3', array(array_column($product_to_list, 'id', 'name')[($product_to_name)], $param_quantity, $param_product_to_name)) );
            if($execute_1 && $execute_2 && $execute_3  && $execute_4 && $execute_5&& pg_query($link, 'COMMIT;')){
                $quantity= $product_from=$product_to=$notes=$product_to_id=$product_to_name="";
                $product_to_query=pg_query($link, $product_to_sql);
                $product_from_query=pg_query($link, $product_from_sql);
                $product_from_list=pg_fetch_all($product_from_query);                
                $product_to_list=pg_fetch_all($product_to_query);;
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
                <h2>Add Product Change Record</h2>
                <p>Please fill this form.</p>
                <form  method="post">
                    <div class='form-row'>
                        <div class='col'>
                            <div class="form-group">
                                <label for='product-from'> Current Product</label>
                                <select class="form-control <?php echo (!empty($product_err)) ? 'is-invalid' : ''; ?>" id="product-from" name="product_from"  required>
                                    <option value='' disabled='disabled' <?php echo (!empty($product_id)) ? '' : 'selected'; ?>>Please select the current product</option>";

                                    <?php
                                        foreach($product_from_list as $product_int){
                                                echo "<option value='".($product_int['id']) ."'>".ucfirst($product_int['name'])."</option>";
                                        }
                                    ?>
                                </select>
                                <span class="invalid-feedback"><?php echo $product_from_err ; ?></span>

                            </div>
                        </div>
                        <div class='col'>
                    
                            <div class="form-group">
                                <label for='product-to'> Final Product</label>
                                <select class="form-control <?php echo (!empty($product_to_err)) ? 'is-invalid' : ''; ?>" id="product-to" name="product_to"  required>
                                    <option value='' disabled='disabled' <?php echo (!empty($product_id)) ? '' : 'selected'; ?>>Please select the final product</option>";

                                    <?php
                                        foreach($product_to_list as $product_int){
                                                echo "<option value='".$product_int['id']."'>".ucfirst($product_int['name'])."</option>";
                                        }
                                    ?>
                                </select>
                                <span class="invalid-feedback"><?php echo $product_to_err ; ?></span>

                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="quantity">Quantity</label>
                        <input type="number" class="form-control <?php echo (!empty($quantity_err)) ? 'is-invalid' : ''; ?>" id="quantity" name='quantity' value="<?php echo (!empty($quantity)) ? $quantity : '0'; ?>"  min='1'>
                        <span class="invalid-feedback"><?php echo $quantity_err ; ?></span>

                    </div>
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea name="notes" class="form-control" id="notes"><?php echo $notes?></textarea>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-primary" value="Submit">
                        <input type="reset" class="btn btn-default" value="Reset">
                    </div>
                </form>
            </div> 
            <div class="container">
                <h2>Current Quantity</h2>
                <?php foreach($product_from_list as $product_int){?>
                    <p><strong><?php echo ucfirst($product_int['name'])?>: </strong><?php echo $product_int['quantity']?></p>
                <?php } ?>
            </div>
        </main>
        <?php require_once(realpath(dirname(__FILE__) . '/..'. '/..'. '/..') ."/"."include/footer.php")?>

    </body>
</html>