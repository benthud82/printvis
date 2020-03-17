<?php

// Set timezone 
date_default_timezone_set('America/New_York');
include_once '../sessioninclude.php';
include '../../connections/conn_printvis.php';
if (isset($_SESSION['MYUSER'])) {
    $var_userid = $_SESSION['MYUSER'];
    $whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
    $whssql->execute();
    $whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

    $var_whse = $whssqlarray[0]['prodvisdb_users_PRIMDC'];
}


$today = date('Y-m-d H:i:s', strtotime("-60 minutes"));


$sql = "DELETE FROM printvis.delete_shortsexp WHERE delete_datetime < '$today'";
$query = $conn1->prepare($sql);
$query->execute();





