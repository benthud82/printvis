<?php

//include '../sessioninclude.php';

include '../../globalincludes/usa_asys.php';
include '../../connections/conn_printvis.php';
ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../functions/functions_totetimes.php';
//if (isset($_SESSION['MYUSER'])) {
//    $var_userid = $_SESSION['MYUSER'];
//    $whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
//    $whssql->execute();
//    $whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);
//
//    $var_whse = $whssqlarray[0]['prodvisdb_users_PRIMDC'];
//    $whsearray = array($var_whse);
//} else {
//    $whsearray = array(7);
//}


$whsearray = array(7, 3.1, 3.2, 6, 9,2);




//$today = date('Y-m-d') ;
$today = date('Y-m-d');

$turninches = intval(120);

$openbatches_case_cols = '';


foreach ($whsearray as $whsesel) {
    include '../timezoneset.php';
    if ($whsesel == 3.1) {
        $building = 1;
        $whsesel = 3;
    } elseif ($whsesel == 3.2) {
        $building = 2;
        $whsesel = 3;
    } else {
        $building = 1;
    }
    //Delete from openbatches_case table where batches are older than yesterday's cutoff time
//    $sqldelete = "DELETE FROM  printvis. WHERE  = $whsesel ";
//    $querydelete = $conn1->prepare($sqldelete);
//    $querydelete->execute();
    //Pull in forecast lines/units
    $forecast = $conn1->prepare("SELECT 
                                                            fcase_hour,
                                                            fcase_equipment,
                                                            fcase_lines,
                                                            fcase_cubevol,
                                                            casepm_pickloc,
                                                            casepm_units,
                                                            casepm_indirect,
                                                            casepm_dropoff,
                                                            casepm_noncon,
                                                            casepm_inch_per_min,
                                                            casepm_kiosk,
                                                            casepm_batch,
                                                            casepm_comp,
                                                            casepm_short,
                                                            casepm_trip,
                                                            casepm_tripdivisor,
                                                            casepm_maxline,
                                                            casepm_forecasttravelheadertime,
                                                            casepm_tripdivisor
                                                        FROM
                                                            printvis.forecast_case
                                                                JOIN
                                                            printvis.pm_casetimes ON fcase_whse = casepm_whse
                                                                AND fcase_build = casepm_build
                                                                AND fcase_equipment = casepm_equipment
                                                        WHERE
                                                            fcase_date = '$today'
                                                                and fcase_whse = $whsesel and fcase_build = $building");
    $forecast->execute();
    $forecast_array = $forecast->fetchAll(pdo::FETCH_ASSOC);


    //Batch equipment estimator.  Split forecast into predicted batches with limit on total number of lines per batch

    foreach ($forecast_array as $key => $value) {
        $fcase_hour = $forecast_array[$key]['fcase_hour'];
        $fcase_equipment = $forecast_array[$key]['fcase_equipment'];
        $fcase_lines = $forecast_array[$key]['fcase_lines'];
        $fcase_cubevol = $forecast_array[$key]['fcase_cubevol'];
        $casepm_pickloc = $forecast_array[$key]['casepm_pickloc'];
        $casepm_units = $forecast_array[$key]['casepm_units'];
        $casepm_indirect = $forecast_array[$key]['casepm_indirect'];
        $casepm_dropoff = $forecast_array[$key]['casepm_dropoff'];
        $casepm_noncon = $forecast_array[$key]['casepm_noncon'];
        $casepm_inch_per_min = $forecast_array[$key]['casepm_inch_per_min'];



        $casepm_trip = $forecast_array[$key]['casepm_trip'];
        $casepm_tripdivisor = $forecast_array[$key]['casepm_tripdivisor'];
        $casepm_maxline = $forecast_array[$key]['casepm_maxline'];


        //is box count over max lines?  If so, split 
        $splitcalc = floor($fcase_lines / $casepm_maxline);

        $hourbatchcount = $splitcalc + 1;  //need to add one to get remainder of lines since floor was used
        //multiply header times by number of predicted batches
        $casepm_kiosk = $forecast_array[$key]['casepm_kiosk'] * $hourbatchcount;
        $casepm_batch = $forecast_array[$key]['casepm_batch'] * $hourbatchcount;
        $casepm_comp = $forecast_array[$key]['casepm_comp'] * $hourbatchcount;
        $casepm_forecasttravelheadertime = $forecast_array[$key]['casepm_forecasttravelheadertime'] * $hourbatchcount;
        if ($fcase_equipment == 'BELTLINE' && $whsesel == 3) {
            $casepm_short = $forecast_array[$key]['casepm_short'] * $hourbatchcount * $fcase_lines;
        } else {
            $casepm_short = $forecast_array[$key]['casepm_short'];
        }

        $TOT_TRIPS = intval(ceil($fcase_cubevol / $casepm_tripdivisor));
        $TIME_TRIPCUBE = $TOT_TRIPS * $casepm_trip;


        $calc_picktimeloc = $fcase_lines * $casepm_pickloc;
        $calc_picktimeunit = $fcase_cubevol * $casepm_units;
        $calc_picktimeindirect = $fcase_lines * $casepm_indirect;
        $calc_picktimedropoff = $fcase_lines * $casepm_dropoff;
//        $calc_picktimenoncon = $fcase_lines * $casepm_noncon;
        $calc_picktimenoncon = 0;
        $TIME_STARTSTOP = 1 * $hourbatchcount;

        $TIME_BATCH_FINAL = $casepm_kiosk + $casepm_batch + $casepm_comp + $casepm_short + $TIME_TRIPCUBE + $calc_picktimeloc + $calc_picktimeunit + $calc_picktimeindirect + $calc_picktimedropoff + $calc_picktimenoncon + $casepm_forecasttravelheadertime + $TIME_STARTSTOP;

        $sqlupdate = "UPDATE printvis.forecast_case SET fcase_minuteforecast='$TIME_BATCH_FINAL' WHERE fcase_whse = $whsesel and fcase_date='$today' and fcase_hour='$fcase_hour' and fcase_equipment='$fcase_equipment' and fcase_build = '$building';";
        $queryupdate = $conn1->prepare($sqlupdate);
        $queryupdate->execute();
    }//end of forecast_array loop
} //end of whse loop