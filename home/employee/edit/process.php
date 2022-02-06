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
$name= $requirements = $product = $worker = $notes=$process_id="";
$name_err = $product_err = $requirements_err = $worker_err =$notes_err="";

$worker_query=pg_query($link, "SELECT id, surname, other_names FROM erp_workers WHERE (active='true')");
$worker_list=pg_fetch_all($worker_query);

$product_query=pg_query($link, "SELECT id, name FROM erp_product WHERE (consumable=true AND active=true)");
$product_list=pg_fetch_all($product_query);

$process_sql="SELECT erp_farm_process.id, erp_farm_process.name,array_to_json(erp_farm_process.requirements) FROM erp_farm_process WHERE (erp_farm_process.active='true');";
$process_query=pg_query($link, $process_sql);
$process_list=pg_fetch_all($process_query);

$process_fields='';

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

if (isset($_GET['process_id']) && test_int($_GET['process_id'])){
    $process_id=test_input($_GET['process_id']);

    
    pg_prepare($link,'get_fields', "SELECT erp_farm_process.notes, erp_farm_process.name,array_to_json(erp_farm_process.requirements) FROM erp_farm_process WHERE (erp_farm_process.id=$1);");
    $process_details=pg_execute($link, 'get_fields', array($process_id));

    if($process_fields=pg_fetch_assoc($process_details)){
        //$name= $requirements = $product = $worker = $notes="";
        $name=$process_fields['name'];
        $worker=pg_fetch_assoc(pg_query($link, "SELECT  erp_farm_process_worker.worker_id FROM erp_farm_process_worker WHERE farm_process_id=$process_id"));
        $product=pg_fetch_assoc(pg_query($link, "SELECT product_id FROM erp_farm_process_product WHERE farm_process_id=$process_id"));
        $notes=$process_fields['notes'];
        $requirements=json_decode($process_fields['array_to_json']);

    }

    //pg_prepare($link, "SELECT erp_product.id,erp_product.name  FROM erp_product  JOIN  erp_farm_process_product ON erp_product.id=erp_farm_process_product.product_id WHERE erp_farm_process_product.farm_process_id= $process_id;");


}
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $process_id=test_input($_REQUEST['process_id']);


    if(empty(test_input($_POST["name"]))){
        $name_err = "Please enter a valid name.";     
    }else{
        $name = test_input($_POST["name"]);
        pg_prepare($link, 'check',"SELECT name FROM erp_farm_process WHERE (name=$1 and id<> $2)");
        $names=pg_execute($link, "check", array($name, $process_id));
        //$names_p=strtolower($name);
        //$names_r=pg_query($link, "SELECT * FROM erp_product_quantity_records WHERE (lower(name)='$names_p')");

        if (pg_num_rows($names)!=0){
            $name_err.='A process with that name already exists';;
        //}elseif(pg_num_rows($names_r)!=0){
         //   $name_err.='<br /> A product with that name already exists. Different cases do not differentiate a product name.';
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
    $requirements=array();
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
        echo'ar you for real';
        pg_query($link, 'BEGIN') or die('Begin transaction failed');
        $sql = "UPDATE erp_farm_process SET name=$1,  requirements=$2, notes=$3 WHERE id=$4;";
        $sql_2 = $sql_3 = $sql_4='';
        if (!empty($product)){
            $rows="";

            for ($i=2; $i < (count($product)+2); $i++) {
                if ($i!=2){
                    $rows.=',';
                }
                $rows.="($1, $".($i).")";
            }
            $sql_4='DELETE FROM erp_farm_process_product WHERE farm_process_id=$1;';
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
            $sql_5='DELETE FROM erp_farm_process_worker WHERE farm_process_id=$1;';
            $sql_3 = "INSERT INTO  erp_farm_process_worker  (farm_process_id, worker_id) VALUES".$rows;
        }


        if((pg_prepare($link,'stmt_insert', $sql)) 
            && (empty($product) ||  (pg_prepare($link,'stmt_insert_2', $sql_2) && pg_prepare($link, 'stmt_insert_4', $sql_4)))
            && (empty($worker) ||  (pg_prepare($link,'stmt_insert_3', $sql_3) && pg_prepare($link, 'stmt_insert_5', $sql_5)))){
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

            $param_sql = array($name, $requirement_dict, $notes, $process_id);

            $result=pg_execute($link, 'stmt_insert',$param_sql);
            
            // Attempt to execute the prepared statement
            if($result && empty($product) && empty($worker) && pg_query($link, 'COMMIT;')){
                $name = $purchase = $worker = $notes= $requirements="";
            }else{
                //$id= pg_fetch_row($result);
                //$exec_1=$exec_2=true;
                if(!empty($product)){
                        array_push($product, $process_id);
                        $exec_1=(pg_execute($link, 'stmt_insert_4', array($process_id+0)) && pg_execute($link, 'stmt_insert_2', array_reverse($product)) );
                }
                if(!empty($worker)){
                        array_push($worker, $process_id);
                        $exec_2=(pg_execute($link, 'stmt_insert_5', array($process_id+0)) && pg_execute($link, 'stmt_insert_3', array_reverse($worker))  );

                    }
                pg_query($link, 'COMMIT;') or die('Failed To Commit');

            //}   else{
                if ($result && $exec_1 && $exec_2){
                    $name= $requirements = $product = $worker = $notes=$process_id="";
                    $process_query=pg_query($link, $process_sql);
                    $process_list=pg_fetch_all($process_query);
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
        <title>Edit Farm Process | Yengas FarmERP</title>
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
                <h2>Edit Farm Process</h2>
                <p>Please fill this form.</p>
                <form  id='form' method="<?php echo (!empty($process_id)) ? 'post' : 'get'; ?>">
                    <div class="form-group ">
                        <label for='process'>Process</label>
                        <select class="form-control select_multiple <?php echo (!empty($process_err)) ? 'is-invalid' : ''; ?>" id="process" name="process_id" onchange="send_get_request('process')">
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
                    <?php if($process_id){ ?>
                    <div class="form-group ">
                        <label for='name'>Name</label>
                        <input type="text" name="name" class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $name; ?>" id='name'>
                        <span class="invalid-feedback"><?php echo $name_err ?></span>
                    </div>   
                    <div class='form-row'>
                        <div class='col'> 
                            <div class="form-group ">
                                <label for="worker">Worker</label>
                                <select multiple class="form-control select_multiple  <?php echo (!empty($worker_err)) ? 'is-invalid' : ''; ?>" id="worker" name="worker_id[]">
                                    <?php
                                        $x=0;
                                        foreach($worker_list as $worker_int){
                                            if (in_array($worker_int['id'],$worker) ) {
                                                echo "<option value='".$worker_int['id']."' selected >".$worker_int['surname'].", ".$worker_int['other_names']."</option>";
                                            }
                                            else {
                                                echo "<option value='".$worker_int['id']."'>".$worker_int['surname'].", ".$worker_int['other_names']."</option>";
                                            }
                                            $x++;
                                            
                                        }
                                    ?>
                                </select>
                                <span class="invalid-feedback"><?php echo $worker_err; ?></span>
                            </div>
                        </div>
                        <div class='col'>
                            <div class="form-group ">
                                <label for="product">Products Used:</label>
                                <select multiple class="form-control select_multiple <?php echo (!empty($product_err)) ? 'is-invalid' : ''; ?>" id="product" name="product_id[]">
                                    <?php
                                        $x=0;
                                        foreach($product_list as $product_int){
                                            if (in_array($product_int['id'], $product) ) {
                                                echo "<option value='".$product_int['id']."' selected >".$product_int['name']."</option>";
                                            }else {
                                                echo "<option value='".$product_int['id']."'>".$product_int['name']."</option>";
                                            }
                                            $x++;
                                            
                                        }
                                    ?>
                                </select>
                                <span class="invalid-feedback"><?php echo $product_err; ?></span>
                            </div>
                        </div>
                    </div>
                    <h4>Add Required specification</h4>
                    <p>Use the field below to add the conditions that should be checked during the farm process</p>

                    <div class="card">
                        <div class="card-body">
                            <div id='fields'>
                            <?php
                             for ($i=0, $x=1; $i < count($requirements); $i++, $x++){?>
                                <div id="requirement_container_<?= $x ?>" class="form-group input-group mb-3">
                                    <input type="text" class="requirement_field form-control" name="requirement_field[]" id="requirement_field_<?= $x ?>" value="<?= $requirements[$i]?>">
                                    <div class="input-group-append">
                                        <button class="btn btn-danger" onclick="change_field('requirement','delete',<?= $x ?>)" type="button" title="Delete Field" id="delete_requirement_button_<?= $x ?>">
                                            <span class="glyphicon glyphicon-trash">Delete</span>
                                        </button>
                                    </div>
                                </div>                            
                            <?php }?>
                            </div>
                        </div>
                    </div>
                    <input type='hidden' name='requirement_field_number' id="requirement_field_number" value='<?= $x ?>'>
                    <button class='btn btn-secondary'type="button" onclick="change_field('requirement', 'add')">Add</button>
                    
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea name="notes" class="form-control" id="notes"><?php echo $notes?></textarea>
                        <span class="help-block"><?php echo $notes_err; ?></span>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-primary" value="Submit">
                        <input type="reset" class="btn btn-default" value="Reset">
                    </div>
                    <?php } ?>
                </form>
            </div> 
        </main>
        <?php require_once(realpath(dirname(__FILE__) . '/..'. '/..'. '/..') ."/"."include/footer.php")?>
        <?php 
        //pg_query($link, 'BEGIN;');
        //pg_prepare($link, 'smt', 'INSERT INTO xl(name, car,quantity) VALUES ($1,$2,$1);');
        //echo pg_execute($link, 'smt', array(11, 'car')).'hkhkfldl';
        //$product_query_2=pg_query($link, "INSERT INTO erp_farm_process (name,  notes)VALUES ('car', 'car') RETURNING id");
        //$product_list_2=pg_fetch_all($product_query_2);




        ?>

    </body>
</html>