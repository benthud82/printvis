<?php

include '../../CustomerAudit/connection/connection_details.php';
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


$unscannedsql = $conn1->prepare("SELECT 
                                                                            case_batch, case_date, case_equip, case_lines, case_time
                                                                        FROM
                                                                            printvis.unscannedcases
                                                                        WHERE
                                                                            DATE(case_date) >= '$var_startdate' and DATE(case_date) <= '$var_enddate' 
                                                                                AND case_whse = $var_whse ");
$unscannedsql->execute();
$unscannedsql_array = $unscannedsql->fetchAll(pdo::FETCH_ASSOC);







$now = date('Y_m_d');


//The name of the CSV file that will be downloaded by the user.
$fileName = 'Unscannedcases_' . $now . '.csv';

//Set the Content-Type and Content-Disposition headers.
header('Content-Type: application/excel');
header('Content-Disposition: attachment; filename="' . $fileName . '"');


$csvdata = array();

//header
$csvdata[] = array("Batch", "Print Date", "Equipment", "Lines", "Unscanned Mins");


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
