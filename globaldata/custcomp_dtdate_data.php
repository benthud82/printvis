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
$var_date = date('Y-m-d', strtotime($_GET['startdate']));
$itemsql = $conn1->prepare("SELECT
    cd.ITEMCODE,
    cd.ORD_RETURNDATE,
    cd.RETURNCODE,
    CONCAT(cd.WCSNUM, '-', cd.WONUM) AS WCSNUM,
    cd.SHIPZONE,
    cd.BOXSIZE,
    cd.TRACERNUM,
    cd.BATCH_NUM,
    UPPER(IFNULL(cd.PICK_TSM, cd.CASEPICK_TSMNAME)) AS PICK_TSM,
    UPPER(IFNULL(cd.PACK_TSMNAME, cd.CASEPICK_TSMNAME)) AS PACK_TSMNAME,
    cd.PICK_LOCATION,
    IFNULL(cd.PICK_DATE, cd.CASEPICK_DATETIME) AS PICK_DATE,
    IFNULL(t.tsm_name, '-') AS EOL_TSM
FROM
    custaudit.complaint_detail cd
    LEFT JOIN printvis.tsm t ON t.tsm_num = IFNULL(cd.EOLLOOSE_TSM, cd.EOLCASE_TSM)
WHERE
    cd.ORD_RETURNDATE = '$var_date'
    AND cd.PICK_WHSE = $var_whse
ORDER BY
    cd.ORD_RETURNDATE DESC
");
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
