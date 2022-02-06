<?php
// Include config file
require_once(realpath(dirname(__FILE__) . '/..'.'/..') ."/"."config.php");
redirecttologin($_SERVER['PHP_SELF']);
checkpermissions(array(2));


$worker_sql="SELECT erp_workers.id, erp_workers.surname erp_workers.other_name FROM erp_workers WHERE (erp_workers.active='true');";
$worker_query=pg_query($link, $worker_sql);
$worker_list=pg_fetch_all($worker_query);
 
// Define variables and initialize with empty values
$worker_id = "";
$worker_id_err="";

// producting form data when form is submitted


if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(empty(test_input($_REQUEST['worker_id']))){
        $worker_id_err='Please Select A worker';
    }else{
        $worker_id=test_input($_REQUEST['worker_id']);

        pg_prepare($link, 'check_worker_exists', "SELECT * FROM erp_workers WHERE (id = $1 AND active='true');");
        $check_worker_exists=pg_execute($link, 'check_worker_exists', array($worker_id));
        if (pg_num_rows($check_worker_exists)!=1){
            $worker_id_err="Error Retrieving The Submitted worker";
        }
    }

    
    // Check input errors before inserting in database
    if(empty($worker_id_err) ){
        if($stmt = pg_prepare($link,'stmt_insert', "UPDATE  erp_workers SET active='false' WHERE id=$1;")){            
            // Attempt to execute the prepared statement
            if(pg_execute($link, 'stmt_insert',array($worker_id))){
                $worker_id_="";
                $worker_query=pg_query($link, $worker_sql);
                $worker_list=pg_fetch_all($worker_query);

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
        <title>Delete Worker<?=" | Forms | ".SOFTWARE_NAME ?></title>
        <?php require_once(realpath(dirname(__FILE__) .'/..'.'/..') ."/"."include/header.php")?>
        <style type="text/css">
            body{ font: 14px sans-serif; }
        </style>
    </head>
    <body>
        <?php require_once (realpath(dirname(__FILE__) . '/..'.'/..') ."/"."nav.php");?>
        <main>
            <div class="wrapper container">
                <h2>Delete Worker</h2>
                <p>Please fill this form.</p>
                <form  id='form' method="post">
                    <div class="form-group ">
                        <label for='worker'>Worker</label>
                        <select class="form-control select_multiple <?= (!empty($worker_id_err)) ? 'is-invalid' : ''; ?>" id="worker" name="worker_id" required>
                            <option value='' disabled='disabled' <?= (!empty($worker_id)) ? '' : 'selected'; ?>>Please select a worker</option>";

                            <?php
                                $x=0;
                                foreach($worker_list as $worker_int){
                                    if (($worker_int['id']==$worker_id)) {
                                        echo "<option value='".$worker_int['id']."' selected >".$worker_int['surname'].", ".$worker_int['other_names']."</option>";
                                    }
                                    else {
                                        echo "<option value='".$worker_int['id']."'>".$worker_int['surname'].", ".$worker_int['other_names']."</option>";
                                    }
                                    $x++;
                                    
                                }
                            ?>
                        </select>
                        <span class="invalid-feedback"><?= $worker_id_err ?></span>
                    </div>
                    <div class="form-group">
                        <input type="button" class="btn btn-primary" value="Submit" data-toggle="modal" data-target="#confirmdeletemodal" >
                        <input type="reset" class="btn btn-default" value="Reset">
                    </div>
                </form>
            </div> 
        </main>
        <?php require_once(realpath(dirname(__FILE__) . '/..'. '/..') ."/"."include/footer.php")?>

        <div class="modal fade" id="confirmdeletemodal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Confirm Worker Deletion</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are You Sure You Want To Delete This Worker?</p>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="document.getElementById('form').submit()">Confirm Delete</button>
            </div>
            </div>
        </div>
    </div>


    </body>
</html>