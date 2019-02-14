<?php

include '../../globalincludes/usa_asys.php';
include '../../connections/conn_printvis.php';
ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../functions/functions_totetimes.php';
//include '../../globalfunctions/custdbfunctions.php';

$whsearray = array(6, 3, 7, 9, 2);
$yesterday = date('Y-m-d', strtotime("-4 days"));

//Batch start times
$casebatchstart = $aseriesconn->prepare("SELECT TRIM(substr(NVCFLT,3,2)) as WHSE,
                                                                                       TRIM(substr(NVCFLT,7,5)) as BATCH,
                                                                                       TRIM(substr(NVCFLT,49,10)) as TSM,
                                                                                       TRIM(substr(NVCFLT,21,9)) as LPNUM, 
                                                                                       TRIM(substr(NVCFLT,146,19)) as PICK_DATETIME 
                                                                            FROM HSIPCORDTA.NOFCAS    
                                                                            WHERE TRIM(substr(NVCFLT,146,10)) >= '$yesterday'");
$casebatchstart->execute();
$casebatchstart_array = $casebatchstart->fetchAll(pdo::FETCH_ASSOC);
$casestartcols = 'caselp_whse, caselp_batch, caselp_tsm, caselp_lp, caselp_pickdatetime';



$values = array();
$maxrange = 3999;
$counter = 0;
$rowcount = count($casebatchstart_array);

do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }


    $data = array();
    $values = array();
    while ($counter <= $maxrange) { //split into 4,000 lines segments to insert into merge table
        $caselp_whse = intval($casebatchstart_array[$counter]['WHSE']);
        $caselp_batch = intval($casebatchstart_array[$counter]['BATCH']);
        $caselp_tsm = intval($casebatchstart_array[$counter]['TSM']);
        $caselp_lp = intval($casebatchstart_array[$counter]['LPNUM']);
        $caselp_pickdatetime = $casebatchstart_array[$counter]['PICK_DATETIME'];

        $data[] = "($caselp_whse, $caselp_batch, $caselp_tsm, $caselp_lp, '$caselp_pickdatetime')";
        $counter += 1;
    }

    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }

    $sql5 = "INSERT IGNORE INTO printvis.caselp_hist ($casestartcols) VALUES $values";
    $query5 = $conn1->prepare($sql5);
    $query5->execute();


    $maxrange += 4000;
} while ($counter <= $rowcount); //end of do loop for tote times









