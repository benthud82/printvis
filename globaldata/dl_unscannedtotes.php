<?php

include '../../connections/conn_printvis.php';
include '../sessioninclude.php';
if (isset($_SESSION['MYUSER'])) {
    $var_userid = $_SESSION['MYUSER'];
    $whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
    $whssql->execute();
    $whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

    $var_whse = $whssqlarray[0]['prodvisdb_users_PRIMDC'];

    if ($var_whse == 3) {
        $building = 2;
    } else {
        $building = 1;
    }
} else {
    $whsearray = array(7);
}


$var_startdate = date('Y-m-d', strtotime($_GET['startdatesel']));
$var_enddate = date('Y-m-d', strtotime($_GET['enddatesel']));


$unscannedsql = $conn1->prepare("SELECT DISTINCT
                                                                totetimes_cart,
                                                                cartstart_tsm,
                                                                cartstart_starttime,
                                                                cartstart_packstation,
                                                                SUM(CASE
                                                                    WHEN allscan_tsm > 0 THEN 1
                                                                    ELSE 0
                                                                END) AS TOTE_SCANNED,
                                                                SUM(CASE
                                                                    WHEN allscan_tsm > 0 THEN 0
                                                                    ELSE 1
                                                                END) AS TOTE_NOTSCANNED,
                                                                SUM(CASE
                                                                    WHEN allscan_tsm > 0 THEN 1
                                                                    ELSE 0
                                                                END) / COUNT(*) AS PERC_SCANNED,
                                                                SUM(CASE
                                                                    WHEN allscan_tsm > 0 THEN 0
                                                                    ELSE totetimes_totalPFD
                                                                END) AS USCANNED_TIME
                                                            FROM
                                                                printvis.alltote_history
                                                                    LEFT JOIN
                                                                printvis.scannedtote_history ON totelp = allscan_lp
                                                                    LEFT JOIN
                                                                printvis.allcart_history_hist A ON cartstart_whse = totetimes_whse
                                                                    AND totetimes_cart = cartstart_batch and date(cartstart_starttime) = date(totetimes_dateadded)
                                                            WHERE
                                                                DATE(cartstart_starttime) >= '$var_startdate' and 
                                                                DATE(cartstart_starttime) <= '$var_enddate' 
                                                                    AND cartstart_whse = $var_whse
                                                                    AND A.cartstart_starttime IN (SELECT 
                                                                        MAX(B.cartstart_starttime)
                                                                    FROM
                                                                        printvis.allcart_history B
                                                                    WHERE
                                                                        B.cartstart_batch = A.cartstart_batch)
                                                            GROUP BY totetimes_cart , cartstart_tsm , cartstart_starttime , cartstart_packstation
                                                            HAVING PERC_SCANNED < 1 ORDER BY USCANNED_TIME desc ");
$unscannedsql->execute();
$unscannedsql_array = $unscannedsql->fetchAll(pdo::FETCH_ASSOC);







$now = date('Y_m_d');


//The name of the CSV file that will be downloaded by the user.
$fileName = 'Unscannedtotes_' . $now . '.csv';

//Set the Content-Type and Content-Disposition headers.
header('Content-Type: application/excel');
header('Content-Disposition: attachment; filename="' . $fileName . '"');


$csvdata = array();

//header
$csvdata[] = array("Cart", "TSM", "Start Time", "Pack Station", "Totes Scanned", "Totes Not Scanned", "Perc Scanned", "Unscanned Mins");


//A multi-dimensional array containing our CSV data.
foreach ($unscannedsql_array as $key => $value) {
    $csvdata[] = array_values($unscannedsql_array[$key]);
}

//Open up a PHP output stream using the function fopen.
$fp = fopen('php://output', 'w');

//Loop through the array containing our CSV data.
foreach ($csvdata as $row) {
    //fputcsv formats the array into a CSV format.
    //It then writes the result to our output stream.
    fputcsv($fp, $row);
}

fclose($fp);
