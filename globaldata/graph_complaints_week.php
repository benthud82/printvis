
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
$slottingtable = $whsesel . 'invlinesshipped';

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
                                                        COUNT(*) AS TOT_COUNT,
                                                        1 - (COUNT(*) / (SELECT 
                                                                SUM(INVLINES)
                                                            FROM
                                                                slotting.$slottingtable
                                                            WHERE
                                                                YEARWEEK(INVDATE) = YEARWEEK(ORD_RETURNDATE)
                                                                    AND INVWHSE = WHSE
                                                            GROUP BY YEARWEEK(INVDATE))) as WEEK_PERC
                                                    FROM
                                                        custaudit.custreturns
                                                    WHERE
                                                        WEEKDAY(ORD_RETURNDATE) NOT IN (5 , 6)
                                                            AND WHSE = $whsesel
                                                            AND RETURNCODE IN ('IBNS' , 'WISP', 'WQSP')
                                                            AND YEARWEEK(ORD_RETURNDATE) >= YEARWEEK(CURDATE() - INTERVAL 52 WEEK)
                                                    GROUP BY SUBDATE(ORD_RETURNDATE,
                                                        WEEKDAY(ORD_RETURNDATE))
                                                    ORDER BY ORD_RETURNDATE");
$result1->execute();



$rows = array();
$rows['name'] = 'Week Starting';
$rows1 = array();
$rows1['name'] = 'WQSP - Wrong Quantity';
$rows2 = array();
$rows2['name'] = 'WISP - Wrong Item';
$rows3 = array();
$rows3['name'] = 'IBNS - Ordered, Not Shipped';
$rows4 = array();
$rows4['name'] = 'Total Complaints';
$rows5 = array();
$rows5['name'] = 'Weekly Accuracy';

foreach ($result1 as $row) {
    $rows['data'][] = date('Y-m-d', strtotime($row['WEEKSTARTING']));  
    $rows1['data'][] = ($row['SUM_WQSP']) * 1;  
    $rows2['data'][] =  ($row['SUM_WISP']) * 1;  
    $rows3['data'][] =  ($row['SUM_IBNS']) * 1;  
    $rows4['data'][] =  ($row['TOT_COUNT']) * 1;  
    $rows5['data'][] =  ($row['WEEK_PERC']) * 100;  

}



$result = array();
array_push($result, $rows);
array_push($result, $rows1);
array_push($result, $rows2);
array_push($result, $rows3);
array_push($result, $rows4);
array_push($result, $rows5);


print json_encode($result);
