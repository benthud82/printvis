<?php

include_once '../sessioninclude.php';
include '../../connections/conn_printvis.php';
include 'custcomp_tsm_sql.php';
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
    cd.ITEMCODE,
    cd.ORD_RETURNDATE,
    cd.RETURNCODE,
    CONCAT(cd.WCSNUM, '-', cd.WONUM) AS WCSNUM,
    cd.SHIPZONE,
    cd.BOXSIZE,
    cd.TRACERNUM,
    $complaint_pick_tsm_sql AS PICK_TSM,
    $complaint_pack_tsm_sql AS PACK_TSMNAME,
    cd.PACK_STATION,
    IFNULL(cd.PACK_DATE, cd.CASEPICK_DATETIME) AS PACK_DATE,
    IFNULL(eol_tsm.tsm_name, '-') AS EOL_TSM,
    cd.SEQNUM,
    cd.DC_CODE
FROM
    custaudit.complaint_detail cd
    $complaint_tsm_joins
    LEFT JOIN printvis.tsm eol_tsm ON eol_tsm.tsm_num = IFNULL(cd.EOLLOOSE_TSM, cd.EOLCASE_TSM)
WHERE
    cd.ORD_RETURNDATE >= '$stardate'
    AND $complaint_pack_tsm_sql = '$var_packtsm'
ORDER BY cd.ORD_RETURNDATE DESC");
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
