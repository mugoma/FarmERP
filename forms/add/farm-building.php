<?php

/*
if (preg_match('(registration.php)', $_SERVER['PHP_SELF'])) {
    Header("Location: error.php?err=01");
    exit();
}*/
// Include config file
require_once(realpath(dirname(__FILE__) .'/..'.'/..') ."/"."config.php");
redirecttologin($_SERVER['PHP_SELF']);
checkpermissions(array(2,3));


 
// Define variables and initialize with empty values
$name= "";
$name_err ="";

// Processing form data when form is submitted


if($_SERVER["REQUEST_METHOD"] == "POST"){

    if(empty(test_input($_POST["name"]))){
        $name_err = "Please enter a valid name.";     
    }elseif(strlen(test_input($_POST["name"])>50)){
        $name_err = "Value entered is more than 50 characters.";     
        
    }else{
        $name=test_input($_POST["name"]);
        
    };
    
    // Check input errors before inserting in database
    if(empty($name_err)){
        // Prepare an insert statement
        $sql = "INSERT INTO erp_farm_building (name, added_by) VALUES ($1, $2)";

        if($stmt = pg_prepare($link,'stmt_insert', $sql)){            
            // Set parameters
            $param_name = $name;
            
            // Attempt to execute the prepared statement
            if(pg_execute($link, 'stmt_insert',array($param_name, $session_username))){
                $name="";

                DisplaySuccessMessage();
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
        <title>Add Farm Building<?=" | Forms | ".SOFTWARE_NAME ?></title>
        <?php require_once(realpath(dirname(__FILE__) .'/..'.'/..') ."/"."include/header.php")?>
        <style type="text/css">
            body{ font: 14px sans-serif; }
        </style>
    </head>
    <body>
        <?php require_once (realpath(dirname(__FILE__) . '/..'.'/..') ."/"."nav.php");?>
        <main>
            <div class="wrapper container">
                <h2>Add Farm Building</h2>
                <p>Please fill this form.</p>
                <form  method="post">
                <div class="form-group ">
                        <label for='name'>Name *</label>
                        <input type="text" name="name" class="form-control <?=(!empty($name_err))?'is-invalid':''; ?>" value="<?= $name; ?>" id='name'>
                        <span class="invalid-feedback"><?= $name_err ?></span>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-primary" value="Submit">
                        <input type="reset" class="btn btn-default" value="Reset">
                    </div>
                </form>
                <?=$required_reminder?>

            </div> 
        </main>
        <?php require_once(realpath(dirname(__FILE__) . '/..'. '/..') ."/"."include/footer.php")?>


    </body>
</html>