<?php
require_once(realpath(dirname(__FILE__) . '/..'.'/..') ."/"."config.php");
redirecttologin($_SERVER['PHP_SELF']);
$user_query=pg_query($link, "SELECT users.username, users.id FROM auth_users users
    JOIN auth_user_groups groups ON users.id=groups.user_id
    WHERE users.active='true'AND  users.is_superuser='false' AND groups.group_id=5");
$user_list=pg_fetch_all($user_query);
$product_query=pg_query($link, "SELECT id, name FROM erp_product WHERE (sale='true' AND active='true' AND grows=FALSE)");
$product_list=pg_fetch_all($product_query);

$retail_query=pg_query($link, $retail_sql="SELECT * FROM erp_retail_unit
WHERE active='true'");
$retail_list=pg_fetch_all($retail_query);

$name=$products=$users=$location=$town=$prev_users=$prev_product=$retail_id="";
$name_err=$products_err=$users_err=$location_err=$town_err=$retail_id_err="";

if (isset($_GET['retail_id']) && test_int($_GET['retail_id'])){
    do{
        $retail_id=(in_array($_REQUEST['retail_id'], array_column($retail_list,'id')))?$_REQUEST['retail_id']:"";
        $retail_id_err=(!in_array($_REQUEST['retail_id'], array_column($retail_list,'id')))?"The selected retail unit does not exist.":"";
        if (!empty($retail_id_err)){break;};
        pg_prepare($link,'get_fields', "SELECT * FROM erp_retail_unit WHERE (id=$1);");
        $retail_details=pg_execute($link, 'get_fields', array($retail_id));

        if($process_fields=pg_fetch_assoc($retail_details)){
            //$name= $requirements = $product = $worker = $notes="";
            $name=$process_fields['name'];
            $town=$process_fields['town'];
            $location=$process_fields['location'];
            $notes=$process_fields['notes'];
            $users=array_column(pg_fetch_all(pg_query($link, "SELECT  erp_retail_user.user_id FROM erp_retail_user JOIN auth_users auth ON auth.id = erp_retail_user.user_id WHERE erp_retail_user.retail_id=$retail_id AND auth.active='true'")) ,'user_id');
            $products=array_column(
                pg_fetch_all(
                    pg_query($link, "SELECT product.product_id, quantity.quantity FROM erp_retail_product product JOIN erp_retail_product_quantity_current quantity ON quantity.product_id=product.product_id WHERE product.retail_id=$retail_id AND quantity.active='true'")
                )
                , 'product_id'
            );
            $prev_product=$products;
            $prev_users=$users;
        }
    }while(0);
    //pg_prepare($link, "SELECT erp_product.id,erp_product.name  FROM erp_product  JOIN  erp_farm_process_product ON erp_product.id=erp_farm_process_product.product_id WHERE erp_farm_process_product.farm_process_id= $process_id;");


}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(empty(test_input($_POST["name"]))){
        $name_err = "Please enter a valid name.";     
    }else{
        $name = test_input($_POST["name"]);
        pg_prepare($link, 'check',"SELECT name FROM erp_retail_unit WHERE (name=$1 AND id<>$2)");
        $names=pg_execute($link, "check", array($name, $retail_id));
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
        $users=$_POST['user_id'];
    }

    if(isset($_POST['product_id']) && empty($_POST['product_id'])){
        $products_err='Please select a product.';

    }elseif (count(array_intersect($_POST['product_id'],array_column($product_list, 'id'))) != count($_POST['product_id'])) {
        $products_err="One of the selected products does not exist.";
    } else{
        $products=$_POST['product_id'];

    }
    $notes=test_input($_POST['notes']);
    if(empty($name_err) && empty($town_err) && empty($products_err) && empty($users_err) && empty($location_err)){
        pg_query($link, "BEGIN;");

        pg_prepare($link, 'existing_before',"SELECT * FROM erp_retail_product_quantity_current WHERE product_id=ANY($1) AND retail_id=$2");
        $existing_before=pg_fetch_all(pg_execute($link,'existing_before',array(to_pg_array($products), $retail_id)));
        if(!empty($existing_before)){
            foreach($existing_before as $value){
                pg_query($link,"UPDATE erp_retail_product_quantity_current SET active=TRUE WHERE id = $value[id]");
                unset($products[array_search($value['product_id'],$products)]);
                $prev_product=\array_diff($prev_product,[$value['product_id']]);
            }
        }
        $sql_6= "UPDATE  erp_retail_product_quantity_current SET active=False WHERE id= ANY($1)";
        $sql_7= "DELETE FROM erp_retail_user WHERE retail_id= ANY($1)";
        $sql_1="UPDATE erp_retail_unit SET name=$1, town=$2,location=$3,added_by=$4, notes=$5 WHERE id =$6 RETURNING id";
        $sql_2="INSERT INTO erp_retail_product(retail_id, product_id) VALUES ";
        $sql_4="INSERT INTO erp_retail_product_quantity_current(retail_id,quantity, product_id) VALUES ";
        $sql_5 = "INSERT INTO erp_retail_product_quantity_records (retail_id,quantity, added_by,transaction_type,previous_quantity, product_quantity_current_id ) VALUES ";

        for ($i=2,$x=3, $y=2,$count=0; $count < count($products); $i++, $x+=2, $y+=2, $count++) { 
            $sql_2.=($count!=0)?",":"";
            $sql_4.=($count!=0)?",":"";
            $sql_5.=($count!=0)?",":"";
            $sql_2.="($1, $$i)";
            $sql_4.="($1, $$y, $".($y+1).")";
            $sql_5.="($1,'0',$2,'Increase', $$x, $".($x+1).")";
        }
        $sql_3="INSERT INTO erp_retail_user(retail_id, user_id) VALUES ";
        for ($i=2; $i < count($users)+2; $i++) { 
            $sql_3.=($i!=2)?",":"";
            $sql_3.="($1, $$i)";
        }

        $sql_4.="RETURNING id ";

        if(pg_prepare($link, 'stmt_insert_1', $sql_1) 
            && (empty($products) 
                || (pg_prepare($link, 'stmt_insert_2',$sql_2)
                && pg_prepare($link,'stmt_insert_4' ,$sql_4) 
                && pg_prepare($link, 'stmt_insert_5', $sql_5)
                )
            ) 
            && pg_prepare($link,'stmt_insert_3' ,$sql_3) 
            && pg_prepare($link,'stmt_delete_1' ,$sql_6) 
            && pg_prepare($link,'stmt_delete_2' ,$sql_7) 
            ){



            $param_6=array(to_pg_array($prev_product));
            $param_7=array(to_pg_array($prev_users));
            $execute_6=pg_execute($link,'stmt_delete_1', $param_6);
            $execute_7=pg_execute($link,'stmt_delete_2', $param_7);

            $param_4=array($retail_id);
            $param_5=array($retail_id, $session_username);
            $product_quantity=array_column($prev_product,'quantity', 'product_id');
            foreach($products as $key=>$product){
                if (array_key_exists($product, $product_quantity)){
                    $param_4[]=$product_quantity[$product];
                    $param_4[]=$product;
                }else{
                    $param_4[]=0;
                    $param_4[]=$product;
                }
            }
            $execute_4=(empty($products)||pg_execute($link, 'stmt_insert_4', $param_4));
            $product_quantity_current_id=(!empty($products))?pg_fetch_all($execute_4):"";


            $param_sql_6_array=array($retail_id,$session_username);

            $x=0;
            foreach($products as $key=>$product){
                if (array_key_exists($product, $product_quantity)){
                    $param_5[]=$product_quantity[$product];
                    $param_5[]=$product_quantity_current_id[$x]['id'];
                }else{
                    $param_5[]=0;
                    $param_5[]=$product_quantity_current_id[$x]['id'];
                }
                $x++;
            }

            $execute_1=pg_execute($link, 'stmt_insert_1', array($name, $town, $location, $session_username, $notes, $retail_id));
            


            array_unshift($users, $retail_id);
            if(!empty($products)){array_unshift($products, $retail_id);}
            

            $execute_2=(empty($products) ||  pg_execute($link, 'stmt_insert_2' , $products));
            $execute_3=pg_execute($link, 'stmt_insert_3' , $users);
            $execute_5=(empty($products) || pg_execute($link, 'stmt_insert_5' , $param_5));
            if ($execute_1 && $execute_2 && $execute_3 && $execute_4 && $execute_5){
                pg_query($link, "COMMIT;");
                //$product_list=pg_fetch_all($product_query);
                $name=$products=$users=$location=$town=$prev_users=$prev_product=$retail_id="";
                $retail_query=pg_query($link, $retail_sql);
                $retail_list=pg_fetch_all($retail_query);
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
        <title>Edit Retail Unit | Forms | <?= SOFTWARE_NAME ?></title>
        <?php require_once(realpath(dirname(__FILE__) .'/..'.'/..') ."/"."include/header.php")?>
        <style type="text/css">
            body{ font: 14px sans-serif; }
        </style>
    </head>
    <body>
        <?php require_once (realpath(dirname(__FILE__) . '/..'.'/..') ."/"."nav.php");?>
        <main>
            <div class="wrapper container">
                <h2>Edit Retail Unit</h2>
                <p>Please fill this form.</p>
                <form  id='form' method="<?= (!empty($retail_id)) ? 'post' : 'get'; ?>">
                <div class="form-group ">
                        <label for='process'>Retail Unit*</label>
                        <select class="form-control select_multiple <?= (!empty($retail_id_err)) ? 'is-invalid' : ''; ?>" id="process" name="retail_id" onchange="send_get_request('process')">
                            <option value='' disabled='disabled' <?= (!empty($retail_id)) ? '' : 'selected'; ?>>Please select a retail unit</option>";

                            <?php foreach($retail_list as $process_int){?>
                            <option value='<?=$process_int['id']?>' <?=($process_int['id']==$retail_id)?"selected":"";?> ><?=$process_int['name']?></option>

                               <?php }  ?>
                        </select>
                        <span class="invalid-feedback"><?= $retail_id_err ?></span>
                    </div>                
                <?php if($retail_id){?>
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
                                    <?php foreach($user_list as $product_int){?>
                                    <option value='<?=$product_int['id']?>' <?=(is_array($users) &&in_array($product_int['id'], $users))?"selected":""?> ><?=$product_int['username']?></option>
                                    <?php }  ?>
                                </select>
                                <span class="invalid-feedback"><?= $users_err; ?></span>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group ">
                                <label for="product">Authorised Products For Sale</label>
                                <select multiple class="form-control select_multiple <?= (!empty($products_err)) ? 'is-invalid' : ''; ?>" id="product" name="product_id[]">
                                    <?php foreach($product_list as $product_int){?>
                                    <option value='<?=$product_int['id']?>' <?=(is_array($products) && in_array($product_int['id'], $products))?"selected":""?> ><?=$product_int['name']?></option>
                                        <?php }  ?>
                                </select>
                                <span class="invalid-feedback"><?= $products_err; ?></span>
                                <small  class="form-text text-muted"> Please ensure that the quantities at the retail store are 0 before deleting them. </small>
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
            <h2>Current Quantity</h2>
                <?php $product_name=array_column($product_list, 'name', 'id');foreach($product_list as $product_int){?>
                    <p><strong><?= ucfirst($product_name[$product_int['id']])?>: </strong><?= $product_int['quantity']?></p>
                <?php } ?>
        </main>
        <?php require_once(realpath(dirname(__FILE__) . '/..'. '/..') ."/"."include/footer.php")?>


    </body>
</html>