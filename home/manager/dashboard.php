<?php 
session_start();
if(!$_SESSION["loggedin"]){
    $_SESSION["login_redirect"]=true;
    header("location: ".'/auth/login.php');
};
/*if (preg_match('(dashboard.php)', $_SERVER['PHP_SELF'])) {
    Header("Location: /error.php?err=01");
    exit();
}*/
if($delegation = strtolower($_SESSION["delegation"]) != 'manager'){
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
            <section>
                <div class='container-fluid'>
                    <div class='col-sm-12 col-lg-8 offset-lg-2'>
                        <h1>FarmErp Manager Dashboard</h1>
                        <p>Welcome, <span class="text-primary"><?php echo $_SESSION['username']; ?></span>. Today is <?php echo date("Y-m-d") ; ?></p>
                    </div>
                </div>
            </section>
        </main>

    </body>
</html>
