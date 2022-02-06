<?php 
session_start();
if(!$_SESSION["loggedin"]){
    $_SESSION["login_redirect"]=true;
    header("location: /auth/login.php");
};
/*if (preg_match('(dashboard.php)', $_SERVER['PHP_SELF'])) {
    Header("Location: /error?err=01");
    exit();
}*/
if(strtolower($_SESSION["delegation"]) != 'employee'){
    header("location: /home/index.php");
};
?>

<!DOCYTPE html>
<html lang="en-ke">
    <head>
        <meta charset="UTF-8">
        <title>Dashboard</title>
        <?php require_once (realpath(dirname(__FILE__) . '/..'. '/..') ."/"."include/header.php")?>

    </head>
    <body>
        <?php require_once ("nav.php")?>
        <main>
            <section class='section'>
                <div class='container-fluid'>
                    <div class='row'>
                        <div class='col-sm-12 col-lg-8 offset-lg-2'>
                            <h1>FarmErp Employee Dashboard</h1>
                            <h2>Welcome, <span class='text-primary'><?php echo $_SESSION['username']; ?></span>. Today is <?php echo date("Y-m-d") ; ?></h2>
                        </div>
                    </div>
                </div>
            </section>
        </main>

    </body>
</html>
