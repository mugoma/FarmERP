<?php phpinfo()?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Reports</title>
        <?php require_once (realpath(dirname(__FILE__) . '/..'. '/..') ."/"."include/header.php")?>
        <style type="text/css">
            body{ font: 14px sans-serif; }
        </style>
    </head>
    <body>
        <?php require_once(realpath(dirname(__FILE__) . '/..'. '/..') ."/"."include/nav_1.php")?>
        <main>
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
        </main>
        <?php require_once(realpath(dirname(__FILE__) . '/..'. '/..') ."/"."include/footer.php")?>

        <?php echo $toast?>    
    </body>
</html>