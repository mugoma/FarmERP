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
//redirecttologin($_SERVER['PHP_SELF']);
//checkpermissions(array(2));

 
// Define variables and initialize with empty values
$username = $password = $confirm_password = "";
$username_err = $password_err = $confirm_password_err = $delegation_err = "";
//Fetching the delegations

//$res = pg_query($link, "SELECT unnest(enum_range(NULL::delegation))");
$group_query=pg_query($link, "SELECT * FROM auth_groups WHERE lower(auth_groups.name)!='admin'");

$group_options = pg_fetch_all($group_query);
 
// Processing form data when form is submitted

if($_SERVER["REQUEST_METHOD"] == "POST"){ 
    // Validate username
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter a username.";
    } else{
        // Prepare a select statement
        $sql = "SELECT id FROM auth_users WHERE username = $1";
        
        if(pg_prepare($link, 'stmt_user_exists', $sql)){
            // Bind variables to the prepared statement as parameters
            
            // Set parameters
            $param_username = strtolower(test_input($_POST["username"]));            
            // Attempt to execute the prepared statement
            if($execute=pg_execute($link,'stmt_user_exists',array($param_username))){
                /* store result */

                $result=pg_fetch_all($execute);
                
                if(pg_num_rows($execute) >= 1){
                    $username_err = "This username is already taken.";
                } else{
                    $username = strtolower(test_input($_POST["username"]));
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            //pg_close($stmt);
        }
    }
    
    // Validate password
    if(empty(test_input($_POST["password"]))){
        $password_err = "Please enter a password.";     
    } elseif(strlen(test_input($_POST["password"])) < 6){
        $password_err = "Password must have atleast 6 characters.";
    } else{
        $password = test_input($_POST["password"]);
    }
    
    // Validate confirm password
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "Please confirm password.";     
    } else{
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($password_err) && ($password != $confirm_password)){
            $confirm_password_err = "Password did not match.";
        }
    }
    // Validate delegation
    if(empty($_POST["delegation"])){
        $delegation_err = "Please select delegation.";     
    } else{
        if (count(array_intersect($_POST['delegation'],array_column($group_options, 'id'))) != count($_POST['delegation'])) {
            $delegation_err="One of the selected delegations does not exist.";
            } else{
                $delegation=$_REQUEST['delegation'];
            }
    }
    
    // Check input errors before inserting in database
    if(empty($username_err) && empty($password_err) && empty($confirm_password_err) && empty($delegation_err)){
        pg_query($link,"BEGIN;");
        // Prepare an insert statement
        $sql = "INSERT INTO auth_users (username, password) VALUES ($1, $2) RETURNING id;";
        $sql_2="INSERT INTO auth_user_groups(user_id, group_id) VALUES";
        for ($i=2; $i < count($delegation)+2; $i++) { 
            $sql_2.=($i!=2)?',':"";
            $sql_2.="($1, $$i)";
        }
        if(pg_prepare($link,'stmt_insert', $sql) && pg_prepare($link, 'stmt_insert_2', $sql_2) &&pg_query($link,"BEGIN;") ){
            // Bind variables to the prepared statement as parameters
            
            // Set parameters
            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
            $param_delegation = $delegation; // Creates a password hash
            $exec_1=pg_execute($link, 'stmt_insert',array($param_username, $param_password));
            $id= pg_fetch_row($exec_1);
            array_unshift($delegation, $id[0]+0);
            $exec_2=pg_execute($link, 'stmt_insert_2', $delegation);
            $exec_user_id=$_SESSION['id'];
            //pg_query($link, "INSERT INTO auth_db_records(user_id, db_transaction) VALUES
            //    ('$exec_user_id','{\"name\":\"Add System User \", \"primary_table\":\"auth_users\", \"affected_id\":\"$".$id[0]."\"}')");
            
            
            if($exec_1 && $exec_2 && pg_query($link, "COMMIT;")) {
                // Redirect to login page
                header("location: /auth/login.html");
            } else{
                echo "Something went wrong. Please try again later.";
            }

            // Close statement
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
    <title>Register System User | Auth | <?= SOFTWARE_NAME ?></title>
    <?php require_once (realpath(dirname(__FILE__) . '/..') ."/"."include/header.php")?>
    <style type="text/css">
        body{ font: 14px sans-serif; }
    </style>
</head>
<body>
<?php require_once (realpath(dirname(__FILE__) . '/..') ."/"."nav.php");?>
    <div class="wrapper container">
        <h2>Register System User</h2>
        <p>Please fill this form to create an account.</p>
        <form  method="post">
            <div class="form-group">
                <label>Username *</label>
                <input type="email" name="username" class="form-control <?=(!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?= $username; ?>" autocomplete="off">
                <span class="invalid-feedback"><?= $username_err; ?></span>
            </div>    
            <div class="form-group ">
                <label>Password *</label>
                <input type="password" name="password" class="form-control <?= (!empty($password_err)) ? 'is-invalid' : ''; ?>" value="<?= $password; ?>">
                <span class="invalid-feedback"><?= $password_err; ?></span>
            </div>
            <div class="form-group ">
                <label>Confirm Password *</label>
                <input type="password" name="confirm_password" class="form-control <?=(!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" value="<?= $confirm_password; ?>">
                <span class="invalid-feedback"><?= $confirm_password_err; ?></span>
            </div>
            <div class="form-group ">
                <label for="delegation">Delegation *</label>
                <select class="form-control select_multiple <?= (!empty($delegation_err)) ? 'is-invalid' : ''; ?>" multiple id="delegation" name="delegation[]" required>
                    <?php
                        foreach($group_options as $option){?>
                            <option value='<?=$option['id']?>'><?=$option['name']?> (<?=$option['description']?>)</option>";
                            };
                        <?php } ?>
                </select>
                <span class="invalid-feedback"><?= $delegation_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Submit">
                <input type="reset" class="btn btn-default" value="Reset">
            </div>
        </form>
        <?=$required_reminder?>
    </div> 
</body>
</html>