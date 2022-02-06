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
$name= $sold = $purchase = $unit = $notes=$grows=$consumable="";
$name_err = $purchase_err = $sold_err = $unit_err =$notes_err=$grows_err=$consumable_err=$consumable_grow_err="";

$unit_querry=pg_query($link, "SELECT id, name, symbol FROM erp_unit_of_measure WHERE (active='true')");

$unit_list=pg_fetch_all($unit_querry);
// Processing form data when form is submitted


if($_SERVER["REQUEST_METHOD"] == "POST"){

    if(empty(test_input($_POST["name"]))){
        $name_err = "Please enter a valid name.";     
    }else{
        $name = test_input($_POST["name"]);
        $names=pg_query($link, "SELECT name FROM erp_product WHERE (name='$name')");
        $names_p=strtolower($name);
        $names_r=pg_query($link, "SELECT * FROM erp_product_quantity_records WHERE (lower(name)='$names_p')");

        if (pg_num_rows($names)!=0){
            $name_err.='A product with that name already exists';;
        }elseif(pg_num_rows($names_r)!=0){
            $name_err.='<br /> A product with that name already exists. Different cases do not differentiate a product name.';
        }
    };



    if(isset($_POST["sold"]) && test_input($_POST["sold"])!='true'){
        $sold_err = "Please enter valid value in sold field.";     
    
    }elseif(isset($_POST["sold"]) && test_input($_POST["sold"]) =='true'){
        $sold = 'true';
    }else{
        $sold = 'false';

    };
    if(isset($_POST["purchase"]) && test_input($_POST["purchase"])!='true'){
        $purchase_err = "Please enter valid value in purchase field.";     
    
    }elseif(isset($_POST["purchase"]) && test_input($_POST["purchase"]) =='true'){
        $purchase = 'true';
    }else{
        $purchase = 'false';

    };
    if(isset($_POST["grows"]) && test_input($_POST["grows"])!='true'){
        $grows_err = "Please enter valid value in grows field.";     
    
    }elseif(isset($_POST["grows"]) && test_input($_POST["grows"]) =='true'){
        $grows = 'true';
    }else{
        $grows = 'false';

    };
    if(isset($_POST["consumable"]) && test_input($_POST["consumable"])!='true'){
        $consumable_err = "Please enter valid value in grows field.";     
    
    }elseif(isset($_POST["consumable"]) && test_input($_POST["consumable"]) =='true'){
        $consumable = 'true';
    }else{
        $consumable = 'false';

    };
    if($consumable==$grows && $consumable='true'){
        $consumable_grow_err='A product cannot be consumable and grows at the same time. Please check only one.';
    }
    if(isset($_POST["produced"]) && test_input($_POST["produced"])!='true'){
        $produced_err = "Please enter valid value in produced field.";     
    
    }elseif(isset($_POST["produced"]) && test_input($_POST["produced"]) =='true'){
        $produced = 'true';
    }else{
        $produced = 'false';

    };
    if(empty(test_input($_POST["unit_id"])) || test_input($_POST["unit_id"])+0 < 0){
        $unit_err = "Please select a valid unit";     
    }else{
        $id='';
        $unit = test_input($_POST["unit_id"])+0;

        foreach ($unit_list as $value) {
            if ($value['id']==$_POST["unit_id"]){
                $id=true;
            break;
            }
        }
        if (empty($id)) {
            $unit_err = 'The selected unit does not exists';
        }
    };
    $notes = test_input($_POST["notes"]);



    // Check input errors before inserting in database
    if(empty($name_err) 
        && empty($purchase_err) 
        && empty($sold_err) 
        && empty($unit_err) 
        && empty($notes_err) 
        && empty($grows_err) 
        && empty($produced_err) 
        && empty($consumable_err)
        && empty($consumable_grow_err)){
        // Prepare an insert statement
        pg_query($link, 'BEGIN;');
        $sql = "INSERT INTO erp_product (name, sale, purchase, unit_of_measure_id, notes, grows, consumable, produced) VALUES ($1, $2, $3, $4, $5,$6, $7, $8) RETURNING id";
        $sql_2="";
        if ($grows=='false'){
            $sql_2 = "INSERT INTO  erp_product_quantity_current  (name, quantity, product_id) VALUES(lower($1), 0, $2)";
            $sql_3 = "INSERT INTO erp_product_quantity_records(name, quantity, transaction_type) VALUES ($1, 0, 'Increase')";
        }
        if((pg_prepare($link,'stmt_insert', $sql)) && ($grows!='false' ||  (pg_prepare($link,'stmt_insert_2', $sql_2) && pg_prepare($link, 'stmt_insert_3', $sql_3)))){
            // Bind variables to the prepared statement as parameters
            // Set parameters
            $param_name = $name;
            $param_purchase = $purchase;
            $param_sold = $sold;
            $param_unit = $unit;
            $param_notes = $notes;
            $param_grows = $grows;
            $param_produced = $produced;
            $param_consumable=$consumable;
            $exec_1=$exec_2=true;
            $array=array($param_name, $param_sold, $param_purchase, $param_unit, $param_notes, $param_grows,$param_consumable, $param_produced);
            $result=pg_execute($link, 'stmt_insert',$array);
            $id=pg_fetch_row($result )[0]+0;
            if($grows=='false'){
                $exec_1=pg_execute($link, 'stmt_insert_3', array(strtolower($param_name))) && pg_execute($link, 'stmt_insert_2',array($param_name, $id));
            }
            // Attempt to execute the prepared statement
            if($result && $exec_1   && pg_query($link, 'COMMIT;')){
                $name= $sold = $purchase = $unit = $notes=$grows="";
            } else{
                pg_query($link, "ROLLBACK;") or die("Unable to rollback");
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
                <h2>Add Product</h2>
                <p>Please fill this form.</p>
                <form  method="post">
                    <div class="form-group">
                        <label for='name'>Name</label>
                        <input type="text" name="name" class="form-control  <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $name; ?>" id='name'>
                        <span class="invalid-feedback"><?php echo $name_err ?></span>
                    </div>
                    <div class='form-row'>
                        <div class="input-group form-group <?php echo (!empty($unit_err)) ? 'is_invalid' : ''; ?>">
                            <label for="unit" class="mb-3 mr-5">Unit</label>
                            <select class="form-control select-multiple mb-8 mr-4<?php echo (!empty($unit_err)) ? 'is-invalid' : ''; ?>" id="unit" name="unit_id">
                                <?php
                                    $x=0;
                                    foreach($unit_list as $unit_int){
                                        if ($unit_int['id'] == $unit) {
                                            echo "<option value='".$unit_int['id']."' selected >".$unit_int['name']."(".$unit_int['symbol'].")</option>";
                                        }elseif (!$unit && $x==0) {
                                            echo "<option value='".$unit_int['id']."' selected >".$unit_int['name']."(".$unit_int['symbol'].")</option>";
                                        }
                                        else {
                                            echo "<option value='".$unit_int['id']."'>".$unit_int['name']."(".$unit_int['symbol'].")</option>";
                                        }
                                        $x++;
                                        
                                    }
                                ?>
                            </select>
                            <span class='input-group-btn'> <button type= 'button' class='btn btn-secondary mb-1 ' data-toggle="modal" data-target="#exampleModal" > &#43;</button></span>
                            <span class="invalid-feedback"><?php echo $unit_err; ?></span>
                        </div>
                    </div>
                    <div class='form-row'>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input  <?php echo (!empty($purchase_err)) ? 'is_invalid' : ''; ?>" type="checkbox" value="true" id="purchase" name="purchase"<?php echo ($purchase=='true') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="purchase">
                                    Can Be Purchased
                                </label>
                                <span class="invalid-feedback"><?php echo $purchase_err; ?></span>

                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input <?php echo (!empty($sold_err)) ? 'is-invalid' : ''; ?>" type="checkbox" value="true" id="sold" name="sold" <?php echo ($sold=='true') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="sold">
                                    Can Be Sold
                                </label>
                                <span class="invalid-feedback"><?php echo $sold_err; ?></span>

                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input <?php echo (!empty($grows_err)) ? 'is-invalid' : ''; ?>" type="checkbox" value="true" id="grows" name="grows" <?php echo ($grows=='true') ? 'checked' : ''; ?> >
                                <label class="form-check-label" for="grows">
                                    Grows
                                </label>
                                <span class="invalid-feedback"><?php echo $grows_err; ?></span>

                            </div>   
                            <div class="form-check form-check-inline">
                                <input class="form-check-input <?php echo (!empty($consumable_err) || !empty($consumable_grow_err)) ? 'is-invalid' : ''; ?>" type="checkbox" value="true" id="grows" name="consumable" <?php echo ($consumable=='true') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="consumable">
                                    Consumable
                                </label>
                                <span class="invalid-feedback"><?php echo $consumable_err; echo $consumable_grow_err ?></span>

                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input <?php echo (!empty($produced_err) || !empty($produced_grow_err)) ? 'is-invalid' : ''; ?>" type="checkbox" value="true" id="grows" name="produced" <?php echo ($produced=='true') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="produced">
                                    Produced
                                </label>
                                <span class="invalid-feedback"><?php echo $produced_err;?></span>

                            </div>
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
    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">New Unit</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id='unit-form' name='unit-form' action="" onsubmit="addUnit()">
                <div class="form-group">
                    <label for="unit-name" class="col-form-label">Name:</label>
                    <input type="text" class="form-control" id="unit-name" name='name'>
                    <span class="invalid-feedback" id="unit-name-err"></span>

                </div>
                <div class="form-group">
                    <label for="unit-symbol" class="col-form-label">Symbol:</label>
                    <input class="form-control" id="unit-symbol" name='symbol'>
                    <span class="invalid-feedback" id="unit-symbol-err"></span>

                </div>
                </form>
                <p id='status'></p>
                <p> Please Reload The page After Successfully Submitting The Form.</p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="addUnit()">Submit</button>
            </div>
            </div>
        </div>
    </div>
</html>