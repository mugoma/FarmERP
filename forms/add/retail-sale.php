<?php

/*
if (preg_match('(registration.php)', $_SERVER['PHP_SELF'])) {
    Header("Location: error.php?err=01");
    exit();
}*/
// Include config file
require_once(realpath(dirname(__FILE__) . '/..'.'/..') ."/"."config.php");
redirecttologin($_SERVER['PHP_SELF']);
checkpermissions(array(5));
$retail_sql="SELECT * FROM erp_retail_unit
    WHERE active='true' AND id IN (SELECT retail_id FROM erp_retail_user WHERE user_id=$_SESSION[id])";
$retail_query=pg_query($link, $retail_sql);
$retail_list=pg_fetch_all($retail_query);

$retail_id=$products=$date=$time=$bank_id=$total_overall_amount=$notes=$trans_code='';
$retail_id_err=$products_err=$date_err=$time_err=$bank_id_err=$total_overall_amount_err=$trans_code_err="";
$amount_err=$quantity_err=$cost_per_unit_err=$param_product_record_array=$param_sale_array=array();

if (isset($_GET['retail_id']) && test_int($_GET['retail_id'])){
    do{
        $retail_id=(in_array($_REQUEST['retail_id'], array_column($retail_list,'id')))?$_REQUEST['retail_id']:"";
        $retail_id_err=(!in_array($_REQUEST['retail_id'], array_column($retail_list,'id')))?"The selected retail unit does not exist.":"";
        if (!empty($retail_id_err)){break;};
        $product_sql="SELECT erp_product.name, erp_product.id AS product_id, erp_retail_product_quantity_current.id AS quantity_id,erp_retail_product_quantity_current.quantity, measure.symbol FROM erp_retail_product
            JOIN erp_product ON erp_product.id=erp_retail_product.product_id AND erp_product.active='true'
            JOIN erp_retail_product_quantity_current ON erp_product.id=erp_retail_product_quantity_current.product_id AND erp_retail_product_quantity_current.grows='false' AND erp_retail_product_quantity_current.active='true'
            JOIN erp_unit_of_measure  measure ON erp_product.unit_of_measure_id=measure.id
        WHERE erp_retail_product.retail_id=$retail_id";
        $product_query=pg_query($link,$product_sql);
        $product_list=pg_fetch_all($product_query);
        $bank_sql="SELECT * FROM erp_bank_account WHERE active='true'";
        $bank_query=pg_query($link,$bank_sql);
        $bank_list=pg_fetch_all($bank_query);
    }while(0);

}

