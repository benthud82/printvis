<?php

include_once '../sessioninclude.php';
include '../../connections/conn_printvis.php';
if (isset($_SESSION['MYUSER'])) {
    $var_userid = $_SESSION['MYUSER'];
    $whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
    $whssql->execute();
    $whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

    $var_whse = $whssqlarray[0]['prodvisdb_users_PRIMDC'];
    $whsesel = $whssqlarray[0]['prodvisdb_users_PRIMDC'];
}
$var_packtsm = strtoupper($_GET['packtsm']);
$stardate = date('Y-m-d', strtotime("-90 days"));
$itemsql = $conn1->prepare("SELECT 
                                                            ITEMCODE,
                                                            ORD_RETURNDATE,
                                                            RETURNCODE,
                                                            CONCAT(WCSNUM, '-', WONUM) AS WCSNUM,
                                                            SHIPZONE,
                                                            BOXSIZE,
                                                            TRACERNUM,
                                                            UPPER(CASE
                                                                WHEN PICK_TSM IS NULL THEN CASEPICK_TSMNAME
                                                                ELSE PICK_TSM
                                                            END) AS PICK_TSM,
                                                            UPPER(CASE
                                                                WHEN PACK_TSMNAME IS NULL THEN CASEPICK_TSMNAME
                                                                ELSE PACK_TSMNAME
                                                            END) AS PACK_TSMNAME,
                                                            PACK_STATION,
                                                            CASE
                                                                WHEN PACK_DATE IS NULL THEN CASEPICK_DATETIME
                                                                ELSE PACK_DATE
                                                            END AS PACK_DATE,
                                                            CASE
                                                                WHEN
                                                                    (SELECT 
                                                                            tsm_name
                                                                        FROM
                                                                            printvis.tsm
                                                                        WHERE
                                                                            tsm_num = CASE
                                                                                WHEN EOLLOOSE_TSM IS NULL THEN EOLCASE_TSM
                                                                                ELSE EOLLOOSE_TSM
                                                                            END) IS NULL
                                                                THEN
                                                                    '-'
                                                                ELSE (SELECT 
                                                                        tsm_name
                                                                    FROM
                                                                        printvis.tsm
                                                                    WHERE
                                                                        tsm_num = CASE
                                                                            WHEN EOLLOOSE_TSM IS NULL THEN EOLCASE_TSM
                                                                            ELSE EOLLOOSE_TSM
                                                                        END)
                                                            END AS EOL_TSM
                                                        FROM
                                                            custaudit.complaint_detail
                                                        WHERE
                                                            ORD_RETURNDATE >= '$stardate'
                                                                AND UPPER(PACK_TSMNAME) = '$var_packtsm'
                                                        ORDER BY ORD_RETURNDATE DESC");
$itemsql->execute();
$item_array = $itemsql->fetchAll(pdo::FETCH_ASSOC);




$output = array(
    "aaData" => array()
);
$row = array();

foreach ($item_array as $key => $value) {
    $row[] = array_values($item_array[$key]);
}


$output['aaData'] = $row;
echo json_encode($output);
