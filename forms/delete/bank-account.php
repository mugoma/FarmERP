<?php
// Include config file
require_once(realpath(dirname(__FILE__) . '/..'.'/..') ."/"."config.php");
redirecttologin($_SERVER['PHP_SELF']);
checkpermissions(array(2));


$account_sql="SELECT * FROM erp_bank_account WHERE (active='true');";
$account_query=pg_query($link, $account_sql);
$account_list=pg_fetch_all($account_query);
 
// Define variables and initialize with empty values
$account_id = "";
$account_id_err="";

// producting form data when form is submitted


if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(empty(test_input($_REQUEST['account_id']))){
        $account_id_err='Please Select A Bank Account';
    }else{
        $account_id=test_input($_REQUEST['account_id']);

        pg_prepare($link, 'check_account_exists', "SELECT * FROM erp_bank_account WHERE (id = $1 AND active='true');");
        $check_account_exists=pg_execute($link, 'check_account_exists', array($account_id));
        if (pg_num_rows($check_account_exists)!=1){
            $account_id_err="Error retrieving the submitted account.";
        }
    }

    
    // Check input errors before inserting in database
    if(empty($account_id_err) ){
        if($stmt = pg_prepare($link,'stmt_insert', "UPDATE  erp_bank_account SET active='false' WHERE id=$1;")){            
            // Attempt to execute the prepared statement
            if(pg_execute($link, 'stmt_insert',array($account_id))){
                $account_id_="";
                $account_query=pg_query($link, $account_sql);
                $account_list=pg_fetch_all($account_query);

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
        <title>Delete Bank Account | Forms | <?= SOFTWARE_NAME ?></title>
        <?php require_once(realpath(dirname(__FILE__) .'/..'.'/..') ."/"."include/header.php")?>
        <style type="text/css">
            body{ font: 14px sans-serif; }
        </style>
    </head>
    <body>
        <?php require_once (realpath(dirname(__FILE__) . '/..'.'/..') ."/"."nav.php");?>
        <main>
            <div class="wrapper container">
                <h2>Delete Bank Account</h2>
                <p>Please fill this form.</p>
                <form  id='form' method="post">
                    <div class="form-group ">
                        <label for='bank-account'>Bank Account</label>
                        <select class="form-control select_multiple <?= (!empty($account_id_err)) ? 'is-invalid' : ''; ?>" id="bank-account" name="account_id" required>
                            <option value='' disabled='disabled' <?= (!empty($account_id)) ? '' : 'selected'; ?>>Please select a bank account</option>";

                            <?php foreach($account_list as $account_int){?>
                            <option value='<?= $account_int['id']?>' <?= ($account_int['id']==$account_id)?"selected":"" ?>><?$account_int['name']?></option>       
                            <?php }?>
                        </select>
                        <span class="invalid-feedback"><?= $account_id_err ?></span>
                    </div>
                    <div class="form-group">
                        <input type="button" class="btn btn-primary" value="Submit" data-toggle="modal" data-target="#confirmdeletemodal" >
                        <input type="reset" class="btn btn-default" value="Reset">
                    </div>
                </form>
            </div> 
        </main>
        <?php require_once(realpath(dirname(__FILE__) . '/..'. '/..') ."/"."include/footer.php")?>

        <div class="modal fade" id="confirmdeletemodal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Confirm Account Deletion</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are You Sure You Want To Delete This Account?</p>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="document.getElementById('form').submit()">Confirm Delete</button>
            </div>
            </div>
        </div>
    </div>


    </body>
</html>