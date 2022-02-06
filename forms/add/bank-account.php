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
// Processing form data when form is submitted


if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    $ac_name = test_input($_POST["ac_name"]);
    pg_prepare($link, 'check',"SELECT name FROM erp_bank_account WHERE (name=$1)");
    $prev_names=pg_execute($link, "check", array($ac_name));

    if (pg_num_rows($prev_names)!=0){
        $ac_name_err.='An account with that name already exists.';;
    }
    if(empty(test_input($_POST["ac_number"]))){
        $ac_number_err = "Please enter a valid account number.";     
    }else{
        $ac_number = $_POST["ac_number"];
        pg_prepare($link, 'check_2',"SELECT name FROM erp_bank_account WHERE (account_number=$1)");
        $prev_number=pg_execute($link, "check_2", array($ac_number));

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
    if (empty($ac_name_err) && empty($ac_number_err) && empty($inst_name_err)){
        $sql="INSERT INTO erp_bank_account(name, institution, account_number, added_by, notes) VALUES($1,$2,$3,$4,$5);";
        if (pg_prepare($link, 'stmt_insert', $sql)){
            $param_ac_number=pg_escape_literal($link,$ac_number);
            if (pg_execute($link,'stmt_insert', array($ac_name, $inst_name, $param_ac_number, $session_username, $notes))){
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
        <title>Add Bank Account | Forms | <?=SOFTWARE_NAME ?></title>
        <?php require_once(realpath(dirname(__FILE__) .'/..'.'/..') ."/"."include/header.php")?>
        <style type="text/css">
            body{ font: 14px sans-serif; }
        </style>
    </head>
    <body>
        <?php require_once (realpath(dirname(__FILE__) . '/..'.'/..') ."/"."nav.php");?>
        <main>
            <div class="wrapper container">
                <h2>Add Bank Account</h2>
                <p>Please fill this form.</p>
                <form  method="post">
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
                </form>
                <?=$required_reminder?>

            </div> 
        </main>
        <?php require_once(realpath(dirname(__FILE__) . '/..'. '/..') ."/"."include/footer.php")?>


    </body>
</html>