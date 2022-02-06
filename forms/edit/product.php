<?php

// Include config file
require_once(realpath(dirname(__FILE__) . '/..'.'/..') ."/"."config.php");
redirecttologin($_SERVER['PHP_SELF']);
checkpermissions(array(2));


// Define variables and initialize with empty values
$name= $sold = $purchase = $unit = $notes=$grows=$consumable=$product_id=$grows_prev=$grows_now=$produced="";
$name_err = $purchase_err = $sold_err = $unit_err =$notes_err=$grows_err=$consumable_err=$consumable_grow_err=$product_id_err=$produced_err="";

$unit_querry=pg_query($link, "SELECT id, name, symbol FROM erp_unit_of_measure WHERE (active='true')");

$unit_list=pg_fetch_all($unit_querry);

$product_query=pg_query($link, "SELECT id, name FROM erp_product WHERE (active='true')");

$product_list=pg_fetch_all($product_query);
// Processing form data when form is submitted
$product_fields='';

if (isset($_GET['product_id']) && test_int($_GET['product_id'])){
    $product_id=test_input($_GET['product_id']);


    $product_fields_query=pg_query($link,"SELECT * FROM erp_product WHERE (erp_product.id=$product_id);");
    $product_fields=pg_fetch_all($product_fields_query);
    if(empty($product_fields)){
        $product_id_err='The Submitted Product Does Not Exist';
        $product_id="";
    }else{
        $name=$product_fields[0]['name'];
        $sold=($product_fields[0]['sale']=='t')?'true':'false';
        $purchase=($product_fields[0]['purchase']=='t')?'true':'false';
        $grows=($product_fields[0]['grows']=='t')?'true':'false';
        $grows_prev=($product_fields[0]['grows']=='t')?'true':'false';
        $consumable=($product_fields[0]['consumable']=='t')?'true':'false';
        $produced=($product_fields[0]['produced']=='t')?'true':'false';
        $notes=$product_fields[0]['notes'];
        $unit=$product_fields[0]['unit_of_measure_id'];
    }



}


