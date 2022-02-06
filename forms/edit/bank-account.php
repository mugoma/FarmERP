<?php

/*
if (preg_match('(registration.php)', $_SERVER['PHP_SELF'])) {
    Header("Location: error.php?err=01");
    exit();
}*/
// Include config file
require_once(realpath(dirname(__FILE__) .'/..'.'/..') ."/"."config.php");
redirecttologin($_SERVER['PHP_SELF']);
checkpermissions(array(2,3));


 
// Define variables and initialize with empty values
$inst_name=$ac_name=$ac_number=$notes="";
$inst_name_err=$ac_name_err=$ac_number_err="";

$account_list=pg_fetch_all(pg_query($link, " SELECT * FROM erp_bank_account WHERE active='true'"));
// Processing form data when form is submitted
if (isset($_GET['account_id']) && test_int($_GET['account_id'])){
    $account_id=test_input($_GET['account_id']);

    
    pg_prepare($link,'get_fields', "SELECT * FROM erp_bank_account WHERE (erp_farm_process.id=$1);");
    $process_details=pg_execute($link, 'get_fields', array($account_id));

    if($process_fields=pg_fetch_assoc($process_details)){
        //$name= $requirements = $product = $worker = $notes="";
        $name=$process_fields['name'];
        $inst_name=$process_fields['institution'];
        $ac_number=$process_fields['account_number'];
        $notes=$process_fields['notes'];

    }

    //pg_prepare($link, "SELECT erp_product.id,erp_product.name  FROM erp_product  JOIN  erp_farm_process_product ON erp_product.id=erp_farm_process_product.product_id WHERE erp_farm_process_product.farm_process_id= $process_id;");


}

if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    $ac_name = test_input($_POST["ac_name"]);
    pg_prepare($link, 'check',"SELECT name FROM erp_bank_account WHERE (name=$1 AND id<> $2)");
    $prev_names=pg_execute($link, "check", array($ac_name, $account_id));

    if (pg_num_rows($prev_names)!=0){
        $ac_name_err.='An account with that name already exists.';;
    }
    if(empty(test_input($_POST["ac_number"]))){
        $ac_number_err = "Please enter a valid account number.";     
    }else{
        $ac_number = $_POST["ac_number"];
        pg_prepare($link, 'check_2',"SELECT name FROM erp_bank_account WHERE (account_number=$1 AND id <> $2)");
        $prev_number=pg_execute($link, "check_2", array($ac_number, $account_id));

        if (pg_num_rows($prev_number)!=0){
            $ac_number_err='An account with that account number already exists.';;
        }
    };
    if(empty(test_input($_POST["inst_name"]))){
        $inst_name_err = "Please enter a valid institution name.";     
    }else{
        $inst_name=test_input($_POST['inst_name']);
    }
    $notes=test_input($_POST['notes']);
    if (empty($ac_name_err) && empty($ac_number_err) && empty($inst_name_err) && empty($account_id_err)){
        $sql="UPDATE erp_bank_account SET name=$1, institution=$2, account_number=$3, added_by=$4, notes=$5 WHERE id =$6;";
        if (pg_prepare($link, 'stmt_insert', $sql)){
            $param_ac_number=pg_escape_literal($link,$ac_number);
            if (pg_execute($link,'stmt_insert', array($ac_name, $inst_name, $param_ac_number, $session_username, $notes, $account_id))){
                $inst_name=$ac_name=$ac_number=$notes="";
                DisplaySuccessMessage();
            }else{
                echo "Something went wrong. Please try again later.";

            }
        }
    }
    
};

?>
 
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Edit Bank Account | Forms | <?=SOFTWARE_NAME ?></title>
        <?php require_once(realpath(dirname(__FILE__) .'/..'.'/..') ."/"."include/header.php")?>
        <style type="text/css">
            body{ font: 14px sans-serif; }
        </style>
    </head>
    <body>
        <?php require_once (realpath(dirname(__FILE__) . '/..'.'/..') ."/"."nav.php");?>
        <main>
            <div class="wrapper container">
                <h2>Edit Bank Account</h2>
                <p>Please fill this form.</p>
                <form  id='form' method="<?= (!empty($account_id)) ? 'post' : 'get'; ?>">
                    <div class="form-group ">
                        <label for='process'>Process</label>
                        <select class="form-control select_multiple <?= (!empty($account_id_err)) ? 'is-invalid' : ''; ?>" id="account" name="account_id" onchange="send_get_request('account')">
                            <option value='' disabled='disabled' <?= (!empty($account_id)) ? '' : 'selected'; ?>>Please select a bank account</option>";

                            <?php
                                $x=0;
                                foreach($account_list as $process_int){?>
                            <option value='<?=$process_int['id']?>' <?= ($process_int['id']==$process_id)?"selected":""?> ><?=$process_int['name']?></option>
                                   <?php  $x++; } ?>
                        </select>
                        <span class="invalid-feedback"><?= $account_id_err ?></span>
                    </div>
                    <?php if($process_id){ ?>
                    <div class="form-group ">
                        <label for='ac_name'>Account Name</label>
                        <input type="text" name="ac_name" class="form-control <?= (!empty($ac_name_err)) ? 'is-invalid' : ''; ?>" value="<?= $ac_name; ?>" id='ac_name'>
                        <span class="invalid-feedback"><?= $ac_name_err ?></span>
                    </div>
                    <div class="form-group ">
                        <label for='inst_name'>Insitution Name *</label>
                        <input type="text" name="inst_name" class="form-control <?= (!empty($inst_name_err)) ? 'is-invalid' : ''; ?>" value="<?= $inst_name; ?>" id='inst_name'>
                        <span class="invalid-feedback"><?= $inst_name_err ?></span>
                    </div>
                    <div class="form-group ">
                        <label for='ac_number'>Account Number</label>
                        <input type="text" name="ac_number" class="form-control <?= (!empty($ac_number_err)) ? 'is-invalid' : ''; ?>" value='<?= $ac_number; ?>' id='ac_number'>
                        <span class="invalid-feedback"><?= $ac_number_err ?></span>
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