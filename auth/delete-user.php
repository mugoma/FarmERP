<?php
// Include config file
require_once (realpath(dirname(__FILE__) . '/..') ."/"."config.php");
redirecttologin($_SERVER['PHP_SELF']);
checkpermissions(array(2));


$user_sql="SELECT id, username FROM auth_users WHERE (active='true');";
$user_query=pg_query($link, $user_sql);
$user_list=pg_fetch_all($user_query);
 
// Define variables and initialize with empty values
$user_id = "";
$user_id_err="";

// producting form data when form is submitted


if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(empty(test_input($_REQUEST['user_id']))){
        $user_id_err='Please select a user.';
    }else{
        $user_id=test_input($_REQUEST['user_id']);

        pg_prepare($link, 'check_user_exists', "SELECT * FROM auth_users WHERE (id = $1 AND active='true');");
        $check_user_exists=pg_execute($link, 'check_user_exists', array($user_id));
        if (pg_num_rows($check_user_exists)!=1){
            $user_id_err="Error retrieving the submitted user.";
        }
    }

    
    // Check input errors before inserting in database
    if(empty($user_id_err) ){
        if($stmt = pg_prepare($link,'stmt_insert', "UPDATE  auth_users SET active='false' WHERE id=$1;")){            
            // Attempt to execute the prepared statement
            if(pg_execute($link, 'stmt_insert',array($user_id))){
                $user_id_="";
                $user_query=pg_query($link, $user_sql);
                $user_list=pg_fetch_all($user_query);

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
        <title>Delete User | Auth | <?= SOFTWARE_NAME ?></title>
        <?php require_once(realpath(dirname(__FILE__) . '/..') ."/"."include/header.php")?>
        <style type="text/css">
            body{ font: 14px sans-serif; }
        </style>
    </head>
    <body>
        <?php require_once (realpath(dirname(__FILE__) . '/..') ."/"."nav.php");?>
        <main>
            <div class="wrapper container">
                <h2>Delete User</h2>
                <p>Please fill this form.</p>
                <form  id='form' method="post">
                    <div class="form-group ">
                        <label for='user'>User</label>
                        <select class="form-control select_multiple <?= (!empty($user_id_err)) ? 'is-invalid' : ''; ?>" id="user" name="user_id" required>
                            <option value='' disabled='disabled' <?= (!empty($user_id)) ? '' : 'selected'; ?>>Please select a user</option>";

                            <?php foreach($user_list as $user_int){?>
                            <option value='<?= $user_int['id']?>' <?= ($user_int['id']==$user_id)?"selected":"" ?>><?=$user_int['username']?></option>       
                            <?php }?>
                        </select>
                        <span class="invalid-feedback"><?= $user_id_err ?></span>
                    </div>

                    <div class="form-group">
                        <input type="button" class="btn btn-primary" value="Submit" data-toggle="modal" data-target="#confirmdeletemodal" >
                        <input type="reset" class="btn btn-default" value="Reset">
                    </div>
                </form>
            </div> 
        </main>
        <?php require_once(realpath(dirname(__FILE__) . '/..') ."/"."include/footer.php")?>

        <div class="modal fade" id="confirmdeletemodal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Confirm User Deletion</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are You Sure You Want To Delete This User?</p>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="document.getElementById('form').submit()">Confirm Delete</button>
            </div>
            </div>
        </div>
    </div>


    </body>
</html>