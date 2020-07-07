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
                                                                            etcomb_id,
                                                                            etcomb_tsm,
                                                                            etcomb_equip,
                                                                            etcomb_curbatch,
                                                                            etcomb_curloc,
                                                                            etcomb_curqty,
                                                                            etcomb_eachqty,
                                                                            etcomb_caseqty,
                                                                            etcomb_curtime,
                                                                            etcomb_prevtime,
                                                                            etcomb_prevloc,
                                                                            etcomb_prevbatch,                                                                           
                                                                            etcomb_timedif,
                                                                             etcomb_breaklunch,
                                                                             etcomb_path,
                                                                            case when etcomb_difbatch = 1 then 'YES' else 'NO' end as etcomb_difbatch
                                                                        FROM
                                                                            printvis.elapsedtime_comb
                                                                        WHERE
                                                                            etcomb_whse = $var_whse
                                                                                AND DATE(etcomb_curtime) BETWEEN '$startdatesel' AND '$enddatesel'
                                                                        ORDER BY etcomb_timedif desc");
$unscannedsql->execute();
$unscannedsql_array = $unscannedsql->fetchAll(pdo::FETCH_ASSOC);


foreach ($unscannedsql_array as $key => $value) {
    $row[] = array_values($unscannedsql_array[$key]);
}

$output['aaData'] = $row;
echo json_encode($output);