// Define variables and initialize with empty values
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if (count(array_intersect(array_keys($_POST['product']),array_column($product_list, 'quantity_id'))) != count($_POST['product'])) {
        $products_err="One of the selected products does not exist.";
    } else{
        $product_quanitity_array=array_column($product_list,'quantity','quantity_id');
        $product_id_array=array_column($product_list,'product_id','quantity_id');
        $products=$_POST['product'];
        $param_sale_array=array($retail_id,$session_username);
        $param_product_record_array=array($session_username,$retail_id);


        foreach($products as $key=>$product){
            $amount=test_input($product["amount"]);
            $quantity=test_input($product["quantity"]);
            $cost_per_unit=test_input($product["cost_per_unit"]);


            if(
                (empty(test_input($product["amount"])) && test_input($product["amount"])!='0') 
                || test_input($product["amount"]) < 0
                || !test_int($product['amount']+0)
            ){
                $amount_err[$key] = "Please enter a valid amount";
            }
            if(
                (empty(test_input($product["quantity"])) && test_input($product["quantity"])!='0') 
                || test_input($product['quantity']) < 0
                || !test_int($product['quantity']+0)
            ){
                $quantity_err[$key] = "Please enter a valid quantity"; 
            }elseif($quantity+0>$product_quanitity_array[$key]+0){
                $quantity_err[$key] = "The quantity submitted is more than quantity in store."; 
            }
            if(
                (empty(test_input($product["cost_per_unit"]))&& test_input($product["cost_per_unit"])!='0')
                || test_input($product['cost_per_unit']) < 0
                || !test_int($product['cost_per_unit']+0)
                ){
                $cost_per_unit_err[$key] = "Please enter a valid cost per unit"; 
            }
            if(!empty($amount) && !empty($quantity) && !empty($cost_per_unit) && ($cost_per_unit* $quantity)!=$amount){
                $amount_err[$key]="Total cost does not add up";
            }elseif(empty($amount) && empty($quantity) && empty($amount_err[$key])&& empty($quantity_err[$key])){
                unset($products[$key]);

            }else{
                $total_overall_amount+=$amount;
                array_push($param_sale_array,$product_id_array[$key],$amount,$cost_per_unit,$product['notes'],$quantity);
                array_push($param_product_record_array,$key,$product_quanitity_array[$key],$quantity);
            }
        }
        if (empty($products)){
           $products_err="All of the submitted products have empty quantities and amount. Please fill in the form correctly"; 
        }
    }
    if(empty($_POST['bank_id'])){
        $bank_id_err="Please select a valid bank account";
    }else{
        $bank_id=$_POST['bank_id'];
    }
    if($total_overall_amount!=$_POST['total_overall_amount']){
        $total_overall_amount_err="The total amount deposited does not match the sum of the individual amounts.";

    }else{
        $total_overall_amount=$_POST['total_overall_amount'];
    }
    if (empty($_POST['date']) || !test_date($_POST['date'])){
        $date_err='Please enter a valid date.';
    }else{
        $date=test_input($_POST['date']);
    }

    if (empty($_POST['time']) || !test_time($_POST['time'])){
        $time_err='Please enter a valid time.';
    }else{
        $time=test_input($_POST['time']);
    }
    if (empty($_POST['transaction_code'])){
        $trans_code_err='Please enter a valid transaction code.';
    }else{
        $trans_code=test_input($_POST['transaction_code']);
    }
    $notes = test_input($_POST["notes"]);
    
    // Check input errors before inserting in database
    if(
        empty($retail_id_err) 
        && empty($$products_err) 
        && empty($date_err)
        && empty($time_err)
        && empty($bank_id_err)
        && empty($total_overall_amount_err)
        && empty($amount_err)
        && empty($quantity_err)
        && empty($cost_per_unit_err)
        && empty($trans_code_err)
        ){
        pg_query($link, 'BEGIN;');
        $sql_1 = "INSERT INTO erp_retail_sales (retail_id,added_by,product_id,amount,cost_per_unit,notes,quantity) VALUES ";
        $sql_2 = "INSERT INTO erp_retail_product_quantity_records(added_by,retail_id,transaction_type,product_quantity_current_id, previous_quantity,quantity) VALUES ";
        $sql_3 = array();
        $sql_4="INSERT INTO erp_cashbook (folio, amount, transaction_type, added_by) VALUES($1, $2, 'Dr',$3);";
        $sql_5="INSERT INTO erp_bank_account_transactions(bank_account_id,retail_id,total_amount,datetime_deposited,added_by,notes,transaction_code) VALUES ($1,$2,$3,$4,$5, $6,$7)";
        for ($x=3,$count=0,$y=3; $count<count($products);  $x+=3,$count++,$y+=5){
            $sql_1.=(($count!=0)?",":"")."($1,$2,$$y,$".($y+1).",$".($y+2).",$".($y+3).",$".($y+4).")";
            $sql_2.=(($count!=0)?",":"")."($1, $2,'Decrease', $".$x.", $".($x+1).", $".($x+2).")";  
      
            array_push($sql_3, "UPDATE erp_retail_product_quantity_current SET quantity=quantity-$1 WHERE id=$2;");
        }
        $prepare_1=pg_prepare($link,'stmt_insert_1',$sql_1);
        $prepare_2=pg_prepare($link,'stmt_insert_2',$sql_2);
        $prepare_4=pg_prepare($link,'stmt_insert_4',$sql_4);
        $prepare_5=pg_prepare($link,'stmt_insert_5',$sql_5);
        $prepare_3=true;
        for ($i=0; $i < count($products); $i++) { 
            if ($prepare_3!=true){break;}
            $prepare_3 = pg_prepare($link , 'stmt_update_'.$i,$sql_3[$i]);
        }
        if($prepare_1 && $prepare_2 && $prepare_3 && $prepare_4 && $prepare_5){
            $execute_1=pg_execute($link,'stmt_insert_1',$param_sale_array);
            $execute_2=pg_execute($link,'stmt_insert_2',$param_product_record_array);
            $retail_name=array_column($retail_list,'name','id')[$retail_id]??$retail_id;
            $execute_4=pg_execute($link,'stmt_insert_4',array("Retail Sales($retail_name)",$total_overall_amount, $session_username));
            $execute_5=pg_execute($link,'stmt_insert_5',array($bank_id,$retail_id,$total_overall_amount,$date." ".$time,$session_username,$notes,$trans_code));
            $i=0;
            foreach($products as $key=>$value){
                if ($execute_4!=true){break;}
                $array=array($value['quantity'], $key);
                $execute_3 = pg_execute($link , 'stmt_update_'.$i,$array);
                $i++;
            }
            // Attempt to execute the prepared statement
            if(
                $execute_1 
                && $execute_2 
                && $execute_3 
                && $execute_4 
                && $execute_5 
                && pg_query($link, 'COMMIT;')){
                    $retail_query=pg_query($link, $retail_sql);
                    $retail_list=pg_fetch_all($retail_query);
                    
                    $retail_id=$products=$date=$time=$bank_id=$total_overall_amount=$notes=$trans_code="";

                DisplaySuccessMessage();
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
        <title>Add Retail Sale Record<?=" | Forms | ".SOFTWARE_NAME ?></title>
        <?php require_once(realpath(dirname(__FILE__) .'/..'.'/..') ."/"."include/header.php")?>
        <style type="text/css">
            body{ font: 14px sans-serif; }
        </style>
    </head>
    <body>
        <?php require_once (realpath(dirname(__FILE__) . '/..'.'/..') ."/"."nav.php");?>
        <main>
            <div class="wrapper container">
                <h2>Add Retail Sale Record</h2>
                <p>Please fill this form.</p>
                <form  id='form' method="<?= (!empty($retail_id)) ? 'post' : 'get'; ?>">
                    <div class="form-group ">
                        <label for='retail'>Retail Shop *</label>
                        <select class="form-control select_multiple <?=(!empty($retail_id_err)) ? 'is-invalid' : ''; ?>" id="retail" name="retail_id" onchange="send_get_request('retail')">
                            <option value='' disabled='disabled' <?=(!empty($retail_id)) ? '' : 'selected'; ?>>Please select a retail unit</option>";

                            <?php
                                $x=0;
                                foreach($retail_list as $process_int){
                                    if (($process_int['id']==$retail_id)) {
                                        echo "<option value='".$process_int['id']."' selected >".$process_int['name']."</option>";
                                    }
                                    else {
                                        echo "<option value='".$process_int['id']."'>".$process_int['name']."</option>";
                                    }
                                    $x++;
                                    
                                }
                            ?>
                        </select>
                        <span class="invalid-feedback"><?= $retail_id_err ?></span>
                    </div>

                    <?php if($retail_id){?>
                    <h3>Products Sold</h3>

                    <?php
                    $x=0;
                    foreach($product_list as $product){?> 

                        <div class='card'>
                            <div class='card-header'>
                            <h4><?=$product['name'] ?></h4>
                            <h5 class="text-danger"><?= $products_err ?></h5>
                            </div>
                            <div class="card-body">
                                <div class="form-row">
                                    <div class='col col-sm-12 col-lg-4'>
                                        <div class="form-group ">
                                            <label for='cost_per_unit<?= $x?>'>Cost Per Unit *</label>
                                            <input type="number" name="product[<?= $product['quantity_id']?>][cost_per_unit]" class="form-control <?= (!empty($cost_per_unit_err[$product['quantity_id']])) ? 'is-invalid' : ''; ?>" value="<?= $products[$product['quantity_id']]['cost_per_unit']; ?>" id='cost_per_unit<?= $x?>' onchange="get_total_price(<?= $x?>)" step="0.01">
                                            <span class="invalid-feedback"><?= $cost_per_unit_err[$product['quantity_id']] ?></span>
                                        </div>
                                    </div>

                                    <!--<div class='col col-sm-12 col-lg-4'>
                                        <div class="form-group ">
                                            <label for='quantity<?= $x?>'>Quantity *</label>
                                            <input type="number" name="product[<?= $product['quantity_id']?>][quantity]" class="form-control <?= (!empty($quantity_err[$product['quantity_id']])) ? 'is-invalid' : ''; ?>" value="<?= $products[$product['quantity_id']]['quantity']; ?>" id='quantity<?= $x?>' onchange="get_total_price(<?= $x?>)" step="0.1">
                                            <span class="invalid-feedback"><?= $quantity_err[$product['quantity_id']] ?></span>
                                        </div>
                                    </div>-->
                                    <div class='col col-sm-12 col-lg-4'>
                                            <label for='quantity<?= $x?>'>Quantity *</label>
                                            <div class="input-group mb-3">
                                                <input type="number" name="product[<?= $product['quantity_id']?>][quantity]" class="form-control <?= (!empty($quantity_err[$product['quantity_id']])) ? 'is-invalid' : ''; ?>" value="<?= $products[$product['quantity_id']]['quantity']; ?>" id='quantity<?= $x?>' onchange="get_total_price(<?= $x?>)" step="0.1"
                                                aria-describedby="unit<?= $x ?>">
                                                
                                                <div class="input-group-append">
                                                    <span class='input-group-text' id="unit<?= $x ?>"><?= $product['symbol'] ?>(s)</span>
                                                </div>
                                                <span class="invalid-feedback"> <?= $quantity_err[$product['quantity_id']] ?> </span>

                                            </div>
                                    </div>

                                    <div class='col col-sm-12 col-lg-4'>
                                        <div class="form-group ">
                                            <label for='amount<?= $x?>'>Total Cost</label>
                                            <input type="number" name="product[<?= $product['quantity_id']?>][amount]" class="form-control <?= (!empty($amount_err[$product['quantity_id']])) ? 'is-invalid' : ''; ?>" value="<?=$products[$product['quantity_id']]['amount'] ?>" id='amount<?= $x?>' readonly>
                                            <span class="invalid-feedback"><?= $amount_err[$product['quantity_id']] ?></span>
                                        </div>
                                    </div>
                                    <div class="col-sm-12">
                                        <div class="form-group ">
                                            <label for='notes<?= $x?>'>Notes</label>
                                            <textarea name="product[<?= $product['quantity_id']?>][notes]" class="form-control"  id='notes<?= $x?>'><?=$products[$product['quantity_id']]['notes'] ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php $x++; }?>
                    <h3>Bank Details</h3>
                    <div class="form-group ">
                        <label for='bank_ac'>Bank Account *</label>
                        <select class="form-control select_multiple <?=(!empty($bank_id_err)) ? 'is-invalid' : ''; ?>" id="bank_ac" name="bank_id" >
                            <option value='' disabled='disabled' <?= (!empty($bank_id)) ? '' : 'selected'; ?>>Please select a bank account</option>";

                            <?php
                                $x=0;
                                foreach($bank_list as $process_int){?>
                            <option value='<?=$process_int['id']?>' <?=($process_int['id']==$bank_id)?"selected":"";?> ><?="$process_int[name] | A\C no: ".html_entity_decode($process_int['account_number']) ." | Institution: $process_int[institution]"?></option>
                                    
                                <?php  }   ?>
                        </select>
                        <span class="invalid-feedback"><?=$bank_id_err ?></span>
                    </div>   
                    <div class="form-group ">
                        <label for='overal_amt'>Amount deposited at the bank *</label>
                        <input type="number" name="total_overall_amount" class="form-control <?= (!empty($total_overall_amount_err)) ? 'is-invalid' : ''; ?>" value="<?= $total_overall_amount ?>" id='overall_amt'>
                        <span class="invalid-feedback"><?= $total_overall_amount_err ?></span>
                    </div>  
                    <div class='form-row'>
                        <div class='col'>
                            <div class="form-group">
                                <label for="date">Date deposited*:</label>
                                <input type="date" class="form-control" id="date"name="date" value="<?= date("Y-m-d")?>"required>
                                <span class="help-block"><?=$date_err ; ?></span>
                            </div>
                            
                        </div>
                        <div class='col'>
                            <div class="form-group">
                                <label for="time">Time Deposited*:</label>
                                <input type="time" class="form-control" id="time" name='time' value="<?= date("H:i")?>" required>
                                <span class="help-block"><?=$time_err ; ?></span>
                            </div>
                        </div>
                        <div class='col'>
                            <div class="form-group">
                                <label for="transaction_code">Transaction Code*:</label>
                                <input type="text" class="form-control" id="transaction_code" name='transaction_code' value="<?= $trans_code ?>" required>
                            </div>
                        </div>
                    </div>                    
                        

                    <div class="form-group ">
                        <label for='overall_notes'>Notes</label>
                        <textarea name="notes" class="form-control <?= (!empty($notes_err)) ? 'is-invalid' : ''; ?>"  id='overall_notes'><?= $notes; ?></textarea>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-primary" value="Submit">
                        <input type="reset" class="btn btn-default" value="Reset">
                    </div>
                    <?php }?>
                    <?=$required_reminder?>

                </form>
            </div> 
            <?= var_dump($quantity_err[1])?>

        </main>
        <?php require_once(realpath(dirname(__FILE__) . '/..'. '/..') ."/"."include/footer.php")?>

    </body>
</html>