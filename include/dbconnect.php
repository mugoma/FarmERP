<?php

if (preg_match('(config.php)', $_SERVER['PHP_SELF'])) {
    Header("Location: error.php?err=01");
    exit();
}
/* Database credentials. */
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'postgres');
define('DB_PASSWORD', 'admin');
define('DB_NAME', 'yengas2_farmerp');
 
/* Attempt to connect to PostgreSQL database */
$link = pg_connect("host=".DB_SERVER." user=".DB_USERNAME." password=".DB_PASSWORD." dbname=".DB_NAME) or die("Could not connect");
 
// Check connection
if($link === false){
    die("ERROR: Could not connect. " . pg_connection_status($link));
}
pg_query($link, "SET TIMEZONE='Africa/Nairobi';");
?>