if($_SERVER["REQUEST_METHOD"] == "POST"){

    if(empty(test_input($_POST["name"]))){
        $name_err = "Please enter a valid name.";     
    }else{
        $name = test_input($_POST["name"]);
        pg_prepare($link, 'check_name',"SELECT name FROM erp_product WHERE (name=$1 AND id <> $2)");
        $names=pg_execute($link, 'check_name', array($name, $product_id));
        /*$names_p=strtolower($name);
        $names_r=pg_query($link, "SELECT * FROM erp_product_quantity_records WHERE (lower(name)='$names_p')");
        */
        if (pg_num_rows($names)!=0){
            $name_err.='A product with that name already exists';;
        }/*elseif(pg_num_rows($names_r)!=0){
            $name_err.='<br /> A product with that name already exists. Different cases do not differentiate a product name.';
        }*/
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
    
    }else{
        $grows=(test_input($_POST["grows"]) =='true')?'true':'false';
        //$grows_now=(test_input($_POST["grows"]) =='true')?'true':'false';
;

    };
    if(isset($_POST["consumable"]) && test_input($_POST["consumable"])!='true'){
        $consumable_err = "Please enter valid value in grows field.";     
    
    }elseif(isset($_POST["consumable"]) && test_input($_POST["consumable"]) =='true'){
        $consumable = 'true';
    }else{
        $consumable = 'false';

    };
    if($consumable==$grows && $consumable=='true'){
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
        $sql = "UPDATE  erp_product SET name=$1, sale=$2, purchase=$3, unit_of_measure_id=$4, notes=$5, grows=$6, consumable=$7, produced=$8 WHERE id= $9 ;";

        if(pg_prepare($link,'stmt_insert', $sql)){
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
            $param_product_id=$product_id;
            $array=array($param_name, $param_sold, $param_purchase, $param_unit, $param_notes, $param_grows,$param_consumable,$param_produced, $param_product_id);
            $result=pg_execute($link, 'stmt_insert',$array);

            // Attempt to execute the prepared statement
            if($result && pg_query($link, 'COMMIT;')){
                $name= $sold = $purchase = $unit = $notes=$grows=$consumable=$product_id="";
                DisplaySuccessMessage();
            } else{
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
        <title>Edit Existing Product<?=" | Forms | ".SOFTWARE_NAME ?></title>
        <?php require_once(realpath(dirname(__FILE__) .'/..'.'/..') ."/"."include/header.php")?>
        <style type="text/css">
            body{ font: 14px sans-serif; }
        </style>
        <!--<script>
        $(document).ready(function() {
            $('.select_multiple').select2();
        });</script>-->
    </head>
    <body>
        <?php require_once (realpath(dirname(__FILE__) . '/..'.'/..') ."/"."nav.php");?>
        <main>
            <div class="wrapper container">
                <h2>Edit Existing Product </h2>
                <p>Please fill this form.</p>
                <form  id='form' method="<?= (!empty($product_id)) ? 'post' : 'get'; ?>">
                    <div class="form-group ">
                        <label for='product'>Product:</label>
                        <select class="form-control select_multiple <?= (!empty($name_err)) ? 'is-invalid' : ''; ?>" id="product" name="product_id" onchange="send_get_request('product')">
                            <option value='' disabled='disabled' <?= (!empty($product_id)) ? '' : 'selected'; ?>>Please select a product</option>";

                            <?php
                                $x=0;
                                foreach($product_list as $process_int){
                                    if (($process_int['id']==$product_id)) {
                                        echo "<option value='".$process_int['id']."' selected >".$process_int['name']."</option>";
                                    }
                                    else {
                                        echo "<option value='".$process_int['id']."'>".$process_int['name']."</option>";
                                    }
                                    $x++;
                                    
                                }
                            ?>
                        </select>
                        <span class="invalid-feedback"><?= $product_id_err ?></span>
                    </div>
                    <?php if($product_id){?>   

                    <div class="form-group">
                        <label for='name'>Name *</label>
                        <input type="text" name="name" class="form-control  <?= (!empty($name_err)) ? 'is-invalid' : ''; ?>" value="<?= $name; ?>" id='name'>
                        <span class="invalid-feedback"><?= $name_err ?></span>
                    </div>
                    <div class='form-row'>
                        <div class="input-group form-group <?= (!empty($unit_err)) ? 'is_invalid' : ''; ?>">
                            <label for="unit" class="mb-3 mr-5">Unit</label>
                            <select class="form-control select_multiple mb-8 mr-4<?= (!empty($unit_err)) ? 'is-invalid' : ''; ?>" id="unit" name="unit_id">
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
                            <span class="invalid-feedback"><?= $unit_err; ?></span>
                        </div>

                    </div>
                    <div class='form-row'>
                            <div class='col-sm-12'><h3>Check all that apply</h3></div>

                            <div class="form-check form-check-inline mb-4">
                                <input class="form-check-input  <?= (!empty($purchase_err)) ? 'is_invalid' : ''; ?>" type="checkbox" value="true" id="purchase" name="purchase"<?= ($purchase=='true') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="purchase">
                                    Can Be Purchased
                                </label>
                                <span class="invalid-feedback"><?= $purchase_err; ?></span>

                            </div>
                            <div class="form-check form-check-inline mb-4">
                                <input class="form-check-input <?= (!empty($sold_err)) ? 'is-invalid' : ''; ?>" type="checkbox" value="true" id="sold" name="sold" <?= ($sold=='true') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="sold">
                                    Can Be Sold
                                </label>
                                <span class="invalid-feedback"><?= $sold_err; ?></span>

                            </div>
                            <div class="form-check form-check-inline mb-4">
                                <input class="form-check-input <?= (!empty($grows_err)) ? 'is-invalid' : ''; ?>" type="checkbox" value="true" id="grows" name="grows" <?= ($grows=='true') ? 'checked' : ''; ?> disabled>
                                <label class="form-check-label" for="grows">
                                    Grows
                                </label>
                                <span class="invalid-feedback"><?= $grows_err; ?></span>

                            </div>   
                            <div class="form-check form-check-inline mb-4">
                                <input class="form-check-input <?= (!empty($consumable_err) || !empty($consumable_grow_err)) ? 'is-invalid' : ''; ?>" type="checkbox" value="true" id="grows" name="consumable" <?= ($consumable=='true') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="consumable">
                                    Consumable
                                </label>
                                <span class="invalid-feedback"><?= $consumable_err; echo $consumable_grow_err ?></span>

                            </div>
                            <div class="form-check form-check-inline mb-4">
                                <input class="form-check-input <?= (!empty($produced_err) || !empty($produced_grow_err)) ? 'is-invalid' : ''; ?>" type="checkbox" value="true" id="grows" name="produced" <?= ($produced=='true') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="produced">
                                    Produced
                                </label>
                                <span class="invalid-feedback"><?= $produced_err;?></span>

                            </div>
                    </div>
                    <div class="form-group ">
                        <label for='notes'>Notes</label>
                        <textarea name="notes" class="form-control <?= (!empty($notes_err)) ? 'is-invalid' : ''; ?>"  id='notes'><?= $notes; ?></textarea>
                        <span class="invalid-feedback"><?= $notes_err ?></span>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-primary" value="Submit">
                        <input type="reset" class="btn btn-default" value="Reset">
                    </div>
                    <?php }?>
                </form>
                <?=$required_reminder?>

            </div> 
        </main>
        <?php require_once(realpath(dirname(__FILE__) . '/..'. '/..') ."/"."include/footer.php")?>


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