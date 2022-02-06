<?php
session_start();
if(!$_SESSION["loggedin"]){
    $_SESSION["login_redirect"]=true;
    header("location: /auth/login.php");
};
/*
if (preg_match('(registration.php)', $_SERVER['PHP_SELF'])) {
    Header("Location: error.php?err=01");
    exit();
}*/
// Include config file
require_once(realpath(dirname(__FILE__) . '/..'.'/..') ."/"."config.php");
 
// Define variables and initialize with empty values
$type_of_vaccine= $purchase_date = $dosage_amount = $age_dosage = $expiry_date="";
$type_of_vaccine_err = $purchase_date_err = $dosage_amount_err = $age_dosage_err =$expiry_date_err="";

// Processing form data when form is submitted


if($_SERVER["REQUEST_METHOD"] == "POST"){

    if(empty(test_input($_POST["type_of_vaccine"]))){
        $type_of_vaccine_err = "Please enter a valid vaccine type.";     
    }else{

        $type_of_vaccine = test_input($_POST["type_of_vaccine"]);
    };
    if(empty(test_input($_POST["purchase_date"]))){
        $purchase_date_err = "Please enter the purchase date";     
    }else{
        $date_array=explode('-', test_input($_POST['purchase_date']));
        if(!checkdate($date_array[1], $date_array[2],$date_array[0])){
            $purchase_date_err.='The date provided is not valid';
        }else{

            $purchase_date=test_input($_POST['purchase_date']);
        };
    };
    if(empty(test_input($_POST["expiry_date"]))){
        $expiry_date_err = "Please enter the purchase date";     
    }else{
        $date_array=explode('-', test_input($_POST['expiry_date']));
        if(!checkdate($date_array[1], $date_array[2],$date_array[0])){
            $expiry_date_err.='The date provided is not valid';
        }else{

            $expiry_date=test_input($_POST['expiry_date']);
        };
    };
    if (test_int((test_input($_POST['dosage_amount'])))){
        $dosage_amount_err.='The dosage_amount provided is not valid';

    }else{

        $dosage_amount=test_input($_POST['dosage_amount']);
    };
    if (test_int((test_input($_POST['age_dosage'])))){
        $age_dosage_err.='The age_dosage provided is not valid';

    }else{

        $age_dosage=test_input($_POST['age_dosage']);
    };
    
    // Check input errors before inserting in database
    if(empty($type_of_vaccine_err) && empty($purchase_date_err) && empty($expiry_date_err) && empty($dosage_amount_err) && empty($age_dosage_err)){
        // Prepare an insert statement
        $sql = "INSERT INTO vaccines (type_of_vaccine, purchase_date, expiry_date, dosage_amount, age_dosage) VALUES ($1, $2, $3, $4, $5)";
         
        if($stmt = pg_prepare($link,'stmt_insert', $sql)){
            // Bind variables to the prepared statement as parameters
            //mysqli_stmt_bind_param($stmt, "sss", $param_username, $param_password);
            
            // Set parameters
            $param_type_of_vaccine = $type_of_vaccine;
            $param_purchase_date = $purchase_date;
            $param_expiry_date = $expiry_date;
            $param_dosage_amount = $dosage_amount;
            $param_age_dosage = $age_dosage;

            
            // Attempt to execute the prepared statement
            if(pg_execute($link, 'stmt_insert',array($param_type_of_vaccine, $purchase_date, $param_expiry_date, $param_dosage_amount, $param_age_dosage))){
                $type_of_vaccine= $purchase_date = $dosage_amount = $age_dosage = $expiry_date="";

            } else{
                echo "Something went wrong. Please try again later.";
            };
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
        <?php require_once(realpath(dirname(__FILE__) . '/..'. '/..') ."/"."include/nav.php")?>
        <main>
            <div class="wrapper container">
                <h2>Add Vaccine Record</h2>
                <p>Please fill this form.</p>
                <form  method="post">
                    <div class="form-group <?php echo (!empty($type_of_vaccine_err)) ? 'has-error' : ''; ?>">
                        <label for='type_of_vaccine'>Type of vaccine</label>
                        <input type="text" name="type_of_vaccine" class="form-control" value="<?php echo $type_of_vaccine; ?>" id='type_of_vaccine'>
                        <span class="help-block"><?php echo $type_of_vaccine_err ?></span>
                    </div>    
                    <div class="form-group <?php echo (!empty($date_sale_err)) ? 'has-error' : ''; ?>">
                        <label for="purchase_date">Purchase Date</label>
                        <input type="date" name="purchase_date" class="form-control" value="<?php echo $purchase_date ?>" id="purchase_date">
                        <span class="help-block"><?php echo $purchase_date_err; ?></span>
                    </div>
                    <div class="form-group <?php echo (!empty($expiry_date_err)) ? 'has-error' : ''; ?>">
                        <label for="expiry_date">Expiry Date</label>
                        <input type="date" name="expiry_date" class="form-control" value="<?php echo $expiry_date?>" id="expiry_date">
                        <span class="help-block"><?php echo $expiry_date_err; ?></span>
                    </div>
                    <div class="form-group <?php echo (!empty($dosage_amount_err)) ? 'has-error' : ''; ?>">
                        <label for="dosage_amount">Dosage Amount</label>
                        <input type="int" name="dosage_amount" class="form-control" value="<?php echo(!empty($dosage_amount)) ? $dosage_amount: 0;?>" id="dosage_amount">
                        <span class="help-block"><?php echo $dosage_amount_err; ?></span>
                    </div>
                    <div class="form-group <?php echo (!empty($age_dosage_err)) ? 'has-error' : ''; ?>">
                        <label for="age_dosage">Age Dosage</label>
                        <input type="int" name="age_dosage" class="form-control" value="<?php echo(!empty($age_dosage)) ? $age_dosage : 0;?>" id="age_dosage">
                        <span class="help-block"><?php echo $age_dosage_err; ?></span>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-primary" value="Submit">
                        <input type="reset" class="btn btn-default" value="Reset">
                    </div>
                </form>
            </div> 
        </main>
        <?php require_once(realpath(dirname(__FILE__) . '/..'. '/..') ."/"."include/footer.php")?>


    </body>
</html>