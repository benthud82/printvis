
<?php

include '../sessioninclude.php';
include '../../CustomerAudit/connection/connection_details.php';
$var_userid = $_SESSION['MYUSER'];
$whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);
$whsesel = $whssqlarray[0]['prodvisdb_users_PRIMDC'];
if ($whsesel == 3) {
    $building = 2;
} else {
    $building = 1;
}
session_write_close();

$result1 = $conn1->prepare("SELECT 
                                                            SUBDATE(ORD_RETURNDATE,
                                                            WEEKDAY(ORD_RETURNDATE)) AS WEEKSTARTING,
                                                                SUM(CASE
                                                                    WHEN RETURNCODE = 'WQSP' THEN 1
                                                                    ELSE 0
                                                                END) AS SUM_WQSP,
                                                                SUM(CASE
                                                                    WHEN RETURNCODE = 'IBNS' THEN 1
                                                                    ELSE 0
                                                                END) AS SUM_IBNS,
                                                                SUM(CASE
                                                                    WHEN RETURNCODE = 'WISP' THEN 1
                                                                    ELSE 0
                                                                END) AS SUM_WISP, 
                                                                count(*) as TOTCOUNT
                                                            FROM
                                                                custaudit.custreturns
                                                            WHERE
                                                                WEEKDAY(ORD_RETURNDATE) NOT IN (5 , 6)
                                                                    AND WHSE = $whsesel
                                                                    AND RETURNCODE IN ('IBNS' , 'WISP', 'WQSP')
                                                                    AND ORD_RETURNDATE >= NOW() - INTERVAL 7 DAY");
$result1->execute();


$rows1 = array();
$rows1['name'] = 'WQSP';
$rows2 = array();
$rows2['name'] = 'WISP';
$rows3 = array();
$rows3['name'] = 'IBNS';


foreach ($result1 as $row) {
    $rows1['data'] = ($row['SUM_WQSP']) * 1;  
    $rows2['data'] =  ($row['SUM_WISP']) * 1;  
    $rows3['data'] =  ($row['SUM_IBNS']) * 1;  


}



$result = array();

array_push($result, $rows1);
array_push($result, $rows2);
array_push($result, $rows3);


print json_encode($result);
