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

// Processing form data when form is submitted


if($_SERVER["REQUEST_METHOD"] == "POST"){

    if(empty(trim($_POST["maintenance_fee"]))){
        $maintenance_fee_err = "Please enter a valid maintenance fee.";     
    }elseif(!is_int(trim($_POST["maintenance_fee"]+0))){
        $maintenance_fee_err = "Value submitted is not valid";
    }else{
        $maintenance_fee = trim($_POST["maintenance_fee"]);
    };

    // Validate daily schedule
    if(empty(trim($_POST["daily_schedule"]))){
        $daily_schedule_err = "Please enter the daily schedule";     
    }else{
        $date_array=explode('-', $daily_schedule);
        echo '<h1>'.$date_array[0].$date_array[1].$date_array[2].$maintenance_fee.'</h1>';

        if(!checkdate($date_array[1], $date_array[2],$date_array[0])){
            $daily_schedule_err.='The date provided is not valid';
        }else{
            $daily_schedule=trim($_POST('daily_schedule'));
        };
    };
    // Validate cleaned
    if(!empty(trim($_POST["cleaned"])) && trim($_POST["cleaned"])!='true'){
        $cleaned_err = "Please enter valid cleaned value.";     
    
    }elseif(trim($_POST["cleaned"]) =='true'){
        $cleaned = true;
    }else{
        $cleaned = false;

    };

    // Validate pesticide application
    if(!empty(trim($_POST["pesticide_application"])) && trim($_POST["pesticide_application"])!='true'){
        $pesticide_application_err = "Please enter valid pesticide application value.";     
    }elseif(trim($_POST["cleaned"]) =='true'){
        $pesticide_application = true;
    }else{
        $pesticide_application = false;

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
                // Redirect to login page
                header("location: login.php");
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
    </style>
</head>
<body>
    <div class="wrapper">
        <h2>Add Meat Sale Record</h2>
        <p>Please fill this form.</p>
        <form  method="post">   
            <div class="form-group <?php echo (!empty($date_sale_err)) ? 'has-error' : ''; ?>">
                <label for="date_sale">Date Of Sale</label>
                <input type="date" name="date_sale" class="form-control" value="<?php echo(!empty($date_sale)) ? $date_sale : 0;?>" id="date_sale">
                <span class="help-block"><?php echo $date_sale_err; ?></span>
            </div>
            <div class="form-group <?php echo (!empty($type_err)) ? 'has-error' : ''; ?>">
                <label for="type">Type</label>
                <input type="int" name="type" class="form-control" value="<?php echo(!empty($type)) ? $type: 0;?>" id="type">
                <span class="help-block"><?php echo $type_err; ?></span>
            </div>
            <div class="form-group <?php echo (!empty($bird_number_err)) ? 'has-error' : ''; ?>">
                <label for="bird_number">Bird Number</label>
                <input type="int" name="bird_number" class="form-control" value="<?php echo(!empty($bird_number)) ? $bird_number: 0;?>" id="bird_number">
                <span class="help-block"><?php echo $bird_number_err; ?></span>
            </div>
            <div class="form-group <?php echo (!empty($weight_err)) ? 'has-error' : ''; ?>">
                <label for="weight">Weight</label>
                <input type="int" name="weight" class="form-control" value="<?php echo(!empty($weight)) ? $weight : 0;?>" id="weight">
                <span class="help-block"><?php echo $weight_err; ?></span>
            </div>
            <div class="form-group <?php echo (!empty($weight_err)) ? 'has-error' : ''; ?>">
                <label for="weight_per_kg">Weight</label>
                <input type="int" name="weight_per_kg" class="form-control" value="<?php echo(!empty($weight_per_kg)) ? $weight_per_kg : 0;?>" id="weight_per_kg">
                <span class="help-block"><?php echo $weight_per_kg_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Submit">
                <input type="reset" class="btn btn-default" value="Reset">
            </div>
        </form>
    </div>    
</body>
</html>