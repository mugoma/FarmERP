<?php
session_start();
if(!$_SESSION["loggedin"]){
    $_SESSION["login_redirect"]=true;
    header("location: /auth/login.html?next=".substr($_SERVER['PHP_SELF'], 0, -3).'html' );
};
/*
if (preg_match('(registration.php)', $_SERVER['PHP_SELF'])) {
    Header("Location: error.php?err=01");
    exit();
}*/
// Include config file
require_once(realpath(dirname(__FILE__) . '/..'.'/..'.'/..') ."/"."config.php");

$product_sql="SELECT erp_product.id, erp_product.name FROM erp_product WHERE (erp_product.active='true');";
$product_query=pg_query($link, $product_sql);
$product_list=pg_fetch_all($product_query);
 
// Define variables and initialize with empty values
$product_id = "";
$product_id_err="";

// producting form data when form is submitted


if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(empty(test_input($_REQUEST['product_id']))){
        $product_id_err='Please Select A Product';
    }else{
        $product_id=test_input($_REQUEST['product_id']);

        pg_prepare($link, 'check_product_exists', "SELECT * FROM erp_product WHERE (id = $1 AND active='true');");
        $check_product_exists=pg_execute($link, 'check_product_exists', array($product_id));
        if (pg_num_rows($check_product_exists)!=1){
            $product_id_err="Error Retrieving The Submitted product";
        }
    }

    
    // Check input errors before inserting in database
    if(empty($product_id_err) ){
        if($stmt = pg_prepare($link,'stmt_insert', "UPDATE  erp_product SET active='false' WHERE id=$1;")){            
            // Attempt to execute the prepared statement
            if(pg_execute($link, 'stmt_insert',array($product_id))){
                $product_id_="";
                $product_query=pg_query($link, $product_sql);
                $product_list=pg_fetch_all($product_query);

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
        <title>Delete product | Yengas FarmERP</title>
        <?php require_once (realpath(dirname(__FILE__) . '/..'. '/..'.'/..') ."/"."include/header.php")?>
        <style type="text/css">
            body{ font: 14px sans-serif; }
        </style>
    </head>
    <body>
        <?php require_once(realpath(dirname(__FILE__) . '/..'. '/..') ."/"."employee/nav.php")?>
        <main>
            <div class="wrapper container">
                <h2>Delete Product</h2>
                <p>Please fill this form.</p>
                <form  id='form' method="post">
                    <div class="form-group ">
                        <label for='product'>Product</label>
                        <select class="form-control select_multiple <?php echo (!empty($product_id_err)) ? 'is-invalid' : ''; ?>" id="product" name="product_id" required>
                            <option value='' disabled='disabled' <?php echo (!empty($product_id)) ? '' : 'selected'; ?>>Please select a product</option>";

                            <?php
                                $x=0;
                                foreach($product_list as $product_int){
                                    if (($product_int['id']==$product_id)) {
                                        echo "<option value='".$product_int['id']."' selected >".$product_int['name']."</option>";
                                    }
                                    else {
                                        echo "<option value='".$product_int['id']."'>".$product_int['name']."</option>";
                                    }
                                    $x++;
                                    
                                }
                            ?>
                        </select>
                        <span class="invalid-feedback"><?= $product_id_err ?></span>
                    </div>
                    <div class="form-group">
                        <input type="button" class="btn btn-primary" value="Submit" data-toggle="modal" data-target="#confirmdeletemodal" >
                        <input type="reset" class="btn btn-default" value="Reset">
                    </div>
                </form>
            </div> 
        </main>
        <div class="modal fade" id="confirmdeletemodal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Confirm product Deletion</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are You Sure You Want To Delete This Product?</p>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="document.getElementById('form').submit()">Confirm Delete</button>
            </div>
            </div>
        </div>
    </div>
        <?php require_once(realpath(dirname(__FILE__) . '/..'. '/..'. '/..') ."/"."include/footer.php")?>


    </body>
</html>