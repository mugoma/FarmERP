<?php

if (preg_match('(config.php)', $_SERVER['PHP_SELF'])) {
    Header("Location: error.php?err=01");
    exit();
}

define( "PATH_SEP"          , (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? '\\' : '/');
define( "PATH_BASE"          , realpath(dirname(__FILE__))   . PATH_SEP);
//define( "PATH_BASE"          , getcwd() . PATH_SEP);
define( "STATIC_URL"          , 'static/');
define( "BOOTSTRAP_URL"          , STATIC_URL.'bootstrap/');
define( "BS_CSS_URL"          , BOOTSTRAP_URL.'css/');
define( "BS_JS_URL"          , BOOTSTRAP_URL.'js/');
define( "CUSTOM_URL"          , STATIC_URL.'custom/');
define( "CUSTOM_CSS_URL"          , CUSTOM_URL.'css/');
define( "CUSTOM_JS_URL"          , CUSTOM_URL.'js/');
define( "SOFTWARE_NAME"          , 'Yengas FarmERP');
date_default_timezone_set('Africa/Nairobi');

$required_reminder="<p>The field(s) marked with asterisk(*) are required.</p>";


require_once ("include/dbconnect.php");
require_once ("common.php");


?>