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
                                                                            etput_id,
                                                                            etput_tsm,
                                                                            etput_equip,
                                                                            etput_curbatch,
                                                                            etput_curloc,
                                                                            etput_curqty,
                                                                            etput_eachqty,
                                                                            etput_caseqty,
                                                                            etput_curtime,
                                                                            etput_prevtime,
                                                                            etput_prevloc,
                                                                            etput_prevbatch,                                                                           
                                                                            etput_timedif,
                                                                             etput_breaklunch,
                                                                            case when etput_difbatch = 1 then 'YES' else 'NO' end as etput_difbatch
                                                                        FROM
                                                                            printvis.elapsedtime_put
                                                                        WHERE
                                                                            etput_whse = $var_whse
                                                                                AND DATE(etput_curtime) BETWEEN '$startdatesel' AND '$enddatesel'
                                                                        ORDER BY etput_timedif desc");
$unscannedsql->execute();
$unscannedsql_array = $unscannedsql->fetchAll(pdo::FETCH_ASSOC);


foreach ($unscannedsql_array as $key => $value) {
    $row[] = array_values($unscannedsql_array[$key]);
}

$output['aaData'] = $row;
echo json_encode($output);
