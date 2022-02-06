<?php
session_start();
if(!$_SESSION["loggedin"]){
    $_SESSION["login_redirect"]=true;
    header("location: ".'/auth/login.php');
};
/*
if (preg_match('(registration.php)', $_SERVER['PHP_SELF'])) {
    Header("Location: error.php?err=01");
    exit();
}*/
// Include config file
require_once(realpath(dirname(__FILE__) . '/..'.'/..') ."/"."config.php");
 
// Define variables and initialize with empty values
$broken = $no_of_eggs = $date_colected = "";
$broken_err = $no_of_eggs_err = $date_collected_err = "";
$toast="";
// Processing form data when form is submitted


if($_SERVER["REQUEST_METHOD"] == "POST"){
    ///*
    if((empty(test_input($_POST["broken"])) && test_input($_POST["broken"])!='0') || test_input($_POST['broken'])<0){
        $broken_err = "Please enter a valid number of broken eggs.";     
    //}elseif(!filter_var($broken+0, FILTER_VALIDATE_INT)){
        
    //    $broken_err = "Value submitted is not valid";
    }else{
        $broken = test_input($_POST["broken"])+0;
    };

    // Validate daily schedule
    if((empty(test_input($_POST["no_of_eggs"])) && test_input($_POST["broken"])!='0') || test_input($_POST['broken']) < 0){
        $no_of_eggs_err = "Please enter a valid number of broken eggs";     
    //}elseif(!filter_var($no_of_eggs+0, FILTER_VALIDATE_INT)){
     //   $no_of_eggs_err = "Value submitted is not valid";
    }else{
        $no_of_eggs = test_input($_POST["no_of_eggs"])+0;
    };

    // Validate cleaned
    // Validate daily schedule
    if(empty(test_input($_POST["date_collected"]))){
        $date_collected_err = "Please enter the date collected";     
    }else{
        $date_array=explode('-', test_input($_POST['date_collected']));
        if(!checkdate($date_array[1], $date_array[2],$date_array[0])){
            $date_collected_err.='The date provided is not valid';
        }else{
            $date_colected=test_input($_POST['date_collected']);
        };
    };
    if ($no_of_eggs<$broken){
        $broken_err.='Broken eggs are more than colected';
    };
    // Check input errors before inserting in database
    if(empty($broken_err) && empty($no_of_eggs_err) && empty($date_collected_err) ){
        // Prepare an insert statement
        $sql = "INSERT INTO egg_production (date_collected, no_of_eggs, broken) VALUES ($1, $2, $3)";
         
        if($stmt = pg_prepare($link,'stmt_insert', $sql)){
            // Bind variables to the prepared statement as parameters
            //mysqli_stmt_bind_param($stmt, "sss", $param_username, $param_password);
            
            // Set parameters
            $param_date_collected = $date_colected;
            $param_no_of_eggs = $no_of_eggs;
            $param_broken = $broken;

        
            // Attempt to execute the prepared statement
            if(pg_execute($link, 'stmt_insert',array($param_date_collected, $param_no_of_eggs, $param_broken))){
                $toast='
                <div class="alert alert-success" role="alert">
                    <strong>Form Submitted successfully.</strong>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
              <script>$(document).ready(function(){
                $(".alert").alert();
              });</script>
              ';
              $broken = $no_of_eggs = $date_colected = "";

            } else{
                echo "Something went wrong. Please try again later.";
            };

            // Close statement
            //mysqli_stmt_close($stmt);
        }
    };
    
    // Close connection
    pg_close($link);
};

?>
 
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Add Vaccine Record</title>
        <?php require_once (realpath(dirname(__FILE__) . '/..'. '/..') ."/"."include/header.php")?>
        <style type="text/css">
            body{ font: 14px sans-serif; }
        </style>
    </head>
    <body>
        <?php require_once(realpath(dirname(__FILE__) . '/..'. '/..') ."/"."include/nav_1.php")?>

        <div class="wrapper container">
            <h2>Add Egg Production Record</h2>
            <p>Please fill this form.</p>
            <form  method="post">   
                <div class="form-group <?php echo (!empty($date_err)) ? 'has-error' : ''; ?>">
                    <label for="date_collected">Date Collected</label>
                    <input type="date" name="date_collected" class="form-control" value="<?php echo $date_colected ?>" id="date_collected" required>
                    <span class="help-block"><?php echo $date_collected_err; ?></span>
                </div>
                <div class='form-row'>
                    <div class="col-md-6 mb-6">
                        <div class="form-group <?php echo (!empty($no_of_eggs_err)) ? 'has-error' : ''; ?>">
                            <label for="no_of_eggs">Number Of Eggs Collected</label>
                            <input type="int" name="no_of_eggs" class="form-control" value="<?php echo(!empty($no_of_eggs)) ? $no_of_eggs: 0;?>" id="no_of_eggs" required>
                            <span class="help-block"><?php echo $no_of_eggs_err; ?></span>
                        </div>
                    </div>
                    <div class="col-md-6 mb-6">
                        <div class="form-group <?php echo (!empty($broken_err)) ? 'has-error' : ''; ?>">
                            <label for="broken">Broken Eggs</label>
                            <input type="int" name="broken" class="form-control" value="<?php echo(!empty($broken)) ? $broken: 0;?>" id="broken" required>
                            <span class="help-block"><?php echo $broken_err; ?></span>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn btn-primary" value="Submit">
                    <input type="reset" class="btn btn-default" value="Reset">
                </div>
            </form>
        </div>
        <?php echo $toast?>    
    </body>
</html>