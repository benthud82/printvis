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
    $enddatesel = date("Y-m-d", strtotime($_GET['enddatesel']));
    $tsm = $_GET['sel_tsm'];
//pulled from heatmap.php
}

$rectrans = $conn1->prepare("SELECT 
                                                                            
                                                                            comp_rec_tsm,
                                                                            tsm_name,
                                                                            comp_rec_dci,
                                                                            comp_rec_type,
                                                                            comp_rec_location,
                                                                            comp_rec_transqty,
                                                                            comp_rec_casehandle,
                                                                            comp_rec_eachhandle,
                                                                            comp_rec_datetime
                                                                                                                                                     
                                                                        FROM
                                                                            printvis.completed_receipts
                                                                            LEFT JOIN
                                                                            printvis.tsm ON comp_rec_tsm = tsm_num
                                                                        WHERE
                                                                            comp_rec_whse = $var_whse
                                                                                AND DATE(comp_rec_datetime) BETWEEN '$startdatesel' AND '$enddatesel'
                                                                                AND COMP_REC_TSM = $tsm    
                                                                        ORDER BY comp_rec_tsm DESC");
$rectrans->execute();
$rectrans_array = $rectrans->fetchAll(pdo::FETCH_ASSOC);


foreach ($rectrans_array as $key => $value) {
    $row[] = array_values($rectrans_array[$key]);
}

$output['aaData'] = $row;
echo json_encode($output);



