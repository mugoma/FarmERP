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
checkpermissions(array(2));

 
// Define variables and initialize with empty values
$username = $delegation = $user_id="";
$username_err =  $delegation_err = $user_id="";
//Fetching the delegations
$user_sql="SELECT id, username FROM auth_users where is_superuser='false';";
$user_query=pg_query($link, $user_sql);
$user_list=pg_fetch_all($user_query);

//$res = pg_query($link, "SELECT unnest(enum_range(NULL::delegation))");
$group_query=pg_query($link, "SELECT * FROM auth_groups WHERE lower(auth_groups.name)!='admin'");

$group_options = pg_fetch_all($group_query);
 
// Processing form data when form is submitted
if (isset($_GET['user_id']) && test_int($_GET['user_id'])){
    $user_id=test_input($_GET['user_id']);

    
    pg_prepare($link,'get_fields', "SELECT * FROM auth_users WHERE id=$1");
    $user_details=pg_execute($link, 'get_fields', array($user_id));

    if($user_fields=pg_fetch_assoc($user_details)){
        $username=$user_fields['username'];
        $delegation=pg_fetch_all(pg_query($link, "SELECT  * FROM auth_user_groups WHERE user_id=$user_id"));
        $delegation=array_column($delegation, 'group_id');


    }



}

if($_SERVER["REQUEST_METHOD"] == "POST"){ 
    // Validate username
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter a username.";
    } else{
        // Prepare a select statement
        $sql = "SELECT id FROM auth_users WHERE username = $1 AND id <> $2";
        
        if(pg_prepare($link, 'stmt_user_exists', $sql)){
            // Bind variables to the prepared statement as parameters
            
            // Set parameters
            $param_username = strtolower(test_input($_POST["username"]));            
            // Attempt to execute the prepared statement
            if($execute=pg_execute($link,'stmt_user_exists',array($param_username, $user_id))){
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
    if(empty($username_err) && empty($delegation_err)){
        pg_query($link,"BEGIN;");
        
        // Prepare an insert statement
        $sql = "UPDATE  auth_users SET username=$1 WHERE id=$2;";
        pg_query($link, "DELETE FROM auth_user_groups WHERE user_id='$user_id'");
        $sql_2="INSERT INTO auth_user_groups(user_id, group_id) VALUES";
        for ($i=2; $i < count($delegation)+2; $i++) { 
            $sql_2.=($i!=2)?',':"";
            $sql_2.="($1, $$i)";
        }
        if(pg_prepare($link,'stmt_insert', $sql) && pg_prepare($link, 'stmt_insert_2', $sql_2)){
            // Bind variables to the prepared statement as parameters
            
            // Set parameters
            $param_username = $username;
            $param_delegation = $delegation; // Creates a password hash
            $exec_1=pg_execute($link, 'stmt_insert',array($param_username, $user_id));
            array_unshift($delegation, $user_id+0);
            $exec_2=pg_execute($link, 'stmt_insert_2', $delegation);
            $exec_user_id=$_SESSION['id'];
            pg_query($link, "INSERT INTO auth_db_records(user_id, db_transaction) VALUES
                ('$exec_user_id','{\"name\":\"Edit System User\", \"primary_table\":\"auth_users\", \"affected_id\":\"$user_id\"}')");
            if($exec_1 && $exec_2  && pg_query($link,"COMMIT;")            ) {
                $username = $delegation = $user_id="";
                DisplaySuccessMessage();
            } else{
                pg_query($link,"ROLLBACK;") or die("Unable to rollback");
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
    <title>Edit System User | Auth | <?= SOFTWARE_NAME ?></title>
    <?php require_once (realpath(dirname(__FILE__) . '/..') ."/"."include/header.php")?>
    <style type="text/css">
        body{ font: 14px sans-serif; }
    </style>
</head>
<body>
<?php require_once (realpath(dirname(__FILE__) . '/..') ."/"."nav.php");?>
    <div class="wrapper container">
        <h2>Edit System User</h2>
        <p>Please fill this form to create an account.</p>
        <form  id='form' method="<?= (!empty($user_id)) ? 'post' : 'get'; ?>">
            <div class="form-group ">
                <label for='user'>System User:</label>
                <select class="form-control select_multiple <?=(!empty($user_id_err)) ? 'is-invalid' : ''; ?>" id="user" name="user_id" onchange="send_get_request('user')">
                    <option value='' disabled='disabled' <?=(!empty($user_id)) ? '' : 'selected'; ?>>Please select a system user</option>";

                    <?php
                        $x=0;
                        foreach($user_list as $process_int){?>
                            <option value='<?=$process_int['id']?>' <?=($process_int['id']==$user_id)?"selected":"";?>><?=$process_int['username']?></option>";
                            
                            
                       <?php } ?>
                </select>
            </div>
            <?php if ($user_id){?>
            <div class="form-group ">
                <label>Username*</label>
                <input type="email" name="username" class="form-control <?= (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?= $username; ?>">
                <span class="invalid-feedback"><?= $username_err; ?></span>
            </div>    
            <div class="form-group ">
                <label for="delegation">Delegation *</label>
                <select class="form-control select_multiple <?=(!empty($delegation_err)) ? 'is-invalid' : ''; ?>" multiple id="delegation" name="delegation[]" required>
                    <?php
                        foreach($group_options as $option){?>
                            <option value='<?=$option['id']?>' <?=(in_array($option['id'], $delegation))?"selected":""; ?>><?=$option['name']?> (<?=$option['description']?>)</option>";
                            };
                        <?php } ?>
                </select>
                <span class="invalid-feedback"><?= $delegation_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Submit">
                <input type="reset" class="btn btn-default" value="Reset">
            </div>
                        <?php }?>
        </form>
        <?=$required_reminder?>
    </div> 
</body>
</html>