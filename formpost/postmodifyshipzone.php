
<?php

include_once '../../connections/conn_printvis.php';
include_once '../sessioninclude.php';
$var_userid = strtoupper($_SESSION['MYUSER']);
$whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE UPPER(prodvisdb_users_ID = '$var_userid')");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

$var_whse = intval($whssqlarray[0]['prodvisdb_users_PRIMDC']);

$modify_shipzone = ($_POST['modify_shipzone']);
$modify_print = intval($_POST['modify_print']);
$modify_truck = intval($_POST['modify_truck']);


$sql = "UPDATE printvis.printcutoff SET cutoff_time = '$modify_print',  cutoff_truck = '$modify_truck' WHERE cutoff_zone = '$modify_shipzone' and cutoff_DC = $var_whse";
$query = $conn1->prepare($sql);
$query->execute();

