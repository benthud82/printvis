<?php

//get whse for user
if (isset($_SESSION['MYUSER'])) {
    $var_userid = $_SESSION['MYUSER'];
    $whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
    $whssql->execute();
    $whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);
    $whsesel = $whssqlarray[0]['prodvisdb_users_PRIMDC'];

    $maperror = $conn1->prepare("SELECT 
                                                                bayloc, vectormap, maxmin, dimissues, shipzones
                                                            FROM
                                                                printvis.badges
                                                             WHERE whse = $whsesel;");
    $maperror->execute();
    $maperrorarray = $maperror->fetchAll(pdo::FETCH_ASSOC);
}
if (isset($maperrorarray)) {
    $bayloc = $maperrorarray[0]['bayloc'];
    $vectormap = $maperrorarray[0]['vectormap'];
    $maxmin = $maperrorarray[0]['maxmin'];
    $dimissues = $maperrorarray[0]['dimissues'];
    $shipzones = $maperrorarray[0]['shipzones'];
} else {
    $bayloc = 0;
    $vectormap = 0;
    $maxmin = 0;
    $dimissues = 0;
    $shipzones = 0;
}