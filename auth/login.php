<?php
// Initialize the session
session_start();
 
// Check if the user is already logged in, if yes then redirect him to welcome page
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){

    header("location: /forms");
}
 
// Include config file
require_once (realpath(dirname(__FILE__) . '/..') ."/"."config.php");
 
// Define variables and initialize with empty values
$username = $password = $delegation = "";
$username_err = $password_err = "";

 
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Check if username is empty
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter username.";
    } else{
        $username = trim($_POST["username"]);
    }
    
    // Check if password is empty
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if(empty($username_err) && empty($password_err)){
        // Prepare a select statement
        $sql = "SELECT id, username, password, delegation FROM auth_users WHERE lower(username) = lower($1) AND active='true'";
        
        if($stmt = pg_prepare($link, 'stmt', $sql)){
            // Bind variables to the prepared statement as parameters
            //pg_prepare($link, "stmt", array($param_username));
            
            // Set parameters
            $param_username = $username;
            
            // Attempt to execute the prepared statement
            $execute = pg_execute($link,'stmt', array($param_username));

            if($execute){
                // Store result
                $result=pg_fetch_array($execute,NULL, PGSQL_ASSOC);
                
                // Check if username exists, if yes then verify password
                if(pg_num_rows($execute) == 1){                    
                    // Bind result variables
                    //pg_get_result($stmt, $id, $username, $hashed_password);
                    $id=$result['id'];
                    $username=$result['username'];
                    $hashed_password=$result['password'];
                    $delegation=$result['delegation'];

                    //if(pg_fetch_array($stmt)){
                    if($result){
                        if(password_verify($password, $hashed_password)){
                            // Password is correct, so start a new session
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["delegation"] = strtolower($delegation);
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;                            
                            
                            // Redirect user to welcome page
                            if ($next=test_input($_REQUEST['next'])) {
                                header("location: $next");
                            }else{
                                header("location: /forms");
                            }
                        } else{
                            $remote_ip=$_SERVER['REMOTE_ADDR'];
                            $remote_address=$_SERVER['REQUEST_URI'];
                            $remote_agent=$_SERVER['HTTP_USER_AGENT'];
                            pg_query($link, "INSERT INTO auth_failed_login(remote_username, 
                            remote_ip, remote_address, user_agent) 
                            VALUES('$username', '$remote_ip', '$remote_address', '$remote_agent');");
                            // Display an error message if password is not valid
                            $password_err = "The password you entered was not valid.";
                        }
                    }
                } else{
                    // Display an error message if username doesn't exist
                    $remote_ip=getUserIpAddr();
                    $remote_address=$_SERVER['REQUEST_URI'];
                    $remote_agent=$_SERVER['HTTP_USER_AGENT']	;
                    pg_query($link, "INSERT INTO auth_failed_login(remote_username, 
                        remote_ip, remote_address, user_agent) 
                        VALUES('$username', '$remote_ip', '$remote_address', '$remote_agent');");	

                    $username_err = "No account found with that username.";
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
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
        <title>Login Page | Auth | <?= SOFTWARE_NAME ?> </title>
        <?php require_once(realpath(dirname(__FILE__) . '/..') ."/"."include/header.php")?>

    </head>
    <body>
        <?php require_once (realpath(dirname(__FILE__) . '/..') ."/"."nav.php");?>
        <main role="main">
            <div class="wrapper container">
                <h2>Login</h2>
                <p>Please fill in your credentials to login.</p>
                <form  method="post">
                    <div class="form-group ">
                        <label>Username*</label>
                        <input type="email" name="username" class="form-control <?= (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?= $username; ?>">
                        <span class="invalid-feedback"><?= $username_err; ?></span>
                    </div>    
                    <div class="form-group ">
                        <label>Password*</label>
                        <input type="password" name="password"  class="form-control <?= (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                        <span class="invalid-feedback"><?= $password_err; ?></span>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-primary" value="Login">
                    </div>
                </form>
                <?=$required_reminder?>
            </div>    
            <?php
            if (isset($_SESSION["login_redirect"]) && $_SESSION["login_redirect"] == true) {
                echo'
                <div class="toast" id="mytoast" style="position: absolute; top: 0; right: 0;">
                    <div class="toast-header">
                        <strong class="mr-auto"><i class="fa fa-grav"></i>Login Required!</strong>
                        <small>30 seconds ago</small>
                        <button type="button" class="ml-2 mb-1 close" data-dismiss="toast">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="toast-body">
                        <div>kindly login first to access this page </div>
                    </div>
                </div>
                <script>
            $(document).ready(function(){
                    $("#mytoast").toast({ autohide: false });
                    $("#mytoast").toast("show");
                }); 

            </script>
                ';
                $_SESSION["login_redirect"] ='';
            }
            ?> 
        </main>
        <?php require_once(realpath(dirname(__FILE__) . '/..') ."/"."include/footer.php")?>

    </body>
</html>