<?php
// Include config file
require_once(realpath(dirname(__FILE__) .'/..'.'/..') ."/"."config.php");
redirecttologin($_SERVER['PHP_SELF']);
checkpermissions(array(2,3,5));
$session_id=$_SESSION['id'];
$delivery_query=pg_query($link, "SELECT  (array_agg(delivery_product_name.name )) as product_names, sent_record.datetime_sent, delivery_product_name.delivery_sent_record_id AS id,sent_record.status 
FROM (SELECT DISTINCT JP1.name, SP1.delivery_sent_record_id
         FROM erp_product AS JP1, erp_delivery_sent_product_record AS SP1
        WHERE NOT EXISTS
           (SELECT *
              FROM erp_product AS JP2
             WHERE JP2.id = JP1.id
               AND JP2.id
                   NOT IN (SELECT SP2.product_id
                             FROM erp_delivery_sent_product_record AS SP2
                            WHERE SP2.delivery_sent_record_id = SP1.delivery_sent_record_id))
                            ) AS delivery_product_name
JOIN erp_delivery_sent_record sent_record ON sent_record.id=delivery_sent_record_id 
WHERE sent_record.status='Not Received'
                            GROUP BY delivery_product_name.delivery_sent_record_id,sent_record.datetime_sent,sent_record.status;");
$delivery_list=pg_fetch_all($delivery_query);
$sent_id=$date=$time=$notes=$products="";
$sent_id_err=$date_err=$time_err=$products_err="";
$product_excess_list=array();
if (isset($_GET['sent_id']) && test_int($_GET['sent_id'])){
    $sent_id=(in_array($_REQUEST['sent_id'], array_column($delivery_list,'id')))?$_REQUEST['sent_id']:"";
    $sent_id_err=(!in_array($_REQUEST['sent_id'], array_column($delivery_list,'id')))?"The selected delivery does not exist":"";
    $sent_product_sql="SELECT erp_product.name,sent_records.receiving_retail_id AS receiving_id,sent_product_records.quantity AS sent_quantity, measure.symbol,quantity_current.id as quantity_current_id, quantity_current.quantity AS quantity_current_quantity , erp_product.id AS product_id FROM erp_product
        JOIN erp_retail_product_quantity_current quantity_current ON quantity_current.product_id=erp_product.id AND quantity_current.active=TRUE
        JOIN erp_delivery_sent_product_record sent_product_records ON sent_product_records.product_id=erp_product.id 
        JOIN erp_delivery_sent_record sent_records ON sent_records.id=sent_product_records.delivery_sent_record_id 
        JOIN erp_unit_of_measure measure ON measure.id=erp_product.unit_of_measure_id
        
    WHERE sent_records.id=$sent_id;";
    $sent_product_query=pg_query($link,$sent_product_sql);
    $sent_product_list=pg_fetch_all($sent_product_query);
}
$product_quantity_list=array_column($sent_product_list, 'sent_quantity', 'product_id');

if($_SERVER["REQUEST_METHOD"] == "POST"){
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
    if (empty($_POST['product_received'])){
        $products_err="Please enter product values.";

    }elseif (count(array_intersect(array_keys($_POST['product_received']),array_column($sent_product_list, 'product_id'))) != count($_POST['product_received'])) {
        $products_err="One of the selected workers does not exist.";
    } else{
        $products=$_POST['product_received'];
        $products = array_filter($products,'strlen' );
        if (empty($products)){
            $products_err="Please enter valid product values.";
        }
        foreach($products as $key=>$value){
            if($value+0>$product_quantity_list[$key]+0){
                $product_excess_list[$key]='The selected productis in excess. Please count again or notify the sender.';
            }

        }
    }
    $retail_id=array_column($sent_product_list,'receiving_id')[0];
    if (empty($products_err) && empty($date_err) && empty($time_err) && empty($product_excess_list)){
        pg_query($link, "BEGIN;");
        $product_less_list=array();
        $sql_1="INSERT INTO erp_delivery_received_record(receiving_retail_id, datetime_received, added_by, notes) VALUES($1, $2, $3, $4) RETURNING id;";
        $sql_2="INSERT INTO erp_delivery_received_product_record(delivery_received_record_id, product_id, quantity) VALUES ";
        $sql_3=array();
        $sql_4 = "INSERT INTO erp_retail_product_quantity_records(added_by,retail_id,transaction_type,product_quantity_current_id, previous_quantity,quantity) VALUES ";

        for ($i=2,$x=3; $i<(count($products)*2)+2; $i+=2, $x+=3){
            $sql_2.=($i!=2)?",":"";
            $sql_4.=($i!=2)?",":"";
            $sql_2.="($1, $$i, $".($i+1).")";
            
            array_push($sql_3, "UPDATE erp_retail_product_quantity_current SET quantity=quantity+$1 WHERE id=$2;");
            $sql_4.="($1,$2, 'Increase', $".$x.", $".($x+1).", $".($x+2).")";


        }
        $product_quantity_list=array_column($sent_product_list, 'sent_quantity', 'product_id');
        $difference_array=array();
        foreach($products as $key=>$value){
            if($value+0<$product_quantity_list[$key]+0){
                $difference_array[]=$key;
                $difference_array[]=($product_quantity_list[$key]-$value);

            }
        }
        $sql_5="INSERT INTO erp_delivery_lost_product_record(delivery_received_record_id,delivery_sent_record_id,product_id,quantity) VALUES ";
        for ($i=0,$x=3; $i<(count($difference_array)/2); $i++, $x+=3){
            $sql_5.=($i!=0)?",":"";
            $sql_5.="($1, $2,$$x, $".($x+1).")";

        }
        $sql_6="UPDATE erp_delivery_sent_record SET status=$1 WHERE id=$2";
        $pg_prepare_1=pg_prepare($link,'stmt_insert_1',$sql_1);
        $pg_prepare_2=pg_prepare($link, 'stmt_insert_2',$sql_2);
        $pg_prepare_3=pg_prepare($link,'stmt_insert_3', $sql_4);
        $pg_prepare_4=true;
        for ($i=0; $i < count($products); $i++) { 
            if ($pg_prepare_4!=true){break;}
            $pg_prepare_4 = pg_prepare($link , 'stmt_update_'.$i,$sql_3[$i]);
        }

        $pg_prepare_5=(empty($difference_array) || pg_prepare($link, 'stmt_insert_4',$sql_5));
        $pg_prepare_6=pg_prepare($link,'stmt_insert_5', $sql_6);
        if ($pg_prepare_1 && $pg_prepare_2  && $pg_prepare_3  && $pg_prepare_4 && $pg_prepare_5 && $pg_prepare_6){
            $execute_1=pg_execute($link, 'stmt_insert_1', array($retail_id, $date." ".$time, $session_username, $notes));
            $received_record_id=pg_fetch_row($execute_1)[0]+0;
            $param_received_product_record=array($received_record_id);
            $param_received_product_quantity_record=array($session_username,$retail_id);
            $product_quantity_list=array_column($sent_product_list, 'quantity_current_quantity','product_id');
            $product_quantity_id_list=array_column($sent_product_list, 'quantity_current_id','product_id');
            foreach($products as $key=>$value){
                array_push($param_received_product_record,$key,$value);    
                array_push($param_received_product_quantity_record,$product_quantity_id_list[$key],$product_quantity_list[$key],$value);
            }
            $execute_2=pg_execute($link, 'stmt_insert_2',$param_received_product_record);
            $execute_3=pg_execute($link, 'stmt_insert_3',$param_received_product_quantity_record);
            $execute_4=true;
            $i=0;
            foreach($products as $key=>$value){
                if ($execute_4!=true){break;}
                $array=array($value,$product_quantity_id_list[$key]);
                $execute_4 = pg_execute($link , 'stmt_update_'.$i,$array);
                $i++;

            }

            $exexute_5=true;
            $param_received_status="Received, Complete";
            if (!empty($difference_array)){
                $param_received_status="Received, Incomplete";
                array_unshift($difference_array,$sent_id);
                array_unshift($difference_array,$received_record_id);
                $exexute_5=pg_execute($link, 'stmt_insert_4',$difference_array);
            }
            $execute_6=pg_execute($link,'stmt_insert_5',array($param_received_status, $sent_id));
            if ($execute_1 && $execute_2 &&$execute_3 && $execute_4 && $exexute_5 && $execute_6){
                pg_query($link, "COMMIT;") or die ("Unable to commit!");
                DisplaySuccessMessage();
                $sent_id=$date=$time=$notes=$products="";
                $delivery_list=pg_fetch_all($delivery_query);

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
        <title>Add Delivery Received Record | Forms | <?= SOFTWARE_NAME ?></title>
        <?php require_once(realpath(dirname(__FILE__) .'/..'.'/..') ."/"."include/header.php")?>
        <style type="text/css">
            body{ font: 14px sans-serif; }
        </style>
    </head>
    <body>
        <?php require_once (realpath(dirname(__FILE__) . '/..'.'/..') ."/"."nav.php");?>
        <main>
            <div class="wrapper container">
                <h2>Add Delivery Received Record</h2>
                <p>Please fill this form.</p>
                <form  id='form' method="<?= (!empty($sent_id)) ? 'post' : 'get'; ?>">
                    <div class="form-group ">
                            <label for='delivery'>Sent Package*</label>
                            <select class="form-control select_multiple <?= (!empty($sent_id_err)) ? 'is-invalid' : ''; ?>" id="delivery" name="sent_id" onchange="send_get_request('delivery')">
                                <option value='' disabled='disabled' <?= (!empty($sent_id)) ? '' : 'selected'; ?>>Please select a sent package</option>";

                                <?php
                                    $x=0;
                                    foreach($delivery_list as $process_int){?>
                                        <?php $name=preg_replace("/\{(.+)\}/i",'$1',$process_int['product_names'])." Datetime Sent: ".date("Y-m-d \a\t H:i",strtotime($process_int['datetime_sent'])) ?>
                                        <option value='<?=$process_int['id']?>' <?=($process_int['id']==$sent_id)?"selected":"";?> ><?=$name?></option>

                                        
                                    <?php }   ?>
                            </select>
                            <span class="invalid-feedback"><?= $sent_id_err ?></span>
                        </div>
                        <?php if($sent_id){ ?>
                            <div clas='form-row'>
                                <h3 clas='col-sm-12'>Products Received</h3>
                        <?php $x=0;?>
                        <?php foreach($sent_product_list as $product){?>
                        <div class="col-sm-6  col-lg-6">
                            <div class='card'>
                                <div class='card-body'>
                                    <div class="form-group row">
                                        <label for="product<?=$x?>" class="col-sm-2 col-form-label"><?= ucfirst(strtolower( $product['name'])) ?></label>
                                        <div class="col-sm-10 input-group">
                                            <input type="number" class="form-control <?= (!empty($product_excess_list[$product['product_id']]) || !empty($products_err)) ? 'is-invalid' : ''; ?>" id="product<?=$x?>" 
                                                name="product_received[<?=($product['product_id'])?>]" 
                                                value="<?= $products[$product['product_id']]?>" data-quantity="<?= $product['sent_quantity']?>"
                                                data-unit="<?= $product['symbol']?>"
                                                min='0' value="<?= $products_used[$product['product_id']]??""?>" 
                                                step="0.1" data-name="<?=ucfirst(strtolower( $product['name']))?> " >
                                            <div class="input-group-append">
                                                <span class='input-group-text'><?= $product['symbol'] ?>(s)</span>
                                            </div>
                                        </div>
                                        <span class="help-block"><?= $product_excess_list[$product['product_id']]??$products_err; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php $x++; } ?>
                    </div>
                    <input type="hidden" value="<?=$x?>" name="t_n" id="t_n">
                    <div class='form-row'>
                        <div class='col'>
                            <div class="form-group">
                                <label for="date">Date Received:</label>
                                <input type="date" class="form-control" id="date"name="date" value="<?= date("Y-m-d")?>"required>
                                <span class="help-block"><?=$date_err ; ?></span>
                            </div>
                            
                        </div>
                        <div class='col'>
                            <div class="form-group">
                                <label for="time">Time Received:</label>
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
                        <input type="button" onclick="confirm_values()" class="btn btn-primary" value="Submit">
                        <input type="reset" class="btn btn-default" value="Reset">
                    </div>
                        <?php }?>

                </form>
                <?=$required_reminder?>

            </div> 

        </main>
        <?php require_once(realpath(dirname(__FILE__) . '/..'. '/..') ."/"."include/footer.php")?>

        <div class="modal fade" id="confirmvaluesmodal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Confirm Received Products</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>The following values will be submitted</p>
                    <ol id='valueslist'>

                    </ol>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="document.getElementById('form').submit()">Confirm Values</button>
                </div>
            </div>
        </div>
<? var_dump($sent_product_list)?>
    </body>

</html> 