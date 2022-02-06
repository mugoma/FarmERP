<?php 
require_once (realpath(dirname(__FILE__) . '/..') ."/"."config.php");
redirecttologin($_SERVER['PHP_SELF']);
/*
session_start();
if($_SESSION['loggedin'] && $delegation = $_SESSION['delegation']){
    header("location:/forms");
}else {
    header('location: /auth/logout.html');
};

if (preg_match('(index.php)', $_SERVER['PHP_SELF'])) {
    Header("Location: /error.php?err=01");
    exit();
}
*/
?>
<?php

?>
<!DOCTYPE html>
<html>
    <head>
        <title><?= "Dashboard | Auth | ".SOFTWARE_NAME ?> </title>
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