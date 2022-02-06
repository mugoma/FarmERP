<?php
/*
if (preg_match('(registration.php)', $_SERVER['PHP_SELF'])) {
    Header("Location: error.php?err=01");
    exit();
}*/
// Include config file
//SELECT enum_range(NULL::delegation)
//SELECT unnest(enum_range(NULL::delegation))  
require_once(realpath(dirname(__FILE__) . '/..') ."/"."config.php");

redirecttologin($_SERVER['PHP_SELF']);
 
// Define variables and initialize with empty values
$current_password = $new_password = $confirm_new_password = "";
$current_password_err= $new_password_err = $confirm_new_password_err = "";
//Fetching the delegations


 
// Processing form data when form is submitted

if($_SERVER["REQUEST_METHOD"] == "POST"){ 
    // Validate username
    if(empty(test_input($_POST["current_password"]))){
        $current_password_err = "Please enter your current password.";
    } else{
        // Prepare a select statement
        $sql = "SELECT password FROM auth_users WHERE (lower(username) = lower($1) AND id=$2)";
        $current_password = test_input($_POST["current_password"]);

        if(pg_prepare($link, 'stmt_user_exists', $sql)){           
            // Attempt to execute the prepared statement
            if($execute=pg_execute($link,'stmt_user_exists',array($_SESSION['username'], $_SESSION['id']))){
                /* store result */
                
                if(pg_num_rows($execute) != 1){
                    $current_password_err = "This user does not exist.";
                } else{
                    $result=pg_fetch_array($execute);
                    if ($result){
                        $hashed_password=$result['password'];


                        if(password_verify($current_password, $hashed_password)){
                            $current_password = test_input($_POST["current_password"]);
                        }else{
                            $current_password="";

                            $current_password_err='The current password is incorrect.';
                        }
                    }

                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
        }
    }
    
    // Validate password
    if(empty(test_input($_POST["new_password"]))){
        $new_password_err = "Please enter a new password.";     
    } elseif(strlen(test_input($_POST["new_password"])) < 8){
        $new_password_err = "Password must have atleast 8 characters.";
    }elseif(empty($current_password_err) && ($current_password == test_input($_POST["new_password"]))){ 
        $new_password_err = "Current password is similar to new password";
    }else{
        $new_password = test_input($_POST["new_password"]);
    }
    
    // Validate confirm password
    if(empty(test_input($_POST["confirm_new_password"]))){
        $confirm_new_password_err = "Please confirm new password.";     
    } else{
        $confirm__new_password = test_input($_POST["confirm_new_password"]);
        if(empty($new_password_err) && ($new_password != $confirm_new_password)){
            $confirm__new_password_err = "Password did not match.";
        }
    }
    // Validate delegation

    
    // Check input errors before inserting in database
    if(empty($current_password_err) && empty($new_password_err) && empty($confirm_new_password_err)){
        pg_query($link, "BEGIN;");
        // Prepare an insert statement
        $sql = "UPDATE  auth_users SET password=$1, last_pwd_change=$2 WHERE id=$3";
         
        if($stmt = pg_prepare($link,'stmt_insert', $sql)){
            
            $param_password = password_hash($new_password, PASSWORD_DEFAULT); // Creates a password hash
            $exec_user_id=$_SESSION['id'];
            pg_query($link, "INSERT INTO auth_db_records(user_id, db_transaction) VALUES
                ('$exec_user_id','{\"name\":\"Edit System User Password\", \"primary_table\":\"auth_users\", \"affected_id\":\"$exec_user_id\"}')");
            
            // Attempt to execute the prepared statement
            if(pg_execute($link, 'stmt_insert',array($param_password, date("Y-m-d"), $_SESSION['id']) ) && pg_query($link, "COMMIT;")){
                // Initialize the session
                session_start();
                
                // Unset all of the session variables
                $_SESSION = array();
                
                // Destroy the session.
                session_destroy();
                redirecttologin($_SERVER['PHP_SELF']);
            } else{
                echo "Something went wrong. Please try again later.";
            }

            // Close statement
            //mysqli_stmt_close($stmt);
        }
    }
    
    // Close connection
    pg_close($link);
}
?>
 
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Password Change | Auth | <?= SOFTWARE_NAME?></title>
        <?php require_once (realpath(dirname(__FILE__) . '/..') ."/"."include/header.php")?>
        <style type="text/css">
            body{ font: 14px sans-serif; }
        </style>
    </head>
    <body>
        <main>
            <div class="wrapper container">
                <h2>Password Change</h2>
                <p>Please fill this form to change your password.</p>
                <form  method="post">
                    <div class="form-group ">
                        <label>Current Password *</label>
                        <input type="password" name="current_password" class="form-control <?= (!empty($current_password_err)) ? 'is-invalid' : ''; ?>" value="<?= $current_password; ?>" aria-describedby="currentpasswordHelpInline">
                        <span class="invalid-feedback"><?= $current_password_err; ?></span>
                        <small id="currentpasswordHelpInline" class="form-text text-muted">
                            Type your current password.
                        </small>
                    </div>    
                    <div class="form-group ">
                        <label>New Password *</label>
                        <input type="password" name="new_password" class="form-control <?= (!empty($new_password_err)) ? 'is-invalid' : ''; ?>" value="<?= $new_password; ?>" aria-describedby="newpasswordHelpInline">
                        <span class="invalid-feedback"><?= $new_password_err; ?></span>
                        <small id="newpasswordHelpInline" class="form-text text-muted">
                            Must be at least 8 characters long.
                        </small>
                    </div>

                    <div class="form-group">
                        <label>Confirm New Password *</label>
                        <input type="password" name="confirm_new_password" class="form-control  <?= (!empty($confirm_new_password_err)) ? 'is-invalid' : ''; ?>" value="<?= $confirm_new_password; ?>"aria-describedby="confirmpasswordHelpInline">
                        <span class="invalid-feedback"><?= $confirm_new_password_err; ?></span>
                        <small id="confirmpasswordHelpInline" class="form-text text-muted">
                            Please repeat the new password typed above.
                        </small>
                    </div>

                    <div class="form-group">
                        <input type="submit" class="btn btn-primary" value="Submit">
                        <input type="reset" class="btn btn-default" value="Reset">
                    </div>
                    <?= $required_reminder ?>
                </form>
            </div> 
        </main>
    </body>
</html>