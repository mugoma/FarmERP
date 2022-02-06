<?php

    if (preg_match('(header.php)', $_SERVER['PHP_SELF'])) {
		Header("Location: /error.php?err=01");
		exit();
    }

?>
<!DOCTYPE html>
    <head>
        <!-- Required meta tags -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <!-- Bootstrap StyleSheets -->
        <link rel='stylesheet' href="<?= '/static/bootstrap/css/bootstrap.min.css'?>">
        <link rel='stylesheet' href="<?= '/static/bootstrap/css/bootstrap-grid.min.css'?>">

        <link rel='stylesheet' href="<?= '/static/bootstrap/css/bootstrap-reboot.min.css'?>">
        <link rel='stylesheet' href="<?= '/static/bootstrap/css/select2.min.css'?>">

        <link rel='stylesheet' href="<?= '/static/bootstrap/css/style.css'?>">
        <!-- End Bootstrap Stylesheets -->


        <!-- Bootstrap Scripts-->

        <script src="<?= '/static/bootstrap/js/jquery-3.5.1.min.js'?>"></script>
        <script src="<?= '/static/bootstrap/js/bootstrap.min.js'?>"></script>

        <script src="<?= '/static/bootstrap/js/bootstrap.bundle.min.js'?>"></script>
        <script src="<?= '/static/bootstrap/js/select2.min.js'?>"></script>
        <script src="<?= '/static/bootstrap/js/script.js'?>"></script>

        <!-- End Bootstrap Scripts-->

    </head>

