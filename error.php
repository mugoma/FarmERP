<!DOCTYPE html>
<html>
<head>
<?php
$a='
</head>
<body>';
http_response_code(403);

switch ($_REQUEST['err']) {

    case "1":
        echo(
            "<title>Access Not Allowed</title>$a.
            <h1>Access Not Allowed</h1>
            <p>Your are not allowed to access this page</p>"
        );
        break;
    case "2":

        echo(
            "<title>403 Error</title>$a
            <h1>403 Error</h1>
            <p>Your do not have permission to access this page. Kindly request for addition of necessary priviledges.</p>"
        );
    break;
    default:
        echo(
            "<title>Error</title>'$a
            <h1>Error</h1>
            <p>Your are not allowed to access this page</p>"
        );        
    break;
}
?>