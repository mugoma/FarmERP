<?php
require_once (realpath(dirname(__FILE__) . '/..') ."/"."config.php");
redirecttologin($_SERVER['PHP_SELF'])

?>
<!DOCTYPE html>
<html>
    <head>
        <title><?= "Dashboard | Forms | ".SOFTWARE_NAME ?> </title>
        <?php require_once(realpath(dirname(__FILE__) . '/..') ."/"."include/header.php")?>

    </head>
    <body>
    <?php require_once (realpath(dirname(__FILE__) . '/..') ."/"."nav.php");?>

        <main>
            <div class='container'>
                <?php include_once("dashboard.php")?>
            </div>
        </main>
        <?php require_once(realpath(dirname(__FILE__) . '/..') ."/"."include/footer.php")?>
    </body>
</html>