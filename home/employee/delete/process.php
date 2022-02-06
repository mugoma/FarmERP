<?php
session_start();
if(!$_SESSION["loggedin"]){
    $_SESSION["login_redirect"]=true;
    header("location: /auth/login.html?next=".substr($_SERVER['PHP_SELF'], 0, -3).'html' );
};
/*
if (preg_match('(registration.php)', $_SERVER['PHP_SELF'])) {
    Header("Location: error.php?err=01");
    exit();
}*/
// Include config file
require_once(realpath(dirname(__FILE__) . '/..'.'/..'.'/..') ."/"."config.php");

$process_sql="SELECT erp_farm_process.id, erp_farm_process.name, FROM erp_farm_process WHERE (erp_farm_process.active='true');";
$process_query=pg_query($link, $process_sql);
$process_list=pg_fetch_all($process_query);
 
// Define variables and initialize with empty values
$process_id = "";
$process_id_err="";

// Processing form data when form is submitted


if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(empty(test_input($_REQUEST['process_id']))){
        $process_id_err='Please Select A Process';
    }else{
        $process_id=test_input($_REQUEST['process_id']);

        pg_prepare($link, 'check_process_exists', "SELECT * FROM erp_farm_process WHERE id = $1 AND active='true');");
        $check_process_exists=pg_execute($link, 'check_process_exists', array($process_id));
        if (pg_num_rows($check_process_exists)!=1){
            $process_id_err="Error Retrieving The Submitted Process";
        }
    }

    
    // Check input errors before inserting in database
    if(empty($process_id_err) ){
        if($stmt = pg_prepare($link,'stmt_insert', "UPDATE  erp_farm_process SET active='false' WHERE id=$1;")){            
            // Attempt to execute the prepared statement
            if(pg_execute($link, 'stmt_insert',array($process_id))){
                $process_id_="";
                $process_query=pg_query($link, $process_sql);
                $process_list=pg_fetch_all($process_query);

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
        <title>Delete Process | Yengas FarmERP</title>
        <?php require_once (realpath(dirname(__FILE__) . '/..'. '/..'.'/..') ."/"."include/header.php")?>
        <style type="text/css">
            body{ font: 14px sans-serif; }
        </style>
    </head>
    <body>
        <?php require_once(realpath(dirname(__FILE__) . '/..'. '/..') ."/"."employee/nav.php")?>
        <main>
            <div class="wrapper container">
                <h2>Delete Process</h2>
                <p>Please fill this form.</p>
                <form  id='form' method="post">
                    <div class="form-group ">
                        <label for='process'>Process</label>
                        <select class="form-control select_multiple <?php echo (!empty($process_id_err)) ? 'is-invalid' : ''; ?>" id="process" name="process_id" required>
                            <option value='' disabled='disabled' <?php echo (!empty($process_id)) ? '' : 'selected'; ?>>Please select a process</option>";

                            <?php
                                $x=0;
                                foreach($process_list as $process_int){
                                    if (($process_int['id']==$process_id)) {
                                        echo "<option value='".$process_int['id']."' selected >".$process_int['name']."</option>";
                                    }
                                    else {
                                        echo "<option value='".$process_int['id']."'>".$process_int['name']."</option>";
                                    }
                                    $x++;
                                    
                                }
                            ?>
                        </select>
                        <span class="invalid-feedback"><?= $process_id_err ?></span>
                    </div>
                    <div class="form-group">
                        <input type="button" class="btn btn-primary" value="Submit" data-toggle="modal" data-target="#confirmdeletemodal" >
                        <input type="reset" class="btn btn-default" value="Reset">
                    </div>
                </form>
            </div> 
        </main>
        <div class="modal fade" id="confirmdeletemodal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Confirm Process Deletion</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are You Sure You Want To Delete This Process?</p>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="document.getElementById('form').submit()">Delete</button>
            </div>
            </div>
        </div>
    </div>
        <?php require_once(realpath(dirname(__FILE__) . '/..'. '/..'. '/..') ."/"."include/footer.php")?>


    </body>
</html>