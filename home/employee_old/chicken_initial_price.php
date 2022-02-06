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
 
// Define variables and initialize with empty values
$username = $password = $confirm_password = "";
$username_err = $password_err = $confirm_password_err = $delegation_err = "";
//Fetching the delegations


 
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
            //mysqli_stmt_bind_param($stmt, "s", $param_username);
            
            // Set parameters
            $param_username = trim($_POST["username"]);
            
            // Attempt to execute the prepared statement
            if($stmt=pg_execute($link,'stmt_user_exists',array($param_username))){
                /* store result */

                $result=pg_fetch_all($stmt);
                
                if(pg_num_rows($result) >= 1){
                    $username_err = "This username is already taken.";
                } else{
                    $username = trim($_POST["username"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            //pg_close($stmt);
        }
    }
    
    // Validate password
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter a password.";     
    } elseif(strlen(trim($_POST["password"])) < 6){
        $password_err = "Password must have atleast 6 characters.";
    } else{
        $password = trim($_POST["password"]);
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
    if(empty(trim($_POST["delegation"]))){
        $delegation_err = "Please select delegation.";     
    } else{
        $delegation = trim($_POST["delegation"]);
        if(!in_array($delegation, $delegation_options)){
            $delegation_err = "Invalid delegation.";
        }
    }
    
    // Check input errors before inserting in database
    if(empty($username_err) && empty($password_err) && empty($confirm_password_err)){
        
        // Prepare an insert statement
        $sql = "INSERT INTO auth_users (username, password, delegation) VALUES ($1, $2, $3)";
         
        if($stmt = pg_prepare($link,'stmt_insert', $sql)){
            // Bind variables to the prepared statement as parameters
            //mysqli_stmt_bind_param($stmt, "sss", $param_username, $param_password);
            
            // Set parameters
            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
            $param_delegation = $delegation; // Creates a password hash
            
            // Attempt to execute the prepared statement
            if(pg_execute($link, 'stmt_insert',array($param_username, $param_password, $param_delegation))){
                // Redirect to login page
                header("location: login.php");
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
    <title>Add Chicken Initial Price</title>
    <?php require_once (realpath(dirname(__FILE__) . '/..') ."/"."include/header.php")?>

</head>
<body>
    <div class="wrapper container">
        <h2>Sign Up</h2>
        <p>Please fill this form to create an account.</p>
        <form  method="post">
            <div class="form-group <?php echo (!empty($username_err)) ? 'has-error' : ''; ?>">
                <label>Username</label>
                <input type="text" name="username" class="form-control" value="<?php echo $username; ?>">
                <span class="help-block"><?php echo $username_err; ?></span>
            </div>    
            <div class="form-group <?php echo (!empty($password_err)) ? 'has-error' : ''; ?>">
                <label>Password</label>
                <input type="password" name="password" class="form-control" value="<?php echo $password; ?>">
                <span class="help-block"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group <?php echo (!empty($confirm_password_err)) ? 'has-error' : ''; ?>">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" value="<?php echo $confirm_password; ?>">
                <span class="help-block"><?php echo $confirm_password_err; ?></span>
            </div> 
            <div class="form-group <?php echo (!empty($confirm_password_err)) ? 'has-error' : ''; ?>">
                <label for="maintenance_fee">Maintenance Fee</label>
                <input type="int" name="maintenance_fee" class="form-control" >
                <span class="help-block"><?php echo $maintenance_fee_err; ?></span>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" value="" id="pesticide" name="pesticide_application">
                <label class="form-check-label" for="pesticide_application">
                    Pesticide Application
                </label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" value="" id="cleaned" name="cleanedn">
                <label class="form-check-label" for="cleaned">
                    Cleaned
                </label>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Submit">
                <input type="reset" class="btn btn-default" value="Reset">
            </div>
        </form>
    </div>    
</body>
</html>