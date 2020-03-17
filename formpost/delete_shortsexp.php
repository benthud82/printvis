<?php
include_once '../sessioninclude.php';
include '../../connections/conn_printvis.php';
// Set timezone 
date_default_timezone_set('America/New_York'); 
if (isset($_SESSION['MYUSER'])) {
    $var_userid = $_SESSION['MYUSER'];
    $whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
    $whssql->execute();
    $whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

    $var_whse = $whssqlarray[0]['prodvisdb_users_PRIMDC'];
}

$var_deletearray = ($_POST['deletearray']);
$today = date('Y-m-d H-i-s');
foreach ($var_deletearray as $key => $value) {
    $loc = ($var_deletearray[$key][0]);
    $sql = "INSERT INTO printvis.delete_shortsexp (iddelete_shortsexp, delete_whse, delete_loc, delete_datetime) VALUES (0,$var_whse, '$loc','$today');";
    $query = $conn1->prepare($sql);
    $query->execute();
}




