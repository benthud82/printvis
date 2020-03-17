
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


//delete current box holds for whse
$sqldelete = "DELETE FROM  printvis.asoboxholds WHERE asohold_whse = $var_whse";
$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();


//pull in new asoboxhold
$mysqltable = 'asoboxholds';
$schema = 'printvis';
$arraychunk = 10000; //each result array will be split into 1000 line chunks to prevent memory over allocation

$result_asoboxhold = $aseriesconn->prepare("SELECT
                                                0      as idasoboxholds,
                                                PBWHSE as asohold_whse ,
                                                PLITEM as asohold_item ,
                                                PLLOC# as asohold_location
                                            FROM
                                                HSIPCORDTA.NOTWPB
                                                JOIN
                                                    HSIPCORDTA.NOTWPL
                                                    on
                                                        PLWCS#     = PBWCS#
                                                        and PLWKNO = PBWKNO
                                                        and PLBOX# = PBBOX#
                                            WHERE
                                                PLSTAT     = ' '
                                                and PBWHSE = $var_whse
                                                and PLRESN = 'ASO'");
$result_asoboxhold->execute();
$result_asoboxholdarray = $result_asoboxhold->fetchAll(PDO::FETCH_ASSOC);

//insert into table
pdoMultiInsert($mysqltable, $schema, $result_asoboxholdarray, $conn1, $arraychunk);