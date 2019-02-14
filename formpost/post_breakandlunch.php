
<?php
include '../../CustomerAudit/connection/connection_details.php';


$var_userid = $_SESSION['MYUSER'];
$whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);
$whsesel = $whssqlarray[0]['prodvisdb_users_PRIMDC'];

if ($whsesel == 3) {
    $building = 2;
} else {
    $building = 1;
}

$newequip = ($_POST['newequip']);
$batchid = intval($_POST['batchid']);





$sql = "UPDATE printvis.case_batchequip SET caseequip_equipment='$newequip' WHERE caseequip_whse='$whsesel' and caseequip_build='$building' and caseequip_batch= $batchid;";
$query = $conn1->prepare($sql);
$query->execute();
