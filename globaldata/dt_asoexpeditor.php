
<?php

include '../../CustomerAudit/connection/connection_details.php';
include '../sessioninclude.php';
include '../functions/functions_totetimes.php';
if (isset($_SESSION['MYUSER'])) {
    $var_userid = $_SESSION['MYUSER'];
    $whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
    $whssql->execute();
    $whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

    $var_whse = $whssqlarray[0]['prodvisdb_users_PRIMDC'];
}


$dt_sql = $conn1->prepare("SELECT 
                                dropzone_item,
                                dropzone_fromzone,
                                dropzone_tozone,
                                dropzone_fromloc,
                                dropzone_toloc,
                                dropzone_reqdate,
                                @COUNT_HOLD:=(SELECT 
                                        COUNT(*)
                                    FROM
                                        printvis.asoboxholds
                                    WHERE
                                        dropzone_whse = asohold_whse
                                            AND dropzone_toloc = asohold_location
                                            AND dropzone_item = asohold_item) AS COUNT_HOLD,
                                @COUNT_SHORTS:=(SELECT 
                                        COUNT(*)
                                    FROM
                                        printvis.shorts_daily_item
                                    WHERE
                                        dropzone_whse = shorts_item_whse
                                            AND dropzone_toloc = shorts_item_loc
                                            AND dropzone_item = shorts_item_item) AS COUNT_SHORTS,
                                (@COUNT_HOLD + @COUNT_SHORTS) AS COUNT_TOTAL
                            FROM
                                printvis.dropzone_replen
                            WHERE dropzone_whse = $var_whse
                            HAVING COUNT_TOTAL > 0
                            ORDER BY COUNT_TOTAL DESC");
$dt_sql->execute();
$dt_array = $dt_sql->fetchAll(pdo::FETCH_ASSOC);




foreach ($dt_array as $key => $value) {
    $dropzone_item = $dt_array[$key]['dropzone_item'];
    $dropzone_fromloc = $dt_array[$key]['dropzone_fromloc'];
    $dropzone_toloc = $dt_array[$key]['dropzone_toloc'];
    $COUNT_HOLD = $dt_array[$key]['COUNT_HOLD'];
    $COUNT_SHORTS = $dt_array[$key]['COUNT_SHORTS'];
    $COUNT_TOTAL = $dt_array[$key]['COUNT_TOTAL'];


    $rowpush = array($dropzone_item, $dropzone_fromloc, $dropzone_toloc, $COUNT_HOLD, $COUNT_SHORTS, $COUNT_TOTAL);
    $row[] = array_values($rowpush);
}

$output = array(
    "aaData" => array()
);

$output['aaData'] = $row;
echo json_encode($output);
