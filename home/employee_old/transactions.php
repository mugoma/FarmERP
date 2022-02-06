<?php
session_start();
if(!$_SESSION["loggedin"]){
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
$trans_ref = $date = $item_sold = $price_per_piece = $number_sold="";
$trans_ref_err = $date_err = $item_sold_err = $price_per_piece_err = $number_sold_err= "";
$toast="";

// Processing form data when form is submitted


if($_SERVER["REQUEST_METHOD"] == "POST"){

    if(empty(test_input($_POST["trans_ref"]))){
        $trans_ref = "Please enter a valid transaction number.";     
    }else{
        $trans_ref = test_input($_POST["trans_ref"]);
    };

    // Validate daily schedule
    if(empty(test_input($_POST["date"]))){
        $date_err = "Please enter the date";     
    }else{
        $date_array=explode('-', test_input($_POST['date']));
        if(!checkdate($date_array[1], $date_array[2],$date_array[0])){
            $date_err.='The date provided is not valid';
        }else{
            $date=test_input($_POST['date']);
        };
    };
    if((empty(test_input($_POST["item_sold"])) && test_input($_POST["item_sold"])!='0') || test_input($_POST["item_sold"])+0 < 0){
        $item_sold_err = "Please enter a valid number of items sold";     
    //}elseif(!filter_var($no_of_eggs+0, FILTER_VALIDATE_INT)){
     //   $no_of_eggs_err = "Value submitted is not valid";
    }else{
        $item_sold = test_input($_POST["item_sold"])+0;
    };

    if((empty(test_input($_POST["price_per_piece"])) && test_input($_POST["price_per_piece"])!='0') || test_input($_POST["price_per_piece"])+0 < 0){
        $price_per_piece_err = "Please enter a valid number of price per piece";     
    //}elseif(!filter_var($no_of_eggs+0, FILTER_VALIDATE_INT)){
     //   $no_of_eggs_err = "Value submitted is not valid";
    }else{
        $price_per_piece = test_input($_POST["price_per_piece"])+0;
    };

    if((empty(test_input($_POST["number_sold"])) && test_input($_POST["number_sold"])!='0') || test_input($_POST["number_sold"])+0 < 0){
        $number_sold_err = "Please enter a valid number of items sold";     
    //}elseif(!filter_var($no_of_eggs+0, FILTER_VALIDATE_INT)){
     //   $no_of_eggs_err = "Value submitted is not valid";
    }else{
        $number_sold = test_input($_POST["number_sold"])+0;
    };
    
    // Check input errors before inserting in database
    if(empty($trans_ref_err) && empty($date_err) && empty($item_sold_err) && empty($price_per_piece_err)  && empty($number_sold_err)){
        
        // Prepare an insert statement
        $sql = "INSERT INTO transactions (transaction_ref, date, item_sold, price_per_piece, no_sold) VALUES ($1, $2, $3, $4, $5)";
         
        if($stmt = pg_prepare($link,'stmt_insert', $sql)){
            // Set parameters
            $param_trans_ref = $trans_ref;
            $param_date = $date;
            $param_item_sold = $item_sold;
            $param_price_per_piece = $price_per_piece;
            $param_number_sold = $number_sold;

            
            // Attempt to execute the prepared statement
            if(pg_execute($link, 'stmt_insert',array($param_trans_ref, $param_date, $param_item_sold, $param_price_per_piece, $param_number_sold))){
                $trans_ref = $date = $item_sold = $price_per_piece = $number_sold="";
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
    <?php require_once("nav.php")?>

    <div class="wrapper container">
        <h2>Add Transaction Record</h2>
        <p>Please fill this form.</p>
        <form  method="post">
            <div class="form-group <?php echo (!empty($trans_ref_err)) ? 'has-error' : ''; ?>">
                <label>Transaction Ref</label>
                <input type="text" name="trans_ref" class="form-control" value="<?php echo $trans_ref; ?>" required>
                <span class="help-block"><?php echo $trans_ref_err ?></span>
            </div>    
            <div class="form-group <?php echo (!empty($date_err)) ? 'has-error' : ''; ?>">
                <label for="date">Date</label>
                <input type="date" name="date" class="form-control" value="<?php echo $date ?>" id="date">
                <span class="help-block"><?php echo $date_err; ?></span>
            </div>
            <div class="form-group <?php echo (!empty($item_sold_err)) ? 'has-error' : ''; ?>">
                <label for="item_sold">Item Sold</label>
                <input type="int" name="item_sold" class="form-control" value="<?php echo(!empty($item_sold)) ? $item_sold: 0;?>" id="item_sold">
                <span class="help-block"><?php echo $item_sold_err; ?></span>
            </div>
            <div class="form-group <?php echo (!empty($price_per_piece_err)) ? 'has-error' : ''; ?>">
                <label for="price_per_piece">Price per Piece</label>
                <input type="int" name="price_per_piece" class="form-control" value="<?php echo(!empty($price_per_piece)) ? $price_per_piece : 0;?>" id="price_per_piece">
                <span class="help-block"><?php echo $price_per_piece_err; ?></span>
            </div>
            <div class="form-group <?php echo (!empty($number_sold_err)) ? 'has-error' : ''; ?>">
                <label for="number_sold">Number Sold</label>
                <input type="int" name="number_sold" class="form-control" value="<?php echo(!empty($number_sold)) ? $number_sold: 0;?>" id="number_sold">
                <span class="help-block"><?php echo $number_sold_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Submit">
                <input type="reset" class="btn btn-default" value="Reset">
            </div>
        </form>
    </div> 
    <?php echo $toast ?>   
</body>
</html>