<?php

require_once(realpath(dirname(__FILE__) . '/..'.'/..') ."/"."config.php");
redirecttologin($_SERVER['PHP_SELF']);
checkpermissions(array(3,4));


// Define variables and initialize with empty values
$process_id= $products_used = $requirements = $workers = $notes= $plant_animal = $date = $time = $amount="";
$process_id_err = $products_err = $requirements_err = $workers_err =$notes_err= $plant_animal_err =$date_err = $time_err = $amount_err="";

$process_requirement_list='';


// Get the active processes
$process_query=pg_query($link, "SELECT erp_farm_process.id, erp_farm_process.name,array_to_json(erp_farm_process.requirements) FROM erp_farm_process WHERE (erp_farm_process.active='true');");
$process_list=pg_fetch_all($process_query);



if (isset($_GET['process_id']) && test_int($_GET['process_id'])){
    $process_id=test_input($_GET['process_id']);
    $worker_query=pg_query($link, "SELECT erp_workers.id,erp_workers.surname, erp_workers.other_names  FROM erp_workers
        JOIN  erp_farm_process_worker ON erp_workers.id=erp_farm_process_worker.worker_id 
        WHERE (erp_workers.active='true' AND erp_farm_process_worker.farm_process_id=$process_id);");
    $worker_list=pg_fetch_all($worker_query);
    $product_query=pg_query($link,"SELECT erp_product_quantity_current.id,erp_product.name, erp_product.unit_of_measure_id, erp_unit_of_measure.symbol, erp_product_quantity_current.quantity FROM erp_farm_process_product  
        JOIN erp_product ON erp_product.id=erp_farm_process_product.product_id  
        JOIN erp_unit_of_measure ON erp_unit_of_measure.id=erp_product.unit_of_measure_id
        JOIN erp_product_quantity_current ON erp_product.id= erp_product_quantity_current.product_id 
        WHERE (erp_farm_process_product.farm_process_id=$process_id);");
    $product_list=pg_fetch_all($product_query);
    $plant_animal_query=pg_query($link, "SELECT erp_grow_product.name,erp_product_quantity_current.quantity,erp_grow_product.id FROM erp_grow_product 
        JOIN erp_product_quantity_current ON erp_product_quantity_current.id=erp_grow_product.product_quantity_current_id
        WHERE erp_product_quantity_current.quantity > 0 ");
    $plant_animal_list=pg_fetch_all($plant_animal_query);

    $process_requirement_list=json_decode((array_column($process_list, 'array_to_json','id'))[$process_id]);
}




if($_SERVER["REQUEST_METHOD"] == "POST"){


    if (isset($_POST['worker_id']) && count(array_intersect($_POST['worker_id'],array_column($worker_list, 'id'))) != count($_POST['worker_id'])) {
        $workers_err="One of the selected workers does not exist.";
    }elseif(isset($_POST['worker_id']) && !is_array($_POST['worker_id'])){
        $products_err="Please submit a valid list of workers.";

    } else{
        $workers=(isset($_POST['worker_id']))? $_POST['worker_id']:"";
        $workers=(isset($_POST['worker_id']))? array_filter( $workers, 'strlen' ):"";

    }
 
    if (isset($_POST['product_used']) && count(array_intersect(array_keys($_POST['product_used']),array_column($product_list, 'id'))) != count($_POST['product_used'])) {
        $products_err="One of the selected products does not exist.";
    }elseif(isset($_POST['product_used']) && !is_array($_POST['product_used'])){
        $products_err="Please submit a valid list of products used.";
    } else{
        $products_used=(isset($_POST['product_used']))? $_POST['product_used']:"";
        $products_used=(isset($_POST['product_used']))? array_filter( $products_used, 'strlen' ):"";

    }

    
    //$plant_animal = ($_POST["plant_animal"]);
    if (isset($_POST['plant_animal']) && count(array_intersect($_POST['plant_animal'],array_column($plant_animal_list, 'id'))) != count($_POST['plant_animal'])) {
        $plant_animal_err="One of the selected plant/animal does not exist.";
    }elseif(isset($_POST['plant_animal']) && !is_array($_POST['plant_animal'])){
        $products_err="Please submit a valid list of plants/animals.";

    }else{
        $plant_animal=(isset($_POST['plant_animal']))? $_POST['plant_animal']:"";
        $plant_animal=(isset($_POST['plant_animal']))? array_filter( $plant_animal, 'strlen' ):"";

    }

    $notes= test_input($_POST["notes"]);
    //Validate Requirements Field
    if (isset($_POST["requirements"]) && count($_POST["requirements"])<=0){
        $requirements_err='Please enter a valid requirements field value.';
    }else{
        $requirements= (isset($_POST["requirements"]))?$_POST["requirements"]:"";
        
    }
    if(empty(test_input($_POST['date'])) || !test_date($_POST['date'])){
        $date_err='Please enter a valid date value';

    }else{
        $date=$_POST['date'];
    }
    if(empty(test_input($_POST['time'])) || !test_time($_POST['time'])){
        $time_err='Please enter a valid time value';

    }else{
        $time=$_POST['time'];
    }

    if(
        isset($_POST['amount']) 
        && empty(test_input($_POST['amount'])) 
        && test_input($_POST['amount'])!='0'
        ){
            $amount_err='Please enter an amount';
        }elseif(!is_int($_POST['amount']+0)){
            $amount_err='The amount entered is not valid';
        }else{
            $amount=($_POST['amount']+0);
        }


    // Check input errors before inserting in database
    if(empty($workers_err) && empty($plant_animal_err) && empty($products_err) && empty($requirements_err) && empty($notes_err) && empty($date_err) && empty($time_err) && empty($amount_err)){
        // Prepare an insert statement
        pg_query($link, 'BEGIN') or die('Begin transaction failed');
        $sql = "INSERT INTO erp_farm_process_record(datetime_processed, farm_process_id, requirements, notes, added_by) VALUES($1, $2, $3, $4,$5, $6) RETURNING id";
        $sql_2 = $sql_3 = $sql_4 =  $sql_6='';
        $sql_5 =array();
        $pg_prepare_1=$pg_prepare_2=$pg_prepare_3 = $pg_prepare_4=$pg_prepare_5=true;
        if (!empty($workers)){
            $rows="";

            for ($i=2; $i < count($workers)+2;$i++) { 
                if ($i!=2){
                    $rows.=',';
                }
                $rows.="($1, $".($i).")";
            }
            $rows.=';';
            $sql_2 = "INSERT INTO erp_farm_process_worker_record(farm_process_record_id, worker_id) VALUES".$rows;
        }
        if (!empty($plant_animal)){
            $rows="";

            for ($i=2; $i < count($plant_animal)+2;$i++) { 
                if ($i!=2){
                    $rows.=',';
                }
                $rows.="($1, $".($i).")";
            }
            $rows.=';';
            $sql_6 = "INSERT INTO erp_farm_process_grow_product_record(farm_process_record_id, grow_product_id) VALUES".$rows;
        }
        if (!empty($products_used)){            
            $rows_1 = $rows_2 = $rows_3 = "";

            for ($i=2,$x=2; $i < ((count($products_used)*2)+2); $i+=2,$x+=3) {
                if ($i!=2){
                    $rows_1.=',';
                    $rows_2.=',';
                }
                $rows_1.="($1, $".$i.", $".($i+1).")";
                $rows_2.="($1, 'Decrease', $".$x.", $".($x+1).", $".($x+2).")";
                array_push($sql_5, "UPDATE  erp_product_quantity_current 
                    SET quantity = quantity - $1 WHERE erp_product_quantity_current.id = $2;");
            }
            $rows_1.=';';
            $rows_2.=';';
            $sql_3 = "INSERT INTO erp_farm_process_product_record  (farm_process_record_id, product_id, quantity) VALUES".$rows_1;
            $sql_4 = "INSERT INTO erp_product_quantity_records(added_by,transaction_type,product_quantity_current_id, previous_quantity,quantity) VALUES ".$rows_2;
            //$sql_5 = $rows_3;

            for ($i=0; $i < count($products_used); $i++) { 
                if ($pg_prepare_4!=true){
                break;
                }
                $pg_prepare_4 = pg_prepare($link , 'stmt_update_'.$i,$sql_5[$i]);
            }
        }
        
        $pg_prepare_1=pg_prepare($link,'stmt_insert', $sql) && (empty($amount) || pg_prepare($link, 'stmt_insert_5', "INSERT INTO erp_cashbook(folio, amount, transaction_type, added_by) VALUES ($1, $2, 'Cr',$3)"));
        $pg_prepare_2=pg_prepare($link,'stmt_insert_2', $sql_2); 
        $pg_prepare_3=pg_prepare($link,'stmt_insert_3', $sql_3) && pg_prepare($link,'stmt_insert_4', $sql_4); 
        $pg_prepare_5=pg_prepare($link, 'stmt_insert_6', $sql_6);

        if($pg_prepare_1 && $pg_prepare_2 && $pg_prepare_3 && $pg_prepare_4 && $pg_prepare_5){
            // Bind variables to the prepared statement as parameters
            $process_array=array_column($process_list, 'name', 'id');
            $param_sql = array($date." ".$time, $process_id, (json_encode($requirements)!="null")?json_encode($requirements):"{}", $notes, $session_username);
            $result=pg_execute($link, 'stmt_insert',$param_sql);

            $id= pg_fetch_row($result);
            $exec_1=$exec_2 =$exec_3 = $exec_4 =$exec_5 =$exec_6=true;
            $param_product_process_record= $param_product_quantity = $param_product_current =array();
            

            $exec_5=(empty($amount)|| pg_execute($link, 'stmt_insert_5', array("Farm Process($process_array[$process_id])",$amount, $session_username)));
            if(!empty($workers)){
                array_unshift($workers, $id[0]+0);
                $exec_1=pg_execute($link, 'stmt_insert_2', $workers);
            }    
            if(!empty($plant_animal)){
                array_unshift($plant_animal, $id[0]+0);
                $exec_6=pg_execute($link, 'stmt_insert_6', $plant_animal);
            }
            if (!empty($products_used)){
                array_push($param_product_process_record, $id[0]+0);
                $i=0;
                $param_product_quantity[]=$session_username;
                foreach($product_list as $key=>$value){
                    if( isset($products_used[$value["id"]])){
                        $quantity_from_form=$products_used[$value['id']];
                        array_push($param_product_process_record, $value['id'],$quantity_from_form);
                        array_push($param_product_quantity,$value['id'],$value['quantity'],$quantity_from_form);
                        if ($exec_4!=true){
                        continue;
                        }else{
                            $array=array($quantity_from_form,$value['id']);
                        $exec_4 = pg_execute($link , 'stmt_update_'.$i,$array);
                        }
                        $i++;
                    }
                }
                $exec_2=pg_execute($link, 'stmt_insert_3',  $param_product_process_record);
                $exec_3=pg_execute($link, 'stmt_insert_4',  $param_product_quantity);

            }

                if ($result && $exec_1 && $exec_2 && $exec_3 && $exec_4 && $exec_5 && $exec_6 && pg_query($link, 'COMMIT;')){
                    $process_id= $products_used = $requirements = $workers = $notes= $plant_animal = $date = $time ="";
                    DisplaySuccessMessage();
                }else{
                    pg_query($link, 'ROLLBACK;') or die('Failed To Commit And Rollback');
                    echo "Something went wrong. Please try again later.";
                }
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
        <title>Add Process Record<?=" | Forms | ".SOFTWARE_NAME ?></title>
        <?php require_once(realpath(dirname(__FILE__) .'/..'.'/..') ."/"."include/header.php")?>
        <style type="text/css">
            body{ font: 14px sans-serif; }
        </style>
    </head>
    <body>
        <?php require_once (realpath(dirname(__FILE__) . '/..'.'/..') ."/"."nav.php");?>
        <main>
            <div class="wrapper container">
                <h2>Add Farm Process Record</h2>
                <p>Please fill this form.</p>
                <form  id='form' method="<?= (!empty($process_id)) ? 'post' : 'get'; ?>">
                    <div class="form-group ">
                        <label for='process'>Process *</label>
                        <select class="form-control select_multiple <?= (!empty($process_id_err)) ? 'is-invalid' : ''; ?>" id="process" name="process_id" onchange="send_get_request('process')">
                            <option value='' disabled='disabled' <?= (!empty($process_id)) ? '' : 'selected'; ?>>Please select a process</option>";

                            <?php
                                $x=0;
                                foreach($process_list as $process_int){
                                    if (($process_int['id']==$process_id)) {
                                        echo "<option value='".$process_int['id']."' selected >".$process_int['name']."</option>";
                                    }
                                    else {
                                        echo "<option value='".$process_int['id']."'>".$process_int['name']."</option>";
                                    }
                                    $x++;
                                    
                                }
                            ?>
                        </select>
                        <span class="invalid-feedback"><?= $process_id_err ?></span>
                    </div>
                    <?php if($process_id){?>   
                            <div class='form-row'>
                                <div class='col-sm-12 col-md-6'>
                                    <div class="form-group  ">
                                        <h3>Workers:</h3>
                                        <select multiple class="form-control select_multiple <?= (!empty($workers_err)) ? 'is-invalid' : ''; ?>" id="worker" name="worker_id[]">
                                            <?php
                                                $x=0;
                                                foreach($worker_list as $worker_int){
                                                    if (!empty($workers)  && in_array($worker_int['id'],$workers)) {
                                                        echo "<option value='".$worker_int['id']."' selected >".$worker_int['surname'].", ".$worker_int['other_names']."</option>";
                                                    }
                                                    else {
                                                        echo "<option value='".$worker_int['id']."'>".$worker_int['surname'].", ".$worker_int['other_names']."</option>";
                                                    }
                                                    $x++;
                                                    
                                                }
                                            ?>
                                        </select>
                                        <span class="invalid-feedback"><?= $workers_err; ?></span>
                                    </div>
                                </div>
                                <div class='col-sm-12 col-md-6'>
                                    <div class="form-group ">
                                        <h3>Plants/Animals:</h3>
                                        <select multiple class="form-control select_multiple <?= (!empty($plant_animal_err)) ? 'is-invalid' : ''; ?>" id="plant_animal" name="plant_animal[]">
                                            <?php
                                                $x=0;
                                                foreach($plant_animal_list as $list_int){
                                                    if (!empty($plant_animal)  && in_array($list_int['id'],$plant_animal)) {
                                                        echo "<option value='".$list_int['id']."' selected >".$list_int['name']."</option>";
                                                    }
                                                    else {
                                                        echo "<option value='".$list_int['id']."'>".$list_int['name']."</option>";
                                                    }
                                                    $x++;
                                                    
                                                }
                                            ?>
                                        </select>
                                        <span class="invalid-feedback"><?= $plant_animal_err; ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class='form-row'>
                                <div class='col-sm-12 col-md-6'>
                                    <div class="form-group ">
                                        <h3>Products Used:</h3>
                                        <div class="card">
                                            <div class="card-body">
                                                <?php $x=0;  foreach($product_list as $product){?>

                                                <div class="">
                                                    <label for="product<?= $x ?>"><?= ucfirst(strtolower( $product['name'])) ?></label>
                                                    <div class="input-group mb-3">
                                                        <input type="number" class="form-control" id="product<?= $x ?>" 
                                                            name="product_used[<?=($product['id'])?>]" 
                                                            min='0' max="<?= $product['quantity']?>" value="<?= $products_used[$product['id']]??""?>" 
                                                            step="0.1" oninvalid="this.setCustomValidity(`The entered value is more than the total quantity in store (${this.max})`)"
                                                            oninput="setCustomValidity('')"
                                                            aria-describedby="unit<?= $x ?>">
                                                        <div class="input-group-append">
                                                            <span class='input-group-text' id="unit<?= $x ?>"><?= $product['symbol'] ?>(s)</span>
                                                        </div>
                                                    </div>
                                                    <span class="help-block"><?= $products_err ; ?></span>


                                                </div>
                                                <?php $x++;}?>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                                <div class='col-sm-12 col-md-6'>
                                    <div class="form-group ">
                                        <h3>Requirements:</h3>
                                        <div class="card">
                                            <div class="card-body">

                                                <?php                                                 
                                                foreach($process_requirement_list as $requirement){?>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" value="true" id="<?= $requirement ?>" name="requirements[<?= $requirement ?>]">
                                                    <label class="form-check-label" for="<?= $requirement ?>">
                                                    <?= $requirement ?>
                                                    </label>
                                                </div>
                                                <?php }?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class='form-row'>
                                <div class='col'>
                                    <div class="form-group">
                                        <label for="date">Date:</label>
                                        <input type="date" class="form-control" id="date"name="date" value="<?= date("Y-m-d")?>"required>
                                    </div>
                                    
                                </div>
                                <div class='col'>
                                    <div class="form-group">
                                        <label for="time">Time</label>
                                        <input type="time" class="form-control" id="time" name='time' value="<?= date("H:i")?>" required>
                                    </div>
                                </div>
                                <div class='col'>
                                    <div class="form-group">
                                        <label for="amount">Addidtional Cost</label>
                                        <input type="number" class="form-control" id="amount" name='amount' value="<?= (!empty($amount)) ? $amount : '0'; ?>" >
                                        <span class="help-block"><?= $amount_err ; ?></span>

                                    </div>
                                </div>
                            </div>
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea name="notes" class="form-control" id="notes"><?= $notes?></textarea>
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