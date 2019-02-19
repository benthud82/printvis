<?php

include_once '../../connections/conn_printvis.php';
include '../sessioninclude.php';
include '../functions/functions_totetimes.php';
if (isset($_SESSION['MYUSER'])) {
    $var_userid = $_SESSION['MYUSER'];
    $whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
    $whssql->execute();
    $whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

    $var_whse = $whssqlarray[0]['prodvisdb_users_PRIMDC'];

    $startdatesel = date("Y-m-d", strtotime($_GET['startdatesel']));  //pulled from heatmap.php
    $enddatesel = date("Y-m-d", strtotime($_GET['enddatesel']));  //pulled from heatmap.php
}

$unscannedsql = $conn1->prepare("SELECT 
                                                                casevol_availdate,
                                                                casevol_availhour,
                                                                casevol_equip,
                                                                casevol_lines,
                                                                casevol_cube
                                                            FROM
                                                                printvis.hist_casevol_summary
                                                            WHERE
                                                                casevol_whse = $var_whse and 
                                                                casevol_availdate BETWEEN '$startdatesel' AND '$enddatesel'");
$unscannedsql->execute();
$unscannedsql_array = $unscannedsql->fetchAll(pdo::FETCH_ASSOC);


foreach ($unscannedsql_array as $key => $value) {
    $row[] = array_values($unscannedsql_array[$key]);
}

$output['aaData'] = $row;
echo json_encode($output);
