<?php
// Include config file
require_once(realpath(dirname(__FILE__) . '/..'.'/..') ."/"."config.php");
redirecttologin($_SERVER['PHP_SELF']);
checkpermissions(array(2));


$retail_sql="SELECT * FROM erp_retail_unit WHERE (active='true');";
$retail_query=pg_query($link, $retail_sql);
$retail_list=pg_fetch_all($retail_query);
 
// Define variables and initialize with empty values
$retail_id = "";
$retail_id_err="";

// producting form data when form is submitted


if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(empty(test_input($_REQUEST['retail_id']))){
        $retail_id_err='Please Select A Retail Unit';
    }else{
        $retail_id=test_input($_REQUEST['retail_id']);

        pg_prepare($link, 'check_retail_exists', "SELECT * FROM erp_retail_unit WHERE (id = $1 AND active='true');");
        $check_retail_exists=pg_execute($link, 'check_retail_exists', array($retail_id));
        if (pg_num_rows($check_retail_exists)!=1){
            $retail_id_err="Error retrieving the submitted retail.";
        }
    }

    
    // Check input errors before inserting in database
    if(empty($retail_id_err) ){
        if($stmt = pg_prepare($link,'stmt_insert', "UPDATE  erp_retail_unit SET active='false' WHERE id=$1;")){            
            // Attempt to execute the prepared statement
            if(pg_execute($link, 'stmt_insert',array($retail_id))){
                $retail_id_="";
                $retail_query=pg_query($link, $retail_sql);
                $retail_list=pg_fetch_all($retail_query);

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
        <title>Delete Retail Unit | Forms | <?= SOFTWARE_NAME ?></title>
        <?php require_once(realpath(dirname(__FILE__) .'/..'.'/..') ."/"."include/header.php")?>
        <style type="text/css">
            body{ font: 14px sans-serif; }
        </style>
    </head>
    <body>
        <?php require_once (realpath(dirname(__FILE__) . '/..'.'/..') ."/"."nav.php");?>
        <main>
            <div class="wrapper container">
                <h2>Delete Retail Unit</h2>
                <p>Please fill this form.</p>
                <form  id='form' method="post">
                    <div class="form-group ">
                        <label for='retail-unit'>Retail Unit</label>
                        <select class="form-control select_multiple <?= (!empty($retail_id_err)) ? 'is-invalid' : ''; ?>" id="retail-unit" name="retail_id" required>
                            <option value='' disabled='disabled' <?= (!empty($retail_id)) ? '' : 'selected'; ?>>Please select a retail shop</option>";

                            <?php foreach($retail_list as $retail_int){?>
                            <option value='<?= $retail_int['id']?>' <?= ($retail_int['id']==$retail_id)?"selected":"" ?>><?$retail_int['name']?></option>       
                            <?php }?>
                        </select>
                        <span class="invalid-feedback"><?= $retail_id_err ?></span>
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
                <h5 class="modal-title" id="exampleModalLabel">Confirm Retail Deletion</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are You Sure You Want To Delete This Retail Shop?</p>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="document.getElementById('form').submit()">Confirm Delete</button>
            </div>
            </div>
        </div>
    </div>


    </body>
</html>