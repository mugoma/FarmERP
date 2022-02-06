<?php
session_start();
if(!$_SESSION["loggedin"]){
    $_SESSION["login_redirect"]=true;
    header("location: /auth/login.php?next=".$_SERVER['PHP_SELF']);
};
/*
if (preg_match('(registration.php)', $_SERVER['PHP_SELF'])) {
    Header("Location: error.php?err=01");
    exit();
}*/
// Include config file
require_once(realpath(dirname(__FILE__) . '/..'.'/..'.'/..') ."/"."config.php");
 
// Define variables and initialize with empty values
$surname= $other_names="";
$surname_err = $other_names_err="";

// Processing form data when form is submitted


if($_SERVER["REQUEST_METHOD"] == "POST"){

    if(empty(test_input($_POST["surname"]))){
        $surname_err = "Please enter a valid name.";     
    }elseif(strlen(test_input($_POST["surname"])>50)){
        $surname_err = "Value entered is more than 50 characters.";     
        
    }else{
        $surname=test_input($_POST["surname"]);
        
    };
    
    if(empty(test_input($_POST["other_names"]))){
        $other_names_err = "Please enter a valid name.";     
    }elseif(strlen(test_input($_POST["other_names"])>50)){
        $other_names_err = "Value entered is more than 50 characters.";     
        
    }else{
        $other_names=test_input($_POST["other_names"]);
        
    };
    
    // Check input errors before inserting in database
    if(empty($surname_err) && empty($other_names_err)){
        // Prepare an insert statement
        $sql = "INSERT INTO erp_workers (surname, other_names,added_by) VALUES ($1, $2,$3)";

        if($stmt = pg_prepare($link,'stmt_insert', $sql)){            
            // Set parameters
            $param_surname = $surname;
            $param_other_names = $other_names;
            
            // Attempt to execute the prepared statement
            if(pg_execute($link, 'stmt_insert',array($param_surname, $param_other_names,$session_username))){
                $surname= $other_names="";

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
        <title>Add Worker | Forms | <?= SOFTWARE_NAME ?></title>
        <?php require_once (realpath(dirname(__FILE__) . '/..'. '/..'.'/..') ."/"."include/header.php")?>
        <style type="text/css">
            body{ font: 14px sans-serif; }
        </style>
    </head>
    <body>
        <?php require_once(realpath(dirname(__FILE__) . '/..'. '/..') ."/"."employee/nav.php")?>
        <main>
            <div class="wrapper container">
                <h2>Add Worker</h2>
                <p>Please fill this form.</p>
                <form  method="post">
                <div class="form-group ">
                        <label for='surname'>Surname</label>
                        <input type="text" name="surname" class="form-control <?php echo (!empty($surname_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $surname; ?>" id='name'>
                        <span class="invalid-feedback"><?php echo $surname_err ?></span>
                    </div>
                    <div class="form-group ">
                        <label for='other_names'>Other Names</label>
                        <input type="text" name="other_names" class="form-control <?php echo (!empty($other_names_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $other_names; ?>" id='other_names'>
                        <span class="invalid-feedback"><?php echo $other_names_err ?></span>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-primary" value="Submit">
                        <input type="reset" class="btn btn-default" value="Reset">
                    </div>
                </form>
            </div> 
        </main>
        <?php require_once(realpath(dirname(__FILE__) . '/..'. '/..'. '/..') ."/"."include/footer.php")?>


    </body>
</html>