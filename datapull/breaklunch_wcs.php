<?php

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../../connections/conn_printvis.php';
include_once '../../globalincludes/usa_asys.php';
//include_once '../globalincludes/newcanada_asys.php';


$result1 = $aseriesconn->prepare("SELECT 
                                                                        TRIM(SUBSTR(A.NVFLAT, 46, 10)) AS TSM,
                                                                        TRIM(SUBSTR(A.NVFLAT, 3, 2)) AS WHSE,
                                                                        TRIM(SUBSTR(A.NVFLAT, 111, 19)) AS STARTTIME,
                                                                        TRIM(SUBSTR(A.NVFLAT, 12, 6)) as TYPE
                                                                    FROM
                                                                        HSIPCORDTA.NOFNVI A
                                                                    WHERE
                                                                        TRIM(SUBSTR(A.NVFLAT, 111, 10)) <> ' '
                                                                            AND TRIM(SUBSTR(A.NVFLAT, 111, 10)) = CURDATE()
                                                                            AND TRIM(SUBSTR(A.NVFLAT, 12, 6)) IN ('PCKBRK' , 'PCKLUN')");
$result1->execute();
$mindaysarray = $result1->fetchAll(pdo::FETCH_ASSOC);


$columns = 'blwcs_tsm, blwcs_whse, blwcs_datetime, blwcs_type, blwcs_nvtype';


$values = array();

$maxrange = 3999;
$counter = 0;
$rowcount = count($mindaysarray);

do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }

    $data = array();
    $values = array();
    while ($counter <= $maxrange) { //split into 5,000 lines segments to insert into merge table
        $blwcs_tsm = $mindaysarray[$counter]['TSM'];
        $blwcs_whse = intval($mindaysarray[$counter]['WHSE']);
        $blwcs_datetime = $mindaysarray[$counter]['STARTTIME'];
        $blwcs_type = $mindaysarray[$counter]['TYPE'];
        if ($blwcs_type == 'PCKBRK') {
            $type = 'BREAK';
            $nv_type = 'J-715';
        } else {
            $type = 'LUNCH';
            $nv_type = 'J-730';
        }

        $data[] = "('$blwcs_tsm', $blwcs_whse, '$blwcs_datetime', '$type', '$nv_type')";
        $counter += 1;
    }


    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO printvis.breaklunch_wcs ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange += 4000;
} while ($counter <= $rowcount);



$sql = "INSERT IGNORE into printvis.breaklunch_combined SELECT 
    *
FROM
    printvis.breaklunch 
UNION SELECT 
    *
FROM
    printvis.breaklunch_wcs";
$query = $conn1->prepare($sql);
$query->execute();

