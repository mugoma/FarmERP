<?php
;
/*
if (preg_match('(registration.php)', $_SERVER['PHP_SELF'])) {
    Header("Location: error.php?err=01");
    exit();
}*/
// Include config file
require_once(realpath(dirname(__FILE__) . '/..'.'/..') ."/"."config.php");
redirecttologin($_SERVER['PHP_SELF']);
checkpermissions(array(3,4));


// Define variables and initialize with empty values
$product=$quantity=$notes=$name=$product_id=$quantity_id=$building=$plant_animal=$date=$time="";
$product_err =$quantity_err=$notes_err=$building_err=$plant_animal_err=$date=$time="";

$product_querry=pg_query($link, "SELECT erp_product.id, erp_product.name, erp_product.grows ,quantity_current.id AS quantity_id,quantity_current.quantity AS quantity_quantity FROM erp_product 
    LEFT JOIN erp_product_quantity_current quantity_current ON quantity_current.product_id=erp_product.id AND quantity_current.grows=FALSE
    WHERE (erp_product.produced ='true' AND erp_product.active='true')");
$product_list=pg_fetch_all($product_querry);

$building_query=pg_query($link, "SELECT id, name  FROM erp_farm_building WHERE (active='true')");
$building_list=pg_fetch_all($building_query);

$plant_animal_query=pg_query($link, "SELECT grow_product.name, grow_product.id, building.id AS building_id , building.name AS building_name FROM erp_grow_product grow_product
        JOIN erp_product_quantity_current quantity_current ON quantity_current.id=grow_product.product_quantity_current_id
        JOIN erp_farm_building building ON grow_product.building_id=building.id
        JOIN erp_product product ON product.id=grow_product.product_id AND product.active=TRUE
    WHERE quantity_current.quantity>0");
$plant_animal_list=pg_fetch_all($plant_animal_query);
// Processing form data when form is submitted


if($_SERVER["REQUEST_METHOD"] == "POST"){

    
    if((empty(test_input($_POST["quantity"])) && test_input($_POST["quantity"])!='0') || test_input($_POST['quantity']) < 0){
        $quantity_err = "Please enter a valid quantity"; 
    }else{
        $quantity = test_input($_POST["quantity"])+0;
    };

    if(empty(test_input($_POST["product_id"])) || test_input($_POST["product_id"])+0 < 0){
        $product_err = "Please select a valid product";     
    }else{
        $id='';
        $product = test_input($_POST["product_id"])+0;

        foreach ($product_list as $value) {
            if ($value['id']==$_POST["product_id"]){
                $id=true;
                $product_id=$value['id'];
                $name=$value['name'];
                $quantity_id=$value['quantity_id']??0;
                $prev_quantity=$value['quantity_quantity']??0;
            break;
            }
        }
        if (empty($id)) {
            $product_err = 'The selected product does not exists';
        }
    };
    $notes = test_input($_POST["notes"]);
    if(empty($_POST['plant_animal']) || !in_array($_POST['plant_animal'], array_column($plant_animal_list,'id'))){
        $plant_animal_err='Please select a valid plant/animal.';
    }else{
        $plant_animal=test_input($_POST['plant_animal']);
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
    $building=array_column($plant_animal_list,'building_id','id')[$plant_animal]??'';

    
    
    // Check input errors before inserting in database
    if(
        empty($product_err) 
        && empty($quantity_err)
        && empty($building_err)
        && empty($plant_animal_err)
        ){
        pg_query($link, 'BEGIN;');
        $sql_1 = "INSERT INTO erp_production_record (product_id, grow_product_id,farm_building_id,datetime_produced,quantity, notes,added_by) VALUES ($1, $2,$3, $4,$5,$6,$7)";
        $sql_2 = "INSERT INTO erp_product_quantity_records (quantity,transaction_type, product_quantity_current_id, added_by,previous_quantity) VALUES ($1, 'Increase',$2,$3,$4)";
        $sql_3 = $sql_4 = $grows= '';

        $param_product_quantity_name='';
        $param_check_exists_row = strtolower($name)."(".date("Y-m-d").")";

        foreach($product_list as $product_int){
            if ($product_int['id'] == $product && $product_int['grows']=='t'&& pg_num_rows(pg_query($link, "SELECT * FROM erp_product_quantity_current table_ WHERE lower(table_.name)=lower('$param_check_exists_row')"))>0 ) {
                $grows=false;
                $sql_3 = "UPDATE erp_product_quantity_current SET  quantity=quantity+$1 WHERE id=$2 RETURNING id";
                //$sql_4 = "INSERT INTO erp_grow_product (product_id,quantity, name) VALUES ($1, $2, $3)";
                $param_product_quantity_name = strtolower($name)."(".date("Y-m-d").")";
            break;
            }elseif ($product_int['id'] == $product && $product_int['grows']=='t') {
                $grows=true;
                $sql_3 = "INSERT INTO erp_product_quantity_current (quantity, product_id, grows) VALUES ($1, $2,'true') RETURNING id";
                $sql_4 = "INSERT INTO erp_grow_product (product_id,quantity, name) VALUES ($1, $2, $3)";
                $param_product_quantity_name = strtolower($name)."(".date("Y-m-d").")";
            break;
            }elseif($product_int['id'] == $product && $product_int['grows']=='f'){
                $grows=false;
                $sql_3 = "UPDATE erp_product_quantity_current SET  quantity=quantity+$1 WHERE id=$2 RETURNING id";
                $param_product_quantity_name = strtolower($name);
            break;
            }
        }

        if(
            pg_prepare($link,'stmt_insert_1', $sql_1) 
            && pg_prepare($link,'stmt_insert_2', $sql_2) 
            && pg_prepare($link,'stmt_insert_3', $sql_3) 
            && ($grows==false ||  pg_prepare($link,'stmt_insert_4', $sql_4))
            ){            
            // Set parameters
            $param_product_id = $product;
            $param_quantity= $quantity;
            $param_notes=$notes;
            $param_name=$name;
            $execute_3='';
            $execute_4='';
            if ($grows==false){
                $execute_3=pg_execute($link, 'stmt_insert_3',array($param_quantity, $quantity_id) );
            }elseif($grows==true) {
                $execute_3=pg_execute($link, 'stmt_insert_3', array($param_product_quantity_name, $param_quantity, $product_id));
                $execute_4=pg_execute($link, 'stmt_insert_4', array($param_product_id, $param_quantity, $param_product_quantity_name));
            }
            $product_quantity_current_id=pg_fetch_assoc($execute_3,0)['id'];
            $array_insert_1=array($param_product_id, $plant_animal,$building,$date." ".$time, $param_quantity,$param_notes, $session_username);
            $array_insert_2=array($param_quantity,$product_quantity_current_id,$session_username,($grows===true)?0:$prev_quantity);
            $execute_1=pg_execute($link, 'stmt_insert_1',$array_insert_1);
            $execute_2=pg_execute($link, 'stmt_insert_2',$array_insert_2);

            // Attempt to execute the prepared statement
            if(
                $execute_1 
                && $execute_2 
                && $execute_3 
                && ($grows==false || $execute_4)
                && pg_query($link, 'COMMIT;')){
                $product=$quantity=$notes="";
                DisplaySuccessMessage();
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
        <title>Add Production Record<?=" | Forms | ".SOFTWARE_NAME ?></title>
        <?php require_once(realpath(dirname(__FILE__) .'/..'.'/..') ."/"."include/header.php")?>
        <style type="text/css">
            body{ font: 14px sans-serif; }
        </style>
    </head>
    <body>
        <?php require_once (realpath(dirname(__FILE__) . '/..'.'/..') ."/"."nav.php");?>
        <main>
            <div class="wrapper container">
                <h2>Add Production Record</h2>
                <p>Please fill this form.</p>
                <form  method="post">
                    <div class="form-group ">
                            <label for="product">Product *</label>
                            <select class="form-control <?= (!empty($product_err)) ? 'is-invalid' : ''; ?>" id="product" name="product_id">
                                <?php
                                    $x=0;
                                    foreach($product_list as $product_int){
                                        if ($product_int['id'] == $product) {
                                            echo "<option value='".$product_int['id']."' selected >".$product_int['name']."</option>";
                                        }elseif (!$product && $x==0) {
                                            echo "<option value='".$product_int['id']."' selected >".$product_int['name']."</option>";
                                        }
                                        else {
                                            echo "<option value='".$product_int['id']."'>".$product_int['name']."</option>";
                                        }
                                        $x++;
                                        
                                    }
                                ?>
                            </select>
                        <span class="invalid-feedback"><?= $product_err; ?></span>
                    </div>
                    <div class="form-group ">
                            <label for="plant_animal">Plant\Animal *</label>
                            <select class="form-control <?= (!empty($product_err)) ? 'is-invalid' : ''; ?>" id="product" name="plant_animal">
                                <?php
                                $x=0;
                                foreach($plant_animal_list as $product_int){?>
                                <option value="<?=$product_int['id']?>" <?=($product_int['id'] == $plant_animal || !$plant_animal && $x==0)?"selected":""?> ><?=$product_int['name']?> in <?=$product_int['building_name']?></option>";

                                 <?php    $x++;
                                    
                                }
                            ?>
                            </select>
                        <span class="invalid-feedback"><?= $plant_animal_err; ?></span>
                    </div> 
                    <!--
                    <div class="form-group ">
                            <label for="building">Farm Building *</label>
                            <select class="form-control <?= (!empty($building_err)) ? 'is-invalid' : ''; ?>" id="building" name="building_id">
                                <?php
                                    $x=0;
                                    foreach($building_list as $product_int){?>
                                    <option value="<?=$product_int['id']?>" <?=($product_int['id'] == $building || !$building && $x==0)?"selected":""?> ><?=$product_int['name']?></option>";

                                     <?php    $x++;
                                        
                                    }
                                ?>
                            </select>
                        <span class="invalid-feedback"><?= $product_err; ?></span>
                    </div> -->                  

                    <div class='form-row'>
                                <div class='col'>
                                    <div class="form-group">
                                        <label for='quantity'>Quantity *</label>
                                        <input type="text" name="quantity" class="form-control <?= (!empty($quantity_err)) ? 'is-invalid' : ''; ?>" value="<?= $quantity; ?>" id='quantity'>
                                        <span class="invalid-feedback"><?= $quantity_err ?></span>
                                    </div>
                                </div>
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
                            </div>
                    <div class="form-group">
                        <label for='notes'>Notes</label>
                        <textarea name="notes" class="form-control  <?= (!empty($notes_err)) ? 'is-invalid' : ''; ?>" value="<?= $notes; ?>" id='notes'></textarea>
                        <span class="invalid-feedback"><?= $notes_err ?></span>
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