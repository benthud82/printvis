
<?php

include '../sessioninclude.php';
include '../../CustomerAudit/connection/connection_details.php';
include '../functions/functions_totetimes.php';

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
                                                                    hourbucket_datetime,
                                                                    hourbucket_notprinted,
                                                                    hourbucket_printed,
                                                                    hourbucket_picking,
                                                                    hourbucket_remaining,
                                                                    hourbucket_notprinted + hourbucket_printed + hourbucket_picking AS TOT_TIME
                                                                FROM
                                                                    printvis.casevis_hourbuckets
                                                                WHERE
                                                                    hourbucket_whse = $whsesel
                                                                        AND hourbucket_build = $building
                                                                        AND DATE(hourbucket_datetime) = CURDATE()");
$result1->execute();



$rows = array();
$rows['name'] = 'Date';
$rows1 = array();
$rows1['name'] = 'Not Printed';
$rows2 = array();
$rows2['name'] = 'Printed not Picked';
$rows3 = array();
$rows3['name'] = 'Currently Picking';
$rows4 = array();
$rows4['name'] = 'Total';
$rows5 = array();
$rows5['name'] = 'Hours Remaining';

foreach ($result1 as $row) {
    $rows['data'][] = date('H:i A', strtotime($row['hourbucket_datetime']));  
    $rows1['data'][] = ($row['hourbucket_notprinted']) * 1;  
    $rows2['data'][] =  ($row['hourbucket_printed']) * 1;  
    $rows3['data'][] =  ($row['hourbucket_picking']) * 1;  
    $rows4['data'][] =  ($row['TOT_TIME']) * 1;  
    $rows5['data'][] =  ($row['hourbucket_remaining']) * 1;  

}



$result = array();
array_push($result, $rows);
array_push($result, $rows1);
array_push($result, $rows2);
array_push($result, $rows3);
array_push($result, $rows4);
array_push($result, $rows5);


print json_encode($result);
