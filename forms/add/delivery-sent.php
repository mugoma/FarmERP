<?php
require_once(realpath(dirname(__FILE__) . '/..'.'/..') ."/"."config.php");
redirecttologin($_SERVER['PHP_SELF']);
checkpermissions(array(3,4, 2));

$retail_query=pg_query($link, "SELECT * FROM erp_retail_unit
    WHERE active='true'");
$retail_list=pg_fetch_all($retail_query);

$retail_id=$products=$date=$time=$notes='';
$retail_id_err=$products_err=$date_err=$time_err="";

if (isset($_GET['retail_id']) && test_int($_GET['retail_id'])){
    $retail_id=$_GET['retail_id'];
    $product_sql="SELECT erp_product.name, erp_product.id AS product_id, erp_product_quantity_current.id AS quantity_id,erp_product_quantity_current.quantity, measure.symbol FROM erp_retail_product
            JOIN erp_product ON erp_product.id=erp_retail_product.product_id AND erp_product.active='true'
            JOIN erp_product_quantity_current ON erp_product.id=erp_product_quantity_current.product_id
            JOIN erp_unit_of_measure  measure ON erp_product.unit_of_measure_id=measure.id
            RIGHT JOIN erp_retail_product_quantity_current  retail_current ON retail_current.active='true' AND erp_product.id=retail_current.product_id
        WHERE erp_retail_product.retail_id=$retail_id";
    $product_query=pg_query($link,$product_sql);
    $product_list=pg_fetch_all($product_query);

}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if (empty($_POST['product_used'])){
        $products_err="Please enter product values.";

    }elseif (count(array_intersect(array_keys($_POST['product_used']),array_column($product_list, 'product_id'))) != count($_POST['product_used'])) {
        $products_err="One of the selected products does not exist.";
    } else{
        $products=$_POST['product_used'];
        $products = array_filter($products );
        if (empty($products)){
            $products_err="Please enter valid product values.";
        }
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
    $notes=test_input($_POST['notes']);

    if(empty($retail_id_err)&& empty($products_err) && empty($date_err) && empty($time_err)){
        pg_query($link, "BEGIN;");
        $sql_1="INSERT INTO erp_delivery_sent_record(receiving_retail_id, datetime_sent, added_by, notes) VALUES($1, $2, $3, $4) RETURNING id;";
        $sql_2="INSERT INTO erp_delivery_sent_product_record(delivery_sent_record_id, product_id, quantity) VALUES ";
        $sql_3=array();
        $sql_4 = "INSERT INTO erp_product_quantity_records(added_by,transaction_type,product_quantity_current_id, previous_quantity,quantity) VALUES ";

        for ($i=2,$x=2; $i<(count($products)*2)+2; $i+=2, $x+=3){
            $sql_2.=($i!=2)?",":"";
            $sql_4.=($i!=2)?",":"";
            $sql_2.="($1, $$i, $".($i+1).")";
            
            array_push($sql_3, "UPDATE erp_product_quantity_current SET quantity=quantity-$1 WHERE id=$2;");
            $sql_4.="($1, 'Decrease', $".$x.", $".($x+1).", $".($x+2).")";


        }
        $pg_prepare_1=pg_prepare($link,'stmt_insert_1', $sql_1) && pg_prepare($link, 'stmt_insert_2', $sql_2);
        $pg_prepare_2=true;
        for ($i=0; $i < count($products); $i++) { 
            if ($pg_prepare_2!=true){break;}
            $pg_prepare_2 = pg_prepare($link , 'stmt_update_'.$i,$sql_3[$i]);
        }
        $pg_prepare_3=pg_prepare($link, 'stmt_insert_3', $sql_4);
        if($pg_prepare_1 && $pg_prepare_2 && $pg_prepare_3){
            $execute_1=pg_execute($link, 'stmt_insert_1', array($retail_id, $date." ".$time, $session_username, $notes));
            
            $sent_record_id=pg_fetch_row($execute_1)[0]+0;
            $param_delivery_product_array=array($sent_record_id);
            $product_quantity_list=array_column($product_list, 'quantity','product_id');
            $product_quantity_id_list=array_column($product_list, 'quantity_id','product_id');
            $param_quantity_record_array=array($session_username);

            foreach($products as $key=>$value){
                array_push($param_delivery_product_array,$key,$value);    
                array_push($param_quantity_record_array,$product_quantity_id_list[$key],$product_quantity_list[$key],$value);
            }
            $execute_2=pg_execute($link, 'stmt_insert_2',$param_delivery_product_array );
            $execute_3=pg_execute($link, 'stmt_insert_3',$param_quantity_record_array);
            $execute_4=true;
            $i=0;
            foreach($products as $key=>$value){
                if ($execute_4!=true){break;}
                $array=array($value,$product_quantity_id_list[$key]);
                $execute_4 = pg_execute($link , 'stmt_update_'.$i,$array);
                $i++;

            }
            if ($execute_1 && $execute_2 && $execute_3 && $execute_4){
                pg_query($link, 'COMMIT;') or die('Unable to commit!');
                $retail_list=pg_fetch_all($retail_query);
                $retail_id=$products=$date=$time='';
                DisplaySuccessMessage();
            }else{
                pg_query($link, 'ROLLBACK;') or die ('Unable to rollback!');
                echo "Something went wrong. Please try again later.";
            }

        }
    }

}

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Add Delivery Send Record | Forms | <?= SOFTWARE_NAME ?></title>
        <?php require_once(realpath(dirname(__FILE__) .'/..'.'/..') ."/"."include/header.php")?>
        <style type="text/css">
            body{ font: 14px sans-serif; }
        </style>
    </head>
    <body>
        <?php require_once (realpath(dirname(__FILE__) . '/..'.'/..') ."/"."nav.php");?>
        <main>
            <div class="wrapper container">
                <h2>Add Delivery Send Record</h2>
                <p>Please fill this form.</p>
                <p>Ensure that you have logged in with your account and that you can add the record.</p>
                <form  id='form' method="<?= (!empty($retail_id)) ? 'post' : 'get'; ?>">
                <div class="form-group ">
                        <label for='process'>Receiving Retail *</label>
                        <select class="form-control select_multiple <?= (!empty($retail_id_err)) ? 'is-invalid' : ''; ?>" id="process" name="retail_id" onchange="send_get_request('process')">
                            <option value='' disabled='disabled' <?= (!empty($retail_id)) ? '' : 'selected'; ?>>Please select a retail unit</option>";

                            <?php
                                $x=0;
                                foreach($retail_list as $process_int){?>
                            <option value='<?=$process_int['id']?>' <?=(($process_int['id']==$retail_id))?"selected":""?>><?=$process_int['name']?></option>

                                   <?php $x++; } ?>
                        </select>
                        <span class="invalid-feedback"><?= $retail_id_err ?></span>
                    </div>
                        <?php if($retail_id){?>
                    <div clas='form-row'>
                        <?php $x=0;?>
                        <?php foreach($product_list as $product){?>
                        <div class='card col-sm-12 col-md-6 col-lg-12'>
                            <div class='card-body'>
                                <div class="form-group row">
                                    <label for="product<?=$x?>" class="col-sm-2 col-form-label"><?= ucfirst(strtolower( $product['name'])) ?></label>
                                    <div class="col-sm-10 input-group">
                                        <input type="number" class="form-control" id="product<?=$x?>" 
                                            name="product_used[<?=($product['product_id'])?>]" 
                                            value="<?= $products[$product['product_id']]?>"
                                            min='0' max="<?= $product['quantity']?>" value="<?= $products_used[$product['product_id']]??""?>" 
                                            step="0.1" oninvalid="this.setCustomValidity(`The entered value is more than the total quantity in store (${this.max})`)"
                                            oninput="setCustomValidity('')">
                                        <div class="input-group-append">
                                            <span class='input-group-text'><?= $product['symbol'] ?>(s)</span>
                                        </div>
                                    </div>
                                    <span class="help-block"><?= $products_err ; ?></span>
                                </div>
                            </div>
                        </div>
                        <?php $x++; } ?>
                    </div>
                    <div class='form-row'>
                        <div class='col'>
                            <div class="form-group">
                                <label for="date">Date Sent:</label>
                                <input type="date" class="form-control" id="date"name="date" value="<?= date("Y-m-d")?>"required>
                                <span class="help-block"><?=$date_err ; ?></span>
                            </div>
                            
                        </div>
                        <div class='col'>
                            <div class="form-group">
                                <label for="time">Time Sent:</label>
                                <input type="time" class="form-control" id="time" name='time' value="<?= date("H:i")?>" required>
                                <span class="help-block"><?=$time_err ; ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for='notes'>Notes</label>
                        <textarea name="notes" class="form-control"  id='notes'><?= $notes; ?></textarea>
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
</html>