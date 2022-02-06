<?php

/*
if (preg_match('(registration.php)', $_SERVER['PHP_SELF'])) {
    Header("Location: error.php?err=01");
    exit();
}*/
// Include config file
require_once(realpath(dirname(__FILE__) . '/..'.'/..') ."/"."config.php");
redirecttologin($_SERVER['PHP_SELF']);
checkpermissions(array(2,3));

 
// Define variables and initialize with empty values
$name = $product = $worker = $notes="";
$name_err = $product_err = $requirements_err = $worker_err =$notes_err="";
$requirements=array();

$worker_query=pg_query($link, "SELECT id, surname, other_names FROM erp_workers WHERE (active='true')");
$worker_list=pg_fetch_all($worker_query);

$product_query=pg_query($link, "SELECT id, name FROM erp_product WHERE (consumable=true AND active=true)");
$product_list=pg_fetch_all($product_query);

$worker_id_list= $product_id_list = array();
if ($worker_list){
    foreach($worker_list as $worker_int){
        array_push($worker_id_list, $worker_int['id']);
}}

if ($product_list){
    foreach($product_list as $product_int){
        array_push($product_id_list, $product_int['id']);
    }
}
// Processing form data when form is submitted


if($_SERVER["REQUEST_METHOD"] == "POST"){

    if(empty(test_input($_POST["name"]))){
        $name_err = "Please enter a valid name.";     
    }else{
        $name = test_input($_POST["name"]);
        pg_prepare($link, 'check',"SELECT name FROM erp_farm_process WHERE (name=$1)");
        $names=pg_execute($link, "check", array($name));

        if (pg_num_rows($names)!=0){
            $name_err.='A process with that name already exists';;
        }
    };





    $absent_worker = $absent_product='';
    $worker = ($_POST["worker_id"]);


    foreach ($worker as $value) {
        if(empty($worker)){
            break;
        }elseif (!in_array($value, $worker_id_list)){
            $absent_worker=true;
        break;
        }
    }
    if ($absent_worker) {
        $worker_err = 'The selected worker does not exists';
    } 
    $product = ($_POST["product_id"]);

    foreach ($product as $value) {
        if(empty($product)){
            break;
        }elseif(!in_array($value, $product_id_list)){
            $absent_product=true;
        break;
        }
    }
    if ($absent_product) {
        $product_err = 'The selected worker does not exists';
    }
    //Validate Notes Field    
    if (isset($_POST["notes"]) && strlen($_POST["notes"])>100){
        $notes_err='Maximum of 100 Characters allowed in this field.';
    }else{
        $notes= test_input($_POST["notes"]);
    }
    //Validate Requirements Field
    if (isset($_POST["requirement_field"]) && count($_POST["requirement_field"])<=0){
        $requirements_err='Please enter a valid requirements field.';
    }else{
        $requirements_input= $_POST["requirement_field"];
        foreach($requirements_input as $requirement){
            if(!empty($requirement)){
                array_push($requirements, test_input($requirement));
            }
        }

    }



    // Check input errors before inserting in database
    if(empty($name_err) && empty($product_err) && empty($requirements_err) && empty($worker_err) && empty($notes_err)){
        // Prepare an insert statement
        pg_query($link, 'BEGIN') or die('Begin transaction failed');
        $sql = "INSERT INTO erp_farm_process (name, requirements, notes, added_by)VALUES ($1, $2, $3, $4) RETURNING id";
        $sql_2 = $sql_3 = '';
        if (!empty($product)){
            $rows="";

            for ($i=2; $i < (count($product)+2); $i++) {
                if ($i!=2){
                    $rows.=',';
                }
                $rows.="($1, $".($i).")";
            }
            $sql_2 = "INSERT INTO  erp_farm_process_product  (farm_process_id, product_id) VALUES".$rows;
        }
        if (!empty($worker)){
            $rows="";

            for ($i=2; $i < count($worker)+2;$i++) { 
                if ($i!=2){
                    $rows.=',';
                }
                $rows.="($1, $".($i).")";
            }
            $sql_3 = "INSERT INTO  erp_farm_process_worker  (farm_process_id, worker_id) VALUES".$rows;
        }


        if(pg_prepare($link,'stmt_insert', $sql) 
            && (empty($product) ||  pg_prepare($link,'stmt_insert_2', $sql_2))
            && (empty($worker) ||  pg_prepare($link,'stmt_insert_3', $sql_3))
            ){
            // Bind variables to the prepared statement as parameters
            // Set parameters
            $requirement_dict='';
            $requirement_dict.="{";
            $counter=0;
            foreach($requirements as $value){
                $counter++;
                $requirement_dict.=$value;
                if ($counter!=count($requirements)){
                    $requirement_dict.=',';
                }
            }
            $requirement_dict.="}";

            $param_sql = array($name, $requirement_dict, $notes, $session_username);

            $result=pg_execute($link, 'stmt_insert',$param_sql);
            
            // Attempt to execute the prepared statement
            if($result && empty($product) && empty($worker) && pg_query($link, 'COMMIT;')){
                $name = $purchase = $worker = $notes= $requirements="";
            }else{
                $id= pg_fetch_row($result);
                $exec_1=$exec_2=true;
                if(!empty($product)){
                        array_push($product, $id[0]+0);
                        $exec_1=pg_execute($link, 'stmt_insert_2', array_reverse($product));
                }
                if(!empty($worker)){
                        array_push($worker, $id[0]+0);
                        $exec_2=pg_execute($link, 'stmt_insert_3', array_reverse($worker));

                    }
                pg_query($link, 'COMMIT') or die('Failed To Commit');

            //}   else{
                if ($result && $exec_1 && $exec_2){
                    $name = $product = $worker = $notes=$requirements="";
                    DisplaySuccessMessage();

                }else{
                    echo "Something went wrong. Please try again later.";
                }
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
        <title>Add Farm Process<?=" | Forms | ".SOFTWARE_NAME ?></title>
        <?php require_once(realpath(dirname(__FILE__) .'/..'.'/..') ."/"."include/header.php")?>
        <style type="text/css">
            body{ font: 14px sans-serif; }
        </style>
        <link rel='stylesheet' href="<?= '/static/bootstrap/css/select2.min.css'?>">
        <script src="<?= '/static/bootstrap/js/select2.min.js'?>"></script>
        <script>
        $(document).ready(function() {
            $('.select_multiple').select2();
        });
        </script>
    </head>
    <body>
        <?php require_once (realpath(dirname(__FILE__) . '/..'.'/..') ."/"."nav.php");?>
        <main>
            <div class="wrapper container">
                <h2>Add Farm Process</h2>
                <p>Please fill this form.</p>
                <form  method="post">
                    <div class="form-group ">
                        <label for='name'>Name *</label>
                        <input type="text" name="name" class="form-control <?= (!empty($name_err)) ? 'is-invalid' : ''; ?>" value="<?= $name; ?>" id='name'>
                        <span class="invalid-feedback"><?= $name_err ?></span>
                    </div>   
                    <div class='form-row'>
                        <div class='col'> 
                            <div class="form-group ">
                                <label for="worker">Worker</label>
                                <select multiple class="form-control select_multiple  <?= (!empty($worker_err)) ? 'is-invalid' : ''; ?>" id="worker" name="worker_id[]">
                                    <?php
                                        $x=0;
                                        foreach($worker_list as $worker_int){?>
                                    <option value='<?$worker_int['id']?>' <?=(is_array($worker) && in_array($worker_int['id'],$worker))?"selected":""?> ><?=$worker_int['surname'].", ".$worker_int['other_names']?></option>

                                         <?php  $x++;                                           
                                        }
                                    ?>
                                </select>
                                <span class="invalid-feedback"><?= $worker_err; ?></span>
                            </div>
                        </div>
                        <div class='col'>
                            <div class="form-group ">
                                <label for="product">Products Used:</label>
                                <select multiple class="form-control select_multiple <?= (!empty($product_err)) ? 'is-invalid' : ''; ?>" id="product" name="product_id[]">
                                    <?php
                                        $x=0;
                                        foreach($product_list as $product_int){?>
                                                <option value='<?= $product_int['id']?>' <?= (is_array($product) && in_array($product_int['id'], $product))?"selected":""?> ><?= $product_int['name']?></option>

                                            <?php $x++;
                                            
                                        }
                                    ?>
                                </select>
                                <span class="invalid-feedback"><?= $product_err; ?></span>
                            </div>
                        </div>
                    </div>
                    <h4>Add Required specification</h4>
                    <p>Use the field below to add the conditions that should be checked during the farm process</p>

                    <div class="card">
                        <div class="card-body">
                            <div id='fields'>
                            </div>
                        </div>
                    </div>
                    <input type='hidden' name='requirement_field_number' id="requirement_field_number" value='0'>
                    <button class='btn btn-secondary'type="button" onclick="change_field('requirement', 'add')">Add</button>
                    
                    <div class="form-group mt-7">
                        <label for="notes">Notes</label>
                        <textarea name="notes" class="form-control" id="notes"><?= $notes?></textarea>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-primary" value="Submit">
                        <input type="reset" class="btn btn-default" value="Reset">
                    </div>
                </form>
                <?=$required_reminder?>

            </div> 
        </main>
        <?php require_once(realpath(dirname(__FILE__) . '/..'. '/..') ."/"."include/footer.php")?>


    </body>
</html>