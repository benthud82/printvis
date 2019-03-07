
<?php

include '../sessioninclude.php';
include '../../connections/conn_printvis.php';


$var_userid = $_SESSION['MYUSER'];
$whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);
$whsesel = $whssqlarray[0]['prodvisdb_users_PRIMDC'];

if (isset($_POST['building'])) {
    $building = intval($_POST['building']);
} else {
    echo '';
}

$count_pj = intval($_POST['count_pj']);
$count_belt = intval($_POST['count_belt']);
$count_op = intval($_POST['count_op']);




$sql = "UPDATE printvis.case_equipmentcount SET equipcount_count=$count_pj WHERE equipcount_whse=$whsesel and equipcount_build=$building and equipcount_equipment='PALLETJACK';";
$query = $conn1->prepare($sql);
$query->execute();

$sql = "UPDATE printvis.case_equipmentcount SET equipcount_count=$count_belt WHERE equipcount_whse=$whsesel and equipcount_build=$building and equipcount_equipment='BELTLINE';";
$query = $conn1->prepare($sql);
$query->execute();

$sql = "UPDATE printvis.case_equipmentcount SET equipcount_count=$count_op WHERE equipcount_whse=$whsesel and equipcount_build=$building and equipcount_equipment='ORDERPICKER';";
$query = $conn1->prepare($sql);
$query->execute();
