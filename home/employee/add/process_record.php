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
$process_id= $products_used = $requirements = $workers = $notes= $plant_animal = $date = $time = $amount="";
$process_id_err = $products_err = $requirements_err = $workers_err =$notes_err= $plant_animal_err =$date_err = $time_err = $amount_err="";

$process_requirement_list='';

$worker_id_list= $product_id_list = $plant_animal_id_list= array();

// Get the active processes
$process_query=pg_query($link, "SELECT erp_farm_process.id, erp_farm_process.name,array_to_json(erp_farm_process.requirements) FROM erp_farm_process WHERE (erp_farm_process.active='true');");
$process_list=pg_fetch_all($process_query);



if (isset($_GET['process_id']) && test_int($_GET['process_id'])){
    $process_id=test_input($_GET['process_id']);
    $worker_query=pg_query($link, "SELECT erp_workers.id,erp_workers.surname, erp_workers.other_names  FROM erp_workers
        JOIN  erp_farm_process_worker ON erp_workers.id=erp_farm_process_worker.worker_id 
        WHERE (erp_workers.active='true' AND erp_farm_process_worker.farm_process_id=$process_id);");
    $worker_list=pg_fetch_all($worker_query);

    //$product_query=pg_query($link, "SELECT erp_product.id,erp_product.name  FROM erp_product  JOIN  erp_farm_process_product ON erp_product.id=erp_farm_process_product.product_id WHERE erp_farm_process_product.farm_process_id= $process_id;");
    $product_query=pg_query($link,"SELECT erp_product.id,erp_product.name, erp_product.unit_of_measure_id, erp_unit_of_measure.symbol, erp_product_quantity_current.quantity FROM erp_product  
        LEFT JOIN  erp_farm_process_product ON erp_product.id=erp_farm_process_product.product_id  
        LEFT JOIN erp_unit_of_measure ON erp_unit_of_measure.id=erp_product.unit_of_measure_id
        LEFT JOIN erp_product_quantity_current ON lower(erp_product.name)= lower(erp_product_quantity_current.name) 
        WHERE (erp_farm_process_product.farm_process_id=$process_id);");
    $product_list=pg_fetch_all($product_query);


    $plant_animal_query=pg_query($link, "SELECT erp_product_quantity_current.name,erp_product_quantity_current.quantity,erp_grow_product.id FROM erp_grow_product 
        JOIN erp_product_quantity_current ON LOWER(erp_product_quantity_current.name)=LOWER(erp_grow_product.name)
        WHERE erp_product_quantity_current.quantity >0 ");
    $plant_animal_list=pg_fetch_all($plant_animal_query);

    if ($worker_list){
        foreach($worker_list as $worker_int){
            array_push($worker_id_list, $worker_int['id']);
    }}

    if ($product_list){
        foreach($product_list as $product_int){
            array_push($product_id_list, $product_int['name']);
        }
    }
    if ($plant_animal_list){
        foreach($plant_animal_list as $product_int){
            array_push($plant_animal_id_list, $product_int['id']);
        }
    }
    foreach($process_list as $process){
        if ($process['id']==$process_id){
            $process_requirement_list=json_decode($process['array_to_json']);
    }}

}
// Processing form data when form is submitted


if($_SERVER["REQUEST_METHOD"] == "POST"){

    $absent_worker = $absent_product_used= $absent_plant_animal='';
    $workers = ($_POST["worker_id"]);


    foreach ($workers as $value) {
        if(empty($workers)){
            break;
        }elseif (!in_array($value, $worker_id_list)){
            $absent_worker=true;
        break;
        }
        if(empty($value) && $value !='0'){
            $workers=\array_diff($workers, [$value]);
        }
    }
    if ($absent_worker) {
        $workers_err = 'The selected worker does not exist.';
    } 
    $products_used = ($_POST["product_used"]);
    $product_name_list=array_column($product_list, 'name');
    for ($i=0; $i < count($product_name_list); $i++) { 
        $product_name_list[$i]=strtolower($product_name_list[$i]);
    }
    $product_quantities=array_map_keys( 'strtolower',array_column($product_list, 'quantity', 'name'));

    foreach ($products_used as $value) {
        if(empty($products_used)){
            break;
        //}elseif(!in_array($value, $product_id_list)){
        }elseif(!in_array(strtolower(array_search($value, $products_used)),$product_name_list )){
            $absent_product_used=true;
        break;
        }
        if($product_quantities[strtolower(array_search($value, $products_used))]<$value ){
            $products_err.="One of the submitted quantities is wrong";
        break;

        }
        if(empty($value) && $value !='0'){
            $products_used=\array_diff($products_used, [$value]);
        }
    
    }
    if ($absent_product_used) {
        $products_err = 'The selected product does not exist.';
    } 
    
    $plant_animal = ($_POST["plant_animal"]);

    foreach ($plant_animal as $value) {
        if(empty($plant_animal)){
            break;
        }elseif(!in_array($value, $plant_animal_id_list)){
            $absent_plant_animal=true;
        break;
        }
        if(empty($value) && $value !='0'){
            $plant_animal=\array_diff($plant_animal, [$value]);
        }
    }
    if ($absent_plant_animal) {
        $plant_animal_err = 'The selected plant/animal does not exist.';
    } 

    $notes= test_input($_POST["notes"]);
    //Validate Requirements Field
    if (isset($_POST["requirements"]) && count($_POST["requirements"])<=0){
        $requirements_err='Please enter a valid requirements field value.';
    }else{
        $requirements= ($_POST["requirements"]);
        
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
        $sql = "INSERT INTO erp_farm_process_record(date, time, farm_process_id, requirements, notes) VALUES($1, $2, $3, $4,$5) RETURNING id";
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


            for ($i=2,$x=1; $i < ((count($products_used)*2)+2); $i+=2,$x+=3) {
                if ($i!=2){
                    $rows_1.=',';
                    $rows_2.=',';
                }
                $rows_1.="($1, $".$i.", $".($i+1).")";
                $rows_2.="($".$x.", $".($x+1).", $".($x+2).")";
                //$rows_3.="UPDATE  erp_product_quantity_current SET quantity = quantity-$".($y)." WHERE lower(name) = $".($y+1).';';
                array_push($sql_5, "UPDATE  erp_product_quantity_current 
                    SET quantity = quantity - $1 WHERE lower(erp_product_quantity_current.name) = $2;");
            }
            $rows_1.=';';
            $rows_2.=';';
            $sql_3 = "INSERT INTO erp_farm_process_product_record  (farm_process_record_id, product_id, quantity) VALUES".$rows_1;
            $sql_4 = "INSERT INTO erp_product_quantity_records(name, quantity, transaction_type) VALUES ".$rows_2;
            //$sql_5 = $rows_3;

            for ($i=0; $i < count($products_used); $i++) { 
                if ($pg_prepare_4!=true){
                break;
                }
                $pg_prepare_4 = pg_prepare($link , 'stmt_update_'.$i,$sql_5[$i]);
            }
        }
        
        $pg_prepare_1=pg_prepare($link,'stmt_insert', $sql) && pg_prepare($link, 'stmt_insert_5', "INSERT INTO erp_cashbook(folio, amount, transaction_type) VALUES ($1, $2, 'Cr')");
        $pg_prepare_2=pg_prepare($link,'stmt_insert_2', $sql_2); 
        $pg_prepare_3=pg_prepare($link,'stmt_insert_3', $sql_3) && pg_prepare($link,'stmt_insert_4', $sql_4); 
        $pg_prepare_5=pg_prepare($link, 'stmt_insert_6', $sql_6);

        if($pg_prepare_1 && $pg_prepare_2 && $pg_prepare_3 && $pg_prepare_4 && $pg_prepare_5){
            // Bind variables to the prepared statement as parameters
            $process_array=array_column($process_list, 'name', 'id');
            ;
            $param_sql = array($date, $time, $process_id, (json_encode($requirements)!="null")?json_encode($requirements):"{}", $notes);
            $result=pg_execute($link, 'stmt_insert',$param_sql);
            
            // Attempt to execute the prepared statement
            //if($result && empty($products_used) && empty($workers)&& empty($plant_animal) && pg_execute($link, 'stmt_insert_5', array($process_array[$process_id],$amount)) && pg_query($link, 'COMMIT;')){
                //$process_id= $products_used = $requirements = $workers = $notes= $plant_animal = $date = $time ="";
            //}else{
                $id= pg_fetch_row($result);
                $exec_1=$exec_2 =$exec_3 = $exec_4 =$exec_5 =$exec_6=true;
                $param_product_process_record= $param_product_quantity = $param_product_current =array();
                

                    $exec_5=pg_execute($link, 'stmt_insert_5', array($process_array[$process_id],$amount));
                    if(!empty($workers)){
                        array_unshift($workers, $id[0]+0);
                        $exec_1=pg_execute($link, 'stmt_insert_2', $workers);
                    }    
                    if(!empty($plant_animal)){
                        array_unshift($plant_animal, $id[0]+0);
                        ;
                        sleep(5);
                        $exec_6=pg_execute($link, 'stmt_insert_6', $plant_animal);
                    }
                    if ( !empty($products_used)){
                        array_push($param_product_process_record, $id[0]+0);
                        $i=0;
                        foreach($product_list as $value){
                            if( $products_used[strtolower($value["name"])] ){
                                $value_from_form=$products_used[strtolower($value['name'])];
                                array_push($param_product_process_record, $value['id']);
                                array_push($param_product_process_record, $value_from_form);
                                array_push($param_product_quantity, $value['name']);
                                array_push($param_product_quantity,$value_from_form , 'Decrease');
                                //array_push($param_product_current,  $products_used[strtolower($value['name'])], $value['name']);
                                if ($exec_4!=true){
                                continue;
                                }else{
                                    $array=array($products_used[strtolower($value['name'])]+0,strtolower($value['name'] ));
                                $exec_4 = pg_execute($link , 'stmt_update_'.$i,$array);
                                }
                                $i++;
                            }


                        }
                        $exec_2=pg_execute($link, 'stmt_insert_3',  $param_product_process_record);
                        $exec_3=pg_execute($link, 'stmt_insert_4',  $param_product_quantity);

                    }

            //}   else{
                if ($result && $exec_1 && $exec_2 && $exec_3 && $exec_4 && $exec_5 && $exec_6 && pg_query($link, 'COMMIT;')){
                    $process_id= $products_used = $requirements = $workers = $notes= $plant_animal = $date = $time ="";
                }else{
                    pg_query($link, 'ROLLBACK;') or die('Failed To Commit And Rollback');
                    echo "Something went wrong. Please try again later.";
                }
            //};
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
        <link rel='stylesheet' href="<?php echo '/static/bootstrap/css/select2.min.css'?>">
        <script src="<?php echo '/static/bootstrap/js/select2.min.js'?>"></script>
        <script>
        $(document).ready(function() {
            $('.select_multiple').select2();
        });
        </script>
    </head>
    <body>
        <?php require_once(realpath(dirname(__FILE__) . '/..'. '/..') ."/"."employee/nav.php")?>
        <main>
            <div class="wrapper container">
                <h2>Add Farm Process Record</h2>
                <p>Please fill this form.</p>
                <form  id='form' method="<?php echo (!empty($process_id)) ? 'post' : 'get'; ?>">
                    <div class="form-group ">
                        <label for='process'>Process</label>
                        <select class="form-control select_multiple <?php echo (!empty($process_id_err)) ? 'is-invalid' : ''; ?>" id="process" name="process_id" onchange="send_get_request('process')">
                            <option value='' disabled='disabled' <?php echo (!empty($process_id)) ? '' : 'selected'; ?>>Please select a process</option>";

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
                        <span class="invalid-feedback"><?php echo $process_id_err ?></span>
                    </div>
                    <?php if($process_id){?>   
                            <div class='form-row'>
                                <div class='col'>
                                    <div class="form-group  <?php echo (!empty($workers_err)) ? 'has-error' : ''; ?>">
                                        <h3>Workers:</h3>
                                        <select multiple class="form-control select_multiple" id="worker" name="worker_id[]">
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
                                        <span class="help-block"><?php echo $workers_err; ?></span>
                                    </div>
                                </div>
                                <div class='col'>
                                    <div class="form-group  <?php echo (!empty($plant_animal_err)) ? 'has-error' : ''; ?>">
                                        <h3>Plants/Animals:</h3>
                                        <select multiple class="form-control select_multiple" id="plant_animal" name="plant_animal[]">
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
                                        <span class="help-block"><?php echo $plant_animal_err; ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class='row'>
                                <div class='col'>
                                    <div class="form-group ">
                                        <h3>Products Used:</h3>
                                        <div class="card">
                                            <div class="card-body">
                                                <?php $x=0; foreach($product_list as $product){?>

                                                <div class="form-group row">
                                                    <label for="product" class="col-sm-2 col-form-label"><?php echo ucfirst(strtolower( $product['name'])) ?></label>
                                                    <div class="col-sm-10 input-group">
                                                        <input type="number" class="form-control" id="product"  
                                                            name="product_used[<?php echo strtolower($product['name'])?>]" 
                                                            min='0' max="<?= $product['quantity']?>" value="<?= $products_used[$product['name']]??""?>"
                                                            oninvalid="this.setCustomValidity(`The entered value is more than the total quantity in store (${this.max})`)"
                                                                        oninput="setCustomValidity('')">
                                                        <div class="input-group-append">
                                                            <span class='input-group-text'><?php echo $product['symbol'] ?>(s)</span>
                                                        </div>
                                                    </div>
                                                    <span class="help-block"><?php echo $products_err ; ?></span>


                                                </div>
                                                <?php } $x++ ?>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                                <div class='col'>
                                    <div class="form-group ">
                                        <h3>Requirements:</h3>
                                        <div class="card">
                                            <div class="card-body">

                                                <?php                                                 
                                                foreach($process_requirement_list as $requirement){?>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" value="true" id="<?php echo $requirement ?>" name="requirements[<?php echo $requirement ?>]">
                                                    <label class="form-check-label" for="<?php echo $requirement ?>">
                                                    <?php echo $requirement ?>
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
                                        <input type="date" class="form-control" id="date"name="date" value="<?php echo date("Y-m-d")?>"required>
                                    </div>
                                    
                                </div>
                                <div class='col'>
                                    <div class="form-group">
                                        <label for="time">Time</label>
                                        <input type="time" class="form-control" id="time" name='time' value="<?php echo date("H:i")?>" required>
                                    </div>
                                </div>
                                <div class='col'>
                                    <div class="form-group">
                                        <label for="amount">Cost</label>
                                        <input type="number" class="form-control" id="amount" name='amount' value="<?php echo (!empty($amount)) ? $amount : '0'; ?>" >
                                        <span class="help-block"><?php echo $amount_err ; ?></span>

                                    </div>
                                </div>
                            </div>
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea name="notes" class="form-control" id="notes"><?php echo $notes?></textarea>
                        <span class="help-block"><?php echo $notes_err; ?></span>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-primary" value="Submit">
                        <input type="reset" class="btn btn-default" value="Reset">
                    </div>
                                    <?php }?>


                </form>
            </div> 
        </main>
        <?php require_once(realpath(dirname(__FILE__) . '/..'. '/..'. '/..') ."/"."include/footer.php")?>

    </body>
</html> 