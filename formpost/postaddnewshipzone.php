
<?php

include_once '../../connections/conn_printvis.php';
include_once '../sessioninclude.php';
$var_userid = strtoupper($_SESSION['MYUSER']);
$whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE UPPER(prodvisdb_users_ID = '$var_userid')");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

$var_whse = intval($whssqlarray[0]['prodvisdb_users_PRIMDC']);

$addshipzone_shipzone = ($_POST['addshipzone_shipzone']);
$addshipzone_print = intval($_POST['addshipzone_print']);
$addshipzone_truck = intval($_POST['addshipzone_truck']);

$cutoff_group = 'NA';

//count current number of ship zones for ranking
$shipcount = $conn1->prepare("SELECT count(*) as SHIPCOUNT FROM printvis.printcutoff WHERE cutoff_DC = $var_whse");
$shipcount->execute();
$shipcount_array = $shipcount->fetchAll(pdo::FETCH_ASSOC);

$newrank = intval($shipcount_array[0]['SHIPCOUNT']) + 1;

$columns = 'cutoff_DC, cutoff_zone, cutoff_time, cutoff_truck, cutoff_group, cutoff_rank';
$values = "$var_whse, '$addshipzone_shipzone', $addshipzone_print, $addshipzone_truck, '$cutoff_group', $newrank";


$sql = "INSERT IGNORE INTO printvis.printcutoff ($columns) VALUES ($values) ";
$query = $conn1->prepare($sql);
$query->execute();


//update badges
include '../datapull/badges_updateshipzone.php';

