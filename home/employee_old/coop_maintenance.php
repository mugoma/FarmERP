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
$maintenance_fee = $daily_schedule = $cleaned = $pesticide_application = "";
$maintenance_fee_err = $daily_schedule_err = $cleaned_err = $pesticide_application_err = "";
$toast="";

// Processing form data when form is submitted


if($_SERVER["REQUEST_METHOD"] == "POST"){

    if(empty(test_input($_POST["maintenance_fee"]))){
        $maintenance_fee_err = "Please enter a valid maintenance fee.";     
    }elseif(!test_int(test_input($_POST["maintenance_fee"]))){
        $maintenance_fee_err = "Value submitted is not valid";
    }else{
        $maintenance_fee = test_input($_POST["maintenance_fee"]);
    };

    // Validate daily schedule
    if(empty(test_input($_POST["daily_schedule"]))){
        $daily_schedule_err = "Please enter the daily schedule";     
    }else{
        if(!test_date($_POST["daily_schedule"])){
            $daily_schedule_err.='The date provided is not valid';
        }else{
            $daily_schedule=test_input($_POST['daily_schedule']);
        };
    };
    // Validate cleaned
    if(!empty(test_input($_POST["cleaned"])) && test_input($_POST["cleaned"])!='true'){
        $cleaned_err = "Please enter valid cleaned value.";     
    
    }elseif(trim($_POST["cleaned"]) =='true'){
        $cleaned = 'true';
    }else{
        $cleaned = 'false';

    };

    // Validate pesticide application
    if(!empty(test_input($_POST["pesticide_application"])) && test_input($_POST["pesticide_application"])!='true'){
        $pesticide_application_err = "Please enter valid pesticide application value.";     
    }elseif(test_input($_POST["pesticide_application"]) =='true'){
        $pesticide_application = 'true';
    }else{
        $pesticide_application = 'false';

    };
    
    // Check input errors before inserting in database
    if(empty($maintenance_fee_err) && empty($daily_schedule_err) && empty($cleaned_err) && empty($pesticide_application_err) ){
        
        // Prepare an insert statement
        $sql = "INSERT INTO coop_maintenance (daily_schedule, cleaned, pesticide_application, maintenance_fee) VALUES ($1, $2, $3, $4)";
         
        if($stmt = pg_prepare($link,'stmt_insert', $sql)){
            // Bind variables to the prepared statement as parameters
            //mysqli_stmt_bind_param($stmt, "sss", $param_username, $param_password);
            
            // Set parameters
            $param_daily_schedule = $daily_schedule;
            $param_cleaned = $cleaned;
            $param_pesticide_application = $pesticide_application;
            $param_maintenance_fee = $maintenance_fee;

            
            // Attempt to execute the prepared statement
            if(pg_execute($link, 'stmt_insert',array($param_daily_schedule, $param_cleaned, $param_pesticide_application, $param_maintenance_fee))){
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
                $maintenance_fee = $daily_schedule = $cleaned = $pesticide_application = "";

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
    <title>Add Coop Maitenance Record</title>
    <?php require_once (realpath(dirname(__FILE__) . '/..'. '/..') ."/"."include/header.php")?>
    <style type="text/css">
        body{ font: 14px sans-serif; }
        .wrapper{ width: 350px; padding: 20px; }
    </style>
</head>
<body>
    <?php require_once(realpath(dirname(__FILE__) . '/..'. '/..') ."/"."include/nav_1.php")?>
    <div class="wrapper container">
        <h2>Add Coop Maintenance Record</h2>
        <p>Please fill this form.</p>
        <form  method="post">
            <div class="form-group <?php echo (!empty($daily_schedule_err)) ? 'has-error' : ''; ?>">
                <label>Daily Schedule</label>
                <input type="date" name="daily_schedule" class="form-control" value="<?php echo $daily_schedule; ?>">
                <span class="help-block"><?php echo $daily_schedule_err ?></span>
            </div>    
            <div class="form-group <?php echo (!empty($maintenance_fee_err)) ? 'has-error' : ''; ?>">
                <label for="maintenance_fee">Maintenance Fee</label>
                <input type="int" name="maintenance_fee" class="form-control" value="<?php echo (!empty($maintenance_fee)) ? $maintenance_fee : 0; ?>">
                <span class="help-block"><?php echo $maintenance_fee_err ?></span>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" value="true" id="pesticide" name="pesticide_application">
                <label class="form-check-label" for="pesticide_application">
                    Pesticide Application
                </label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" value="true" id="cleaned" name="cleaned">
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
    <?php echo $toast?>    
   
</body>
</html>