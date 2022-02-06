<!DOCTYPE html>
<?php
session_start();
if ($_SESSION['loggedin'] && $delegation = $_SESSION['admin']) {
}elseif($_SESSION['loggedin'] && $delegation = $_SESSION['delegation']){
    header("location: /home/$delegation/dashboard.php");
}else {
    header('location: /auth/logout');
};

?>