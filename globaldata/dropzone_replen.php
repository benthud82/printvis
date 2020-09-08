
<?php

include '../../CustomerAudit/connection/connection_details.php';
include '../sessioninclude.php';
include '../functions/functions_totetimes.php';
require '../../globalincludes/usa_asys.php';
if (isset($_SESSION['MYUSER'])) {
    $var_userid = $_SESSION['MYUSER'];
    $whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
    $whssql->execute();
    $whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

    $var_whse = $whssqlarray[0]['prodvisdb_users_PRIMDC'];
}

# checkbox dropzone from shorts_expeditor.php
$onlydrop = ($_POST['onlydrop']);
if ($onlydrop == 1) {
    $var_onlydropsql = " and mvdend <> '0001-01-01-00.00.00.000000' ";
} else {
    $var_onlydropsql = " ";
}



$today = date('Ymd', strtotime('-5 days'));
//delete current box holds for whse
$sqldelete = "DELETE FROM  printvis.dropzone_replen WHERE dropzone_whse = $var_whse";
$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();


//pull in new asoboxhold
$mysqltable = 'dropzone_replen';
$schema = 'printvis';
$arraychunk = 10000; //each result array will be split into 1000 line chunks to prevent memory over allocation

$result_dropzone = $aseriesconn->prepare("SELECT
                                            MVTRN# as dropzone_tran,
                                            MVWHSE as dropzone_whse,
                                            MVTITM as dropzone_item,
                                            MVFZNE as dropzone_fromzone,
                                            MVTZNE as dropzone_tozone,
                                            MVPLC# as dropzone_fromloc,
                                            MVTLC# as dropzone_toloc,
                                            MVREQD as dropzone_reqdate
                                        FROM
                                            HSIPCORDTA.NPFMVE
                                        WHERE                                            
                                             MVSTAT <> 'C'
                                             $var_onlydropsql
                                            and MVWHSE  = $var_whse
                                            and MVREQD >= $today");
$result_dropzone->execute();
$result_dropzonearray = $result_dropzone->fetchAll(PDO::FETCH_ASSOC);

//insert into table
pdoMultiInsert($mysqltable, $schema, $result_dropzonearray, $conn1, $arraychunk);
