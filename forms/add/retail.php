<?php
require_once(realpath(dirname(__FILE__) . '/..'.'/..') ."/"."config.php");
redirecttologin($_SERVER['PHP_SELF']);
checkpermissions(array(2,3));

$user_query=pg_query($link, "SELECT users.username, users.id FROM auth_users users
    JOIN auth_user_groups groups ON users.id=groups.user_id
    WHERE users.active='true'AND  users.is_superuser='false' AND groups.group_id=5");
$user_list=pg_fetch_all($user_query);
$product_query=pg_query($link, "SELECT id, name FROM erp_product WHERE (sale='true' AND active='true' AND grows=FALSE)");
$product_list=pg_fetch_all($product_query);

$name=$products=$users=$location=$town="";
$name_err=$products_err=$users_err=$location_err=$town_err="";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(empty(test_input($_POST["name"]))){
        $name_err = "Please enter a valid name.";     
    }else{
        $name = test_input($_POST["name"]);
        pg_prepare($link, 'check',"SELECT name FROM erp_retail_unit WHERE (name=$1)");
        $names=pg_execute($link, "check", array($name));
        if (pg_num_rows($names)!=0){
            $name_err.='A retail store with that name already exists.';
        }
    };
    if(empty(test_input($_POST["town"]))){
        $town_err = "Please enter a valid town name."; 
    }else{
        $town=test_input($_POST['town']);
    }
    $location=test_input($_POST['location']);
    $notes=test_input($_POST['notes']);

    if(isset($_POST['user_id']) && empty($_POST['user_id'])){
            $users_err='Please select a user.';

    }elseif (count(array_intersect($_POST['user_id'],array_column($user_list, 'id'))) != count($_POST['user_id'])) {
            $users_err="One of the selected users does not exist.";
    } else{
        $users=$_REQUEST['user_id'];
    }

    if(isset($_POST['product_id']) && empty($_POST['product_id'])){
        $products_err='Please select a product.';

    }elseif (count(array_intersect($_POST['product_id'],array_column($product_list, 'id'))) != count($_POST['product_id'])) {
        $products_err="One of the selected products does not exist.";
    } else{
        $products=$_REQUEST['product_id'];

    }
    $notes=test_input($_POST['notes']);
    if(empty($name_err) && empty($town_err) && empty($products_err) && empty($users_err) && empty($location_err)){
        pg_query($link, "BEGIN;");
        $sql_1="INSERT INTO erp_retail_unit(name, town,location,added_by, notes) VALUES ($1,$2,$3,$4,$5) RETURNING id";
        $sql_2="INSERT INTO erp_retail_product(retail_id, product_id) VALUES ";
        $sql_4="INSERT INTO erp_retail_product_quantity_current(retail_id,quantity, product_id) VALUES ";
        $sql_5 = " INSERT INTO erp_retail_product_quantity_records (retail_id,quantity, added_by,transaction_type,previous_quantity, product_quantity_current_id ) VALUES ";
        for ($i=2,$x=3; $i < count($products)+2; $i++, $x++) { 
            $sql_2.=($i!=2)?",":"";
            $sql_4.=($i!=2)?",":"";
            $sql_5.=($i!=2)?",":"";
            $sql_2.="($1, $$i)";
            $sql_4.="($1, 0, $$i)";
            $sql_5.="($1,'0',$2,'Increase', 0, $$x )";
        }
        $sql_3="INSERT INTO erp_retail_user(retail_id, user_id) VALUES ";
        for ($i=2; $i < count($users)+2; $i++) { 
            $sql_3.=($i!=2)?",":"";
            $sql_3.="($1, $$i)";
        }

        $sql_4.="RETURNING id ";

        if(pg_prepare($link, 'stmt_insert_1', $sql_1) 
            && pg_prepare($link, 'stmt_insert_2',$sql_2) 
            && pg_prepare($link,'stmt_insert_3' ,$sql_3) 
            && pg_prepare($link,'stmt_insert_4' ,$sql_4) 
            && pg_prepare($link, 'stmt_insert_5', $sql_5)){

            $execute_1=pg_execute($link, 'stmt_insert_1', array($name, $town, $location, $session_username, $notes));
            $retail_id=(pg_fetch_row($execute_1))[0]+0;
            
            array_unshift($products, $retail_id);
            $execute_4=pg_execute($link, 'stmt_insert_4', $products);
            $product_quantity_current_id=(pg_fetch_all($execute_4));


            $param_sql_6_array=array($retail_id,$session_username);
            foreach($product_quantity_current_id as $id){
                array_push($param_sql_6_array, $id['id']);
            }

            array_unshift($users, $retail_id);

            $execute_2=pg_execute($link, 'stmt_insert_2' , $products);
            $execute_3=pg_execute($link, 'stmt_insert_3' , $users);
            $execute_5=pg_execute($link, 'stmt_insert_5' , $param_sql_6_array);
            if ($execute_1 && $execute_2 && $execute_3 && $execute_4 && $execute_5){
                pg_query($link, "COMMIT;");
                $product_list=pg_fetch_all($product_query);
                $name=$products=$users=$location=$town="";
                DisplaySuccessMessage();
            }else{
                pg_query($link, "ROLLBACK;");
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
        <title>Add Retail | Forms | <?= SOFTWARE_NAME ?></title>
        <?php require_once(realpath(dirname(__FILE__) .'/..'.'/..') ."/"."include/header.php")?>
        <style type="text/css">
            body{ font: 14px sans-serif; }
        </style>
    </head>
    <body>
        <?php require_once (realpath(dirname(__FILE__) . '/..'.'/..') ."/"."nav.php");?>
        <main>
            <div class="wrapper container">
                <h2>Add Retail Shop</h2>
                <p>Please fill this form.</p>
                <form  method="post">
                <div class="form-group ">
                        <label for='name'>Name *</label>
                        <input type="text" name="name" class="form-control <?=(!empty($name_err)) ? 'is-invalid' : ''; ?>" value="<?= $name; ?>" id='name'>
                        <span class="invalid-feedback"><?=$name_err ?></span>
                    </div>
                    <div class='form-row'>
                        <div class='col'>
                            <div class="form-group ">
                                <label for='town'>City\Town *</label>
                                <input type="text" name="town" class="form-control <?= (!empty($town_err)) ? 'is-invalid' : ''; ?>" value="<?= $town; ?>" id='town'>
                                <span class="invalid-feedback"><?= $town_err ?></span>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group ">
                                <label for='location'>Location </label>
                                <input type="text" name="location" class="form-control <?= (!empty($location_err)) ? 'is-invalid' : ''; ?>" value="<?= $location; ?>" id='location'>
                                <span class="invalid-feedback"><?= $location_err ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">

                        <div class="col">
                            <div class="form-group ">
                                <label for="user">Authorised System Users:</label>
                                <select multiple class="form-control select_multiple <?= (!empty($users_err)) ? 'is-invalid' : ''; ?>" id="user" name="user_id[]">
                                    <?php
                                        $x=0;
                                        foreach($user_list as $product_int){
                                            if (in_array($product_int['id'], $users)) {
                                                echo "<option value='".$product_int['id']."' selected >".$product_int['username']."</option>";
                                            }else {
                                                echo "<option value='".$product_int['id']."'>".$product_int['username']."</option>";
                                            }
                                            $x++;
                                            
                                        }
                                    ?>
                                </select>
                                <span class="invalid-feedback"><?= $users_err; ?></span>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group ">
                                <label for="product">Authorised Products For Sale</label>
                                <select multiple class="form-control select_multiple <?= (!empty($products_err)) ? 'is-invalid' : ''; ?>" id="product" name="product_id[]">
                                    <?php
                                        $x=0;
                                        foreach($product_list as $product_int){
                                            if (in_array($product_int['id'], $products)) {
                                                echo "<option value='".$product_int['id']."' selected >".$product_int['name']."</option>";
                                            }else {
                                                echo "<option value='".$product_int['id']."'>".$product_int['name']."</option>";
                                            }
                                            $x++;
                                            
                                        }
                                    ?>
                                </select>
                                <span class="invalid-feedback"><?= $products_err; ?></span>
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
                </form>
                <?=$required_reminder?>

            </div> 
        </main>
        <?php require_once(realpath(dirname(__FILE__) . '/..'. '/..') ."/"."include/footer.php")?>


    </body>
</html>