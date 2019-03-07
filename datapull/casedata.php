<?php

include '../../globalincludes/usa_asys.php';
include '../../connections/conn_printvis.php';
ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../functions/functions_totetimes.php';
//include '../../globalfunctions/custdbfunctions.php';
$whsearray = array(3, 6, 9, 2);


$today = date('Y-m-d');
$dayofweek = date('w', strtotime($today));
if ($dayofweek == 1) {
    $yesterday = date('Y-m-d', strtotime("-3 days"));
} else {
    $yesterday = date('Y-m-d', strtotime("-1 day"));
}

$yestJdate = _gregdatetoyyddd($yesterday);

//initialize variables
$equipdata = $data = $data_totetimes = $batchcompletedata = $datafortaskpred = NULL;


$yesterdaytime = ('17:20:59');
$todaydatetime = date('Y-m-d H:i:s');
$printcutoff = date('Y-m-d H:i:s', strtotime("$yesterday $yesterdaytime"));
$turninches = intval(300);

$openbatches_case_cols = 'casebatch_whse, casebatch_build, casebatch_cart, casebatch_aisle, casebatch_trips, casebatch_cubicinch, casebatch_lines, casebatch_firstloc, casebatch_lastloc, casebatch_minbin, openbatches_printdatetime,casebatch_uniquelocs, casebatch_noncon, casebatch_vertdist';

$equipmenttype_case_cols = 'caseequip_whse, caseequip_build, caseequip_batch, caseequip_printdate, caseequip_lines, caseequip_primpicks, caseequip_bulkpicks, caseequip_ptbpicks, caseequip_palletpicks, caseequip_halfdeckpicks, caseequip_otherpicks, caseequip_equipment';

$casetotetimes_cols = 'casetote_time_whse, casetote_time_build, casetote_time_cart, casetote_time_aisle, casetote_time_equipment, casetote_time_trips, casetote_time_lines, casetote_time_firstloc,'
        . 'casetote_time_lastloc, casetote_time_printdate, casetote_time_pickloctime, casetote_time_pickunittime, casetote_time_indirecttime, casetote_time_dropofftime, casetote_time_noncontime, casetote_time_inches_inneraisle, casetote_time_inches_outeraisle, casetote_time_traveltime, casetote_time_verttime, casetote_time_totaltime';

$casebatchtimes_cols = 'casebatches_whse,casebatches_build,casebatches_cart,casebatches_equipment,casebatches_printdate,casebatches_time_kiosk,casebatches_time_batch,casebatches_time_complete,casebatches_time_short,casebatches_trip,casebatches_totaltrips,casebatches_time_tripcube,casebatches_lines,casebatches_firstloc,casebatches_lastloc,casebatches_time_pickloc,casebatches_time_unit,casebatches_time_indirect,casebatches_time_dropoff,casebatches_time_noncon,casebatches_aisleinches,casebatches_time_aisletravel,casebatches_time_verttravel,casebatches_starttofirst,casebatches_lasttostop,casebatches_inchpermin,casebatches_startstopinches,casebatches_time_startstop,casebatches_time_final';



//Delete from casebatchstarttime table where batches are older than yesterday's cutoff time
$sqldelete4 = "DELETE from  printvis.case_boxesreleased WHERE boxrel_printdate < $yestJdate ";
$querydelete4 = $conn1->prepare($sqldelete4);
$querydelete4->execute();

//Delete from casebatchstarttime table where batches are older than yesterday's cutoff time
$sqldelete6 = "DELETE FROM  printvis.casebatchdelete WHERE casedelete_date < '$yesterday'";
$querydelete6 = $conn1->prepare($sqldelete6);
$querydelete6->execute();

//Delete from casebatchstarttime table where batches are older than yesterday's cutoff time
$sqldelete7 = "DELETE FROM  printvis.casepick_openpicks WHERE allpicks_ptjd <= '$yestJdate'";
$querydelete7 = $conn1->prepare($sqldelete7);
$querydelete7->execute();


foreach ($whsearray as $whsesel) {
    if ($whsesel == 3) {
        $building = 2;
    } else {
        $building = 1;
    }
    include '../timezoneset.php';
    //Delete from openbatches_case table where batches are older than yesterday's cutoff time
    $sqldelete = "DELETE FROM  printvis.openbatches_case WHERE casebatch_whse = $whsesel and  openbatches_printdatetime < '$printcutoff' ";
    $querydelete = $conn1->prepare($sqldelete);
    $querydelete->execute();

    //Delete from case_batchequip table where batches are older than yesterday's cutoff time
    $sqldelete2 = "DELETE FROM  printvis.case_batchequip WHERE caseequip_whse = $whsesel and  caseequip_printdate < '$printcutoff' ";
    $querydelete2 = $conn1->prepare($sqldelete2);
    $querydelete2->execute();

    //Delete from casetote_time table where batches are older than yesterday's cutoff time
    $sqldelete3 = "DELETE FROM  printvis.casetote_time WHERE casetote_time_whse = $whsesel and  casetote_time_printdate < '$printcutoff' ";
    $querydelete3 = $conn1->prepare($sqldelete3);
    $querydelete3->execute();

    //Delete from casetote_time table where batches are older than yesterday's cutoff time
    $sqldelete4 = "DELETE FROM  printvis.casebatches_time WHERE casebatches_whse = $whsesel and  casebatches_printdate < '$printcutoff' ";
    $querydelete4 = $conn1->prepare($sqldelete4);
    $querydelete4->execute();

    //Delete from casetote_time table where batches are older than yesterday's cutoff time
//    $sqldelete5 = "DELETE FROM  printvis.taskpred WHERE taskpred_whse = $whsesel and  taskpred_updatetime < '$printcutoff'  and taskpred_function = 'CAS'";
//    $querydelete5 = $conn1->prepare($sqldelete5);
//    $querydelete5->execute();
    //Delete from casebatchstarttime table where batches are older than yesterday's cutoff time
    $sqldelete4 = "DELETE FROM  printvis.casebatchstarttime WHERE starttime_whse = $whsesel and  date(starttime_starttime) <> '$today' ";
    $querydelete4 = $conn1->prepare($sqldelete4);
    $querydelete4->execute();







    //Batch start times
    $casebatchstart = $aseriesconn->prepare("SELECT TRIM(substr(NVCFLT,3,2)) as WHSE,
                                                                                         TRIM(substr(NVCFLT,7,5)) as BATCH, 
                                                                                         TRIM(substr(NVCFLT,49,10)) as TSM,
                                                                                         TRIM(substr(NVCFLT,120,26))  as STARTTIME
                                                                                     FROM HSIPCORDTA.NOFCAS
                                                                                     WHERE TRIM(substr(NVCFLT,3,2)) = $whsesel and TRIM(substr(NVCFLT,120,10)) = '$today'");
    $casebatchstart->execute();
    $casebatchstart_array = $casebatchstart->fetchAll(pdo::FETCH_ASSOC);
    $casestartdata = array();
    $casestartcols = 'starttime_whse, starttime_build, starttime_batch,  starttime_tsm, starttime_starttime';
    foreach ($casebatchstart_array as $key5 => $value) {
        $starttime_whse = $casebatchstart_array[$key5]['WHSE'];
        $starttime_build = $building;
        $starttime_batch = $casebatchstart_array[$key5]['BATCH'];
        $starttime_tsm = $casebatchstart_array[$key5]['TSM'];
        $starttime_starttime = $casebatchstart_array[$key5]['STARTTIME'];

        $casestartdata[] = "($starttime_whse, $starttime_build, $starttime_batch, $starttime_tsm, '$starttime_starttime' )";
    }
    $arraycount = count($casebatchstart_array);

    if ($arraycount > 0) {
        //Add to table casebatchstarttime
        $values5 = implode(',', $casestartdata);
        $sql5 = "INSERT IGNORE INTO printvis.casebatchstarttime ($casestartcols) VALUES $values5";
        $query5 = $conn1->prepare($sql5);
        $query5->execute();

        $sql5 = "INSERT IGNORE INTO printvis.casebatchstarttime_hist ($casestartcols) VALUES $values5";
        $query5 = $conn1->prepare($sql5);
        $query5->execute();
    }



    //Batch equipment estimator
    $batchdata = $aseriesconn->prepare("SELECT
                                                                                PBWHSE,                                                                     
                                                                                CASE WHEN PBWHSE = 3 and PDLOC# >= 'W400000' then 2 else 1 end as PBBUILD,
                                                                                PBCART,                                                                     
                                                                                PBPTJD,                                                                     
                                                                                PBPTHM,
                                                                                count(*) as LINE_COUNT,
                                                                                sum(case when LMPRIM = 'P' then 1 else 0 end) as PRIM_PICKS, 
                                                                                sum(case when LMTIER = 'C01' then 1 else 0 end) as BULK_PICKS,  
                                                                                sum(case when LMTIER in ('C02','C04') then 1 else 0 end) as PTB_PICKS, 
                                                                                sum(case when substr(LMLOC#,6,1) = '1' then 1 else 0 end) as PALLET_PICKS, 
                                                                                sum(case when LMTIER in ('C05', 'C06')  and substr(LMLOC#,6,1) >= '2' then 1 else 0 end) as HALFDECK_PICKS,
                                                                                sum(case when LMTIER not in ('C01', 'C02', 'C03', 'C05', 'C06') then 1 else 0 end) as OTHER_PICKS,
                                                                                MAX(PDLOC#) as LAST_LOC,
                                                                                MIN(PDLOC#) as FIRST_LOC
                                                                        FROM HSIPCORDTA.NOTWPD
                                                                        JOIN HSIPCORDTA.NOTWPB on PDWCS# = PBWCS# and PDWKNO = PBWKNO and PBBOX# = PDBOX# 
                                                                        LEFT JOIN HSIPCORDTA.NPFLSM on LMWHSE = PDWHSE and LMLOC# = PDLOC#
                                                                        WHERE PDWHSE = $whsesel                                                                    
                                                                                and PDBXSZ = 'CSE'                                                                    
                                                                                and PDCART > 0                                                                     
                                                                                and PDLOC# not like '%SDS%'      
                                                                                 AND PBSHPC <> 'CPL'
                                                                                and LMLOC# <> 'WDN0000'
                                                 --                               and PDCART = 90537
                                                                                AND LMLOC# not like 'A%' and LMLOC# not like 'B%' and LMLOC# not like 'Y%'
                                                                        GROUP BY PBWHSE, CASE WHEN PBWHSE = 3 and PDLOC# >= 'W400000' then 2 else 1 end , PBCART, PBPTJD, PBPTHM                                                                 
                                                                        ORDER BY PBWHSE, CASE WHEN PBWHSE = 3 and PDLOC# >= 'W400000' then 2 else 1 end , PBCART, PBPTJD, PBPTHM");
    $batchdata->execute();
    $batcharray = $batchdata->fetchAll(pdo::FETCH_ASSOC);

    foreach ($batcharray as $key => $value) {
        $PBWHSE = $batcharray[$key]['PBWHSE'];
        $PBBUILD = $batcharray[$key]['PBBUILD'];
        $PBCART = $batcharray[$key]['PBCART'];
        $PBPTJD = $batcharray[$key]['PBPTJD'];
        $PBPTHM = intval($batcharray[$key]['PBPTHM']);
        if ($PBPTHM < 1000) {
            $PBPTHM = '0' . $PBPTHM;
        }
        $printhourmin = date('H:i', strtotime($PBPTHM));

        $printtime = $printhourmin . ':00';
        $printdate = _1yydddtogregdate($PBPTJD);
        $printdatetime = date('Y-m-d H:i:s', strtotime("$printdate $printtime"));
        if ($printdatetime < $printcutoff) {
            continue;
        }
        $LINE_COUNT = $batcharray[$key]['LINE_COUNT'];
        $PRIM_PICKS = $batcharray[$key]['PRIM_PICKS'];
        $BULK_PICKS = $batcharray[$key]['BULK_PICKS'];
        $PTB_PICKS = $batcharray[$key]['PTB_PICKS'];
        $PALLET_PICKS = $batcharray[$key]['PALLET_PICKS'];
        $HALFDECK_PICKS = $batcharray[$key]['HALFDECK_PICKS'];
        $OTHER_PICKS = $batcharray[$key]['OTHER_PICKS'];
        $LAST_LOC = $batcharray[$key]['LAST_LOC'];
        $FIRST_LOC = $batcharray[$key]['FIRST_LOC'];


        $equiptype = _equipestimator($LINE_COUNT, $PRIM_PICKS, $BULK_PICKS, $PTB_PICKS, $PALLET_PICKS, $HALFDECK_PICKS, $OTHER_PICKS, $FIRST_LOC, $LAST_LOC, $whsesel);

        $equipdata[] = "($PBWHSE, $PBBUILD, $PBCART, '$printdatetime', $LINE_COUNT,$PRIM_PICKS, $BULK_PICKS, $PTB_PICKS,$PALLET_PICKS, $HALFDECK_PICKS,$OTHER_PICKS, '$equiptype')";
    }
    //insert equipment type into case_batchequip table
    if (!is_null($equipdata)) {
        $values2 = implode(',', $equipdata);
        $sql2 = "INSERT IGNORE INTO printvis.case_batchequip ($equipmenttype_case_cols) VALUES $values2";
        $query2 = $conn1->prepare($sql2);
        $query2->execute();
    }


    //Update any batch that has been signed out as RCH
    //Do a lookup for batch number in case_batchequip table to find key and update equipment type to 'REACH'
    $caserch = $aseriesconn->prepare("SELECT DISTINCT                                                                                      
                                                                                        TRIM(substr(NVCFLT,7,5)) as BATCH_RCH
                                                                                         FROM HSIPCORDTA.NOFCAS
                                                                                             WHERE TRIM(substr(NVCFLT,3,2)) = $whsesel and TRIM(substr(NVCFLT,146,10)) = '$today' and TRIM(substr(NVCFLT,18,3)) = 'RCH'");
    $caserch->execute();
    $caserch_array = $caserch->fetchAll(pdo::FETCH_ASSOC);

    foreach ($caserch_array as $keyval => $value) {
        $rchbatch = $caserch_array[$keyval]['BATCH_RCH'];

        $sql = "UPDATE printvis.case_batchequip SET caseequip_equipment='REACH' WHERE caseequip_whse='$whsesel' and caseequip_batch='$rchbatch';";
        $query = $conn1->prepare($sql);
        $query->execute();
    }



//Pull in ship zone cutoff times
    $cutoff = $conn1->prepare("SELECT cutoff_zone, cutoff_time FROM printvis.printcutoff WHERE cutoff_DC = $whsesel");
    $cutoff->execute();
    $cutoffarray = $cutoff->fetchAll(pdo::FETCH_ASSOC);


    //All picks currently in open file.
    $alldata = $aseriesconn->prepare("SELECT 
                                                                                PBWHSE,
                                                                                CASE
                                                                                    WHEN PBWHSE = 3 AND PDLOC# >= 'W400000' THEN 2
                                                                                    ELSE 1
                                                                                END AS PBBUILD,
                                                                                PBCART,
                                                                                PBPTJD,
                                                                                PBPTHM,
                                                                                SUBSTR(PDLOC#, 1, 3) AS AISLE,
                                                                             (CASE
                                                                                    WHEN PCCVOL = 0 THEN PCEVOL * .0610237
                                                                                    ELSE PCCVOL * .0610237
                                                                                END) AS CUBIC_INCH,
                                                                                 PDLOC# as LOCATION,
                                                                                 PDBIN#  as BINNUM,
                                                                                (CASE
                                                                                    WHEN LMPRIM = 'P' THEN 1
                                                                                    ELSE 0
                                                                                END) AS PRIM_PICKS,
                                                                                (CASE
                                                                                    WHEN LMTIER = 'C01' THEN 1
                                                                                    ELSE 0
                                                                                END) AS BULK_PICKS,
                                                                                (CASE
                                                                                    WHEN LMTIER = 'C02' THEN 1
                                                                                    ELSE 0
                                                                                END) AS PTB_PICKS,
                                                                                (CASE
                                                                                    WHEN LMTIER = 'C03' THEN 1
                                                                                    ELSE 0
                                                                                END) AS PALLET_PICKS,
                                                                                (CASE
                                                                                    WHEN LMTIER = 'C05' THEN 1
                                                                                    ELSE 0
                                                                                END) AS HALF_PICKS,
                                                                                (CASE
                                                                                    WHEN LMTIER = 'C06' THEN 1
                                                                                    ELSE 0
                                                                                END) AS DECK_PICKS,
                                                                                (CASE
                                                                                    WHEN LMTIER NOT IN ('C01' , 'C02', 'C03', 'C05', 'C06') THEN 1
                                                                                    ELSE 0
                                                                                END) AS OTHER_PICKS,
                                                                                (case when PBBOXV * .061024 > 10368 then 1 else 0 end) as NONCON
                                                                            FROM
                                                                                HSIPCORDTA.NOTWPD A
                                                                                     LEFT JOIN
                                                                                HSIPCORDTA.NPFCPC ON PCITEM = PDITEM
                                                                                    JOIN
                                                                                HSIPCORDTA.NOTWPB ON PDWCS# = PBWCS# AND PDWKNO = PBWKNO
                                                                                    AND PBBOX# = PDBOX#
                                                                                    LEFT JOIN
                                                                                HSIPCORDTA.NPFLSM ON LMWHSE = PDWHSE AND LMLOC# = PDLOC#
                                                                            WHERE
                                                                                PDWHSE = $whsesel AND PDBXSZ = 'CSE'
                                                                                    AND PDCART > 0
                                                                                    AND PDLOC# NOT LIKE '%SDS%'
                                                                                    AND PCWHSE = 0
                                                                                    AND PBSHPC <> 'CPL'
                                                                 --                   AND PDCART = 90537
                                                                                    AND LMLOC# not like 'A%' and LMLOC# not like 'B%' and LMLOC# not like 'Y%'
                                                                              ORDER BY  PDCART asc, PDBIN# asc");
    $alldata->execute();
    $alldata_array = $alldata->fetchAll(pdo::FETCH_ASSOC);

    //insert data into casepick_openpicks
    foreach ($alldata_array as $key => $value) {
        $allpicks_whse = $alldata_array[$key]['PBWHSE'];
        $allpicks_build = $alldata_array[$key]['PBBUILD'];
        $allpicks_cart = $alldata_array[$key]['PBCART'];
        $allpicks_ptjd = $alldata_array[$key]['PBPTJD'];
        $allpicks_pthm = $alldata_array[$key]['PBPTHM'];
        $allpicks_aisle = $alldata_array[$key]['AISLE'];
        $allpicks_cubeinch = $alldata_array[$key]['CUBIC_INCH'];
        $allpicks_location = $alldata_array[$key]['LOCATION'];
        $allpicks_binnum = $alldata_array[$key]['BINNUM'];
        $allpicks_primpicks = $alldata_array[$key]['PRIM_PICKS'];
        $allpicks_bulkpicks = $alldata_array[$key]['BULK_PICKS'];
        $allpicks_ptbpicks = $alldata_array[$key]['PTB_PICKS'];
        $allpicks_palletpicks = $alldata_array[$key]['PALLET_PICKS'];
        $allpicks_halfpicks = $alldata_array[$key]['HALF_PICKS'];
        $allpicks_deckpicks = $alldata_array[$key]['DECK_PICKS'];
        $allpicks_otherpicks = $alldata_array[$key]['OTHER_PICKS'];
        $allpicks_noncon = $alldata_array[$key]['NONCON'];
        //what is prev location
        if ($allpicks_binnum == 1) {
            $prevloc = 'X';
        } else {
            $prevloc = $alldata_array[$key - 1]['LOCATION'];
        }

        //what is next location
        if (isset($alldata_array[$key + 1]['BINNUM'])) {
            if ($alldata_array[$key + 1]['BINNUM'] == 1) {
                $nextloc = 'X';
            } else {
                $nextloc = $alldata_array[$key + 1]['LOCATION'];
            }
        } else {
            $nextloc = 'X';
        }
        //function to determine if different location on next line
        $diff_next = _diffnext($allpicks_location, $nextloc);

        //function to determine if different location on previous line
        $diff_prev = _diffprev($allpicks_location, $prevloc);

        //If either the previous or the next location is different, must add vertical distance
        $vertadd = max($diff_next, $diff_prev);

        $data1[] = "($allpicks_whse, $allpicks_build, $allpicks_cart, $allpicks_ptjd, $allpicks_pthm, '$allpicks_aisle', '$allpicks_cubeinch', '$allpicks_location', $allpicks_binnum, $allpicks_primpicks, $allpicks_bulkpicks, $allpicks_ptbpicks, $allpicks_palletpicks, $allpicks_halfpicks, $allpicks_deckpicks, $allpicks_otherpicks, $allpicks_noncon, $vertadd)";
    }
    $openpicks_case_cols = 'allpicks_whse, allpicks_build, allpicks_cart, allpicks_ptjd, allpicks_pthm, allpicks_aisle, allpicks_cubeinch, allpicks_location, allpicks_binnum, allpicks_primpicks, allpicks_bulkpicks, allpicks_ptbpicks, allpicks_palletpicks, allpicks_halfpicks, allpicks_deckpicks, allpicks_otherpicks, allpicks_noncon, allpicks_vertadd';
    if (!empty($data1)) {
        $values1 = implode(',', $data1);
        $sql = "INSERT IGNORE INTO printvis.casepick_openpicks ($openpicks_case_cols) VALUES $values1 ";
        $query = $conn1->prepare($sql);
        $query->execute();
    }

    //All totes that are currently in the open box and open detail file.   ***Will have to modify this to group from newly created casepick_allpicks table to account for vertical travel***
    $totedata = $conn1->prepare("SELECT 
                                                        allpicks_whse AS PBWHSE,
                                                        allpicks_build AS PBBUILD,
                                                        allpicks_cart AS PBCART,
                                                        allpicks_ptjd PBPTJD,
                                                        allpicks_pthm AS PBPTHM,
                                                        allpicks_aisle AISLE,
                                                        SUM(allpicks_cubeinch) AS TRIPS_CUBE,
                                                        SUM(allpicks_cubeinch) AS CUBIC_INCH,
                                                        COUNT(allpicks_cart) AS LINE_COUNT,
                                                        (SELECT 
                                                                T.allpicks_location
                                                            FROM
                                                                printvis.casepick_openpicks T
                                                            WHERE
                                                                T.allpicks_cart = A.allpicks_cart
                                                                    AND T.allpicks_binnum = MIN(A.allpicks_binnum)) AS FIRSTLOC,
                                                        (SELECT 
                                                                T.allpicks_location
                                                            FROM
                                                                printvis.casepick_openpicks T
                                                            WHERE
                                                                T.allpicks_cart = A.allpicks_cart
                                                                    AND T.allpicks_binnum = MAX(A.allpicks_binnum)) AS LASTLOC,
                                                        (SELECT 
                                                                MIN(A.allpicks_binnum)
                                                            FROM
                                                                printvis.casepick_openpicks T
                                                            WHERE
                                                                T.allpicks_cart = A.allpicks_cart
                                                                    AND T.allpicks_binnum = MIN(A.allpicks_binnum)) AS MINBIN,
                                                        SUM(allpicks_primpicks) AS PRIM_PICKS,
                                                        SUM(allpicks_bulkpicks) AS BULK_PICKS,
                                                        SUM(allpicks_ptbpicks) AS PTB_PICKS,
                                                        SUM(allpicks_palletpicks) AS PALLET_PICKS,
                                                        SUM(allpicks_halfpicks) AS HALF_PICKS,
                                                        SUM(allpicks_deckpicks) AS DECK_PICKS,
                                                        SUM(allpicks_otherpicks) AS OTHER_PICKS,
                                                        COUNT(DISTINCT allpicks_location) AS UNIQUE_LOCS,
                                                        SUM(allpicks_noncon) AS COUNT_NONCON,
                                                        SUM(CASE
                                                            WHEN allpicks_vertadd = 1 THEN casemap_ycoor
                                                            ELSE 0
                                                        END) AS VERTDIST
                                                    FROM
                                                        printvis.casepick_openpicks A
                                                            LEFT JOIN
                                                        printvis.pickprediction_casemap ON SUBSTRING(allpicks_location, 1, 6) = casemap_loc
                                                        and casemap_whse = allpicks_whse
                                                    WHERE
                                                        allpicks_whse = $whsesel
                                                    GROUP BY allpicks_whse , allpicks_build , allpicks_cart , allpicks_ptjd , allpicks_pthm , allpicks_aisle");
    $totedata->execute();
    $totedataarray = $totedata->fetchAll(pdo::FETCH_ASSOC);


    foreach ($totedataarray as $key => $value) {
        //Is print date greater than yesterday at 5:00 PM?
        $PBPTJD = intval($totedataarray[$key]['PBPTJD']);
        $PBPTHM = intval($totedataarray[$key]['PBPTHM']);
        if ($PBPTHM < 1000) {
            $PBPTHM = '0' . $PBPTHM;
        }
        $printhourmin = date('H:i', strtotime($PBPTHM));
        $printtime = $printhourmin . ':00';
        $printdate = _1yydddtogregdate($PBPTJD);
        $printdatetime = date('Y-m-d H:i:s', strtotime("$printdate $printtime"));
        if ($printdatetime < $printcutoff) {
            continue;
        }
        $PBWHSE = $totedataarray[$key]['PBWHSE'];
        $PBBUILD = $totedataarray[$key]['PBBUILD'];
        $PBCART = $totedataarray[$key]['PBCART'];
        $AISLE = $totedataarray[$key]['AISLE'];
        $TRIPS_CUBE = $totedataarray[$key]['TRIPS_CUBE'];
        $CUBIC_INCH = $totedataarray[$key]['CUBIC_INCH'];
        $LINE_COUNT = $totedataarray[$key]['LINE_COUNT'];
        $FIRSTLOC = $totedataarray[$key]['FIRSTLOC'];
        $LASTLOC = $totedataarray[$key]['LASTLOC'];
        $casebatch_minbin = intval($totedataarray[$key]['MINBIN']);
        $UNIQUE_LOCS = intval($totedataarray[$key]['UNIQUE_LOCS']);
        $casebatch_noncon = intval($totedataarray[$key]['COUNT_NONCON']);
        $vertdistance = intval($totedataarray[$key]['VERTDIST']);
        $data[] = "($PBWHSE, $PBBUILD, $PBCART, '$AISLE', '$TRIPS_CUBE', '$CUBIC_INCH',  $LINE_COUNT, '$FIRSTLOC', '$LASTLOC', $casebatch_minbin, '$printdatetime', $UNIQUE_LOCS,$casebatch_noncon, $vertdistance)";
    }

    //insert ignore into mysql table openbatches_case current open batches
    if (!is_null($data)) {
        $values = implode(',', $data);
        $sql = "INSERT INTO printvis.openbatches_case ($openbatches_case_cols) VALUES $values ON DUPLICATE KEY UPDATE casebatch_trips=VALUES(casebatch_trips), casebatch_lines=VALUES(casebatch_lines), casebatch_firstloc=VALUES(casebatch_firstloc), casebatch_lastloc=VALUES(casebatch_lastloc), openbatches_printdatetime=VALUES(openbatches_printdatetime), casebatch_cubicinch=VALUES(casebatch_cubicinch) , casebatch_vertdist=VALUES(casebatch_vertdist) ";
        $query = $conn1->prepare($sql);
        $query->execute();
    }
    //Current open batches are now in openbatches_case table.  Pull in open batches and estimate times.  Insert into casebatches_time
    $opentotedata = $conn1->prepare("SELECT 
    openbatches_case.*,
    @LOWSTART_X:=(SELECT 
            aisle_x
        FROM
            printvis.aisle_coor
                JOIN
            printvis.pickprediction_casemap ON casemap_whse = aisle_whse
                AND casemap_aisle = aisle_id
        WHERE
            casemap_whse = casebatch_whse
                AND casemap_loc = SUBSTR(casebatch_lastloc, 1, 6)
                AND aisle_location = 'L') AS LOWSTART_X,
    @LOWSTART_Z:=(SELECT 
            aisle_z
        FROM
            printvis.aisle_coor
                JOIN
            printvis.pickprediction_casemap ON casemap_whse = aisle_whse
                AND casemap_aisle = aisle_id
        WHERE
            casemap_whse = casebatch_whse
                AND casemap_loc = SUBSTR(casebatch_lastloc, 1, 6)
                AND aisle_location = 'L') AS LOWSTART_Z,
    @HIGHSTART_X:=(SELECT 
            aisle_x
        FROM
            printvis.aisle_coor
                JOIN
            printvis.pickprediction_casemap ON casemap_whse = aisle_whse
                AND casemap_aisle = aisle_id
        WHERE
            casemap_whse = casebatch_whse
                AND casemap_loc = SUBSTR(casebatch_lastloc, 1, 6)
                AND aisle_location = 'H') AS HIGHSTART_X,
    @HIGHSTART_Z:=(SELECT 
            aisle_z
        FROM
            printvis.aisle_coor
                JOIN
            printvis.pickprediction_casemap ON casemap_whse = aisle_whse
                AND casemap_aisle = aisle_id
        WHERE
            casemap_whse = casebatch_whse
                AND casemap_loc = SUBSTR(casebatch_lastloc, 1, 6)
                AND aisle_location = 'H') AS HIGHSTART_Z,
    @BRIDGESTART_X:=(SELECT 
            aisle_x
        FROM
            printvis.aisle_coor
                JOIN
            printvis.pickprediction_casemap ON casemap_whse = aisle_whse
                AND casemap_aisle = aisle_id
        WHERE
            casemap_whse = casebatch_whse
                AND casemap_loc = SUBSTR(casebatch_lastloc, 1, 6)
                AND aisle_location = 'B') AS BRIDGESTART_X,
    @BRIDGESTART_Z:=(SELECT 
            aisle_z
        FROM
            printvis.aisle_coor
                JOIN
            printvis.pickprediction_casemap ON casemap_whse = aisle_whse
                AND casemap_aisle = aisle_id
        WHERE
            casemap_whse = casebatch_whse
                AND casemap_loc = SUBSTR(casebatch_lastloc, 1, 6)
                AND aisle_location = 'B') AS BRIDGESTART_Z,
    @LASTLOC_X:=(SELECT 
            pickprediction_xcoor
        FROM
            printvis.pickprediction_casemap
        WHERE
            SUBSTRING(casebatch_lastloc, 1, 6) = casemap_loc
                AND casemap_whse = casebatch_whse) AS LASTLOC_X,
    @LASTLOC_Z:=(SELECT 
            casemap_zcoor
        FROM
            printvis.pickprediction_casemap
        WHERE
            SUBSTRING(casebatch_lastloc, 1, 6) = casemap_loc
                AND casemap_whse = casebatch_whse) AS LASTLOC_Z,
    @FIRSTLOC_X:=(SELECT 
            pickprediction_xcoor
        FROM
            printvis.pickprediction_casemap
        WHERE
            SUBSTRING(casebatch_firstloc, 1, 6) = casemap_loc
                AND casemap_whse = casebatch_whse) AS FIRSTLOC_X,
    @FIRSTLOC_Z:=(SELECT 
            casemap_zcoor
        FROM
            printvis.pickprediction_casemap
        WHERE
            SUBSTRING(casebatch_firstloc, 1, 6) = casemap_loc
                AND casemap_whse = casebatch_whse) AS FIRSTLOC_Z,
    (ABS(@FIRSTLOC_X - @LASTLOC_X) + ABS(@FIRSTLOC_Z - @LASTLOC_Z)) AS INNERAISLETRAVEL,
    caseequip_equipment,
    casepm_pickloc,
    casepm_units,
    casepm_indirect,
    casepm_dropoff,
    casepm_noncon,
    casepm_inch_per_min,
    casepm_vert_inch_per_min,
    baytobay_inches
FROM
    printvis.openbatches_case
        JOIN
    printvis.case_batchequip ON casebatch_whse = caseequip_whse
        AND casebatch_build = caseequip_build
        AND casebatch_cart = caseequip_batch
        LEFT JOIN
    printvis.pm_casetimes ON casepm_whse = casebatch_whse
        AND casepm_build = casebatch_build
        AND caseequip_equipment = casepm_equipment
    LEFT JOIN
        printvis.casepm_baytobay on baytobay_whse = casebatch_whse and baytobay_aisle = casebatch_aisle
WHERE
    casebatch_whse = $whsesel and casebatch_build = $building
 --          and caseequip_batch = 30702
ORDER BY casebatch_cart , casebatch_minbin");
    $opentotedata->execute();
    $opentotedataarray = $opentotedata->fetchAll(pdo::FETCH_ASSOC);



    //initiate batch variable
    $openbatchcount = 0;
    $openarraycount = count($opentotedataarray) - 1;



    foreach ($opentotedataarray as $key => $value) {



        //calculate estimated completion times by batch/aisle key
        $casebatch_whse = $opentotedataarray[$key]['casebatch_whse'];
        $casebatch_build = $opentotedataarray[$key]['casebatch_build'];

        //What is CSTART
        $cstart = $conn1->prepare("SELECT 
                                                                            pickprediction_xcoor, casemap_zcoor
                                                                        FROM
                                                                            printvis.pickprediction_casemap
                                                                        WHERE
                                                                            casemap_whse = $whsesel
                                                                                AND casemap_building = $casebatch_build
                                                                                AND casemap_loc = 'CSTART' ");
        $cstart->execute();
        $cstart_array = $cstart->fetchAll(pdo::FETCH_ASSOC);
        $cstart_xcoor = $cstart_array[0]['pickprediction_xcoor'];
        $cstart_zcoor = $cstart_array[0]['casemap_zcoor'];

        //What is CSTOP
        $cstop = $conn1->prepare("SELECT 
                                                                            pickprediction_xcoor, casemap_zcoor
                                                                        FROM
                                                                            printvis.pickprediction_casemap
                                                                        WHERE
                                                                            casemap_whse = $whsesel
                                                                                AND casemap_building = $casebatch_build
                                                                                AND casemap_loc = 'CSTOP' ");
        $cstop->execute();
        $cstop_array = $cstop->fetchAll(pdo::FETCH_ASSOC);
        $cstop_xcoor = $cstop_array[0]['pickprediction_xcoor'];
        $cstop_zcoor = $cstop_array[0]['casemap_zcoor'];



        $casebatch_cart = intval($opentotedataarray[$key]['casebatch_cart']);
        $casebatch_aisle = $opentotedataarray[$key]['casebatch_aisle'];
        $casebatch_trips = $opentotedataarray[$key]['casebatch_trips'];
        $casebatch_lines = $opentotedataarray[$key]['casebatch_lines'];
        $casebatch_firstloc = $opentotedataarray[$key]['casebatch_firstloc'];
        $casebatch_lastloc = $opentotedataarray[$key]['casebatch_lastloc'];
        $openbatches_printdatetime = $opentotedataarray[$key]['openbatches_printdatetime'];
        $casebatch_vertdist = intval($opentotedataarray[$key]['casebatch_vertdist']);
        $UNIQUE_LOCS = $opentotedataarray[$key]['casebatch_uniquelocs'];
        $casebatch_noncon = intval($opentotedataarray[$key]['casebatch_noncon']);
        $baytobaytravel = $opentotedataarray[$key]['baytobay_inches'];
        $caseaisle_inches_inner = $opentotedataarray[$key]['INNERAISLETRAVEL'] + ($UNIQUE_LOCS * $baytobaytravel);
        if (is_null($caseaisle_inches_inner)) {
            $caseaisle_inches_inner = 0;
        }
        $LOWSTART_X = $opentotedataarray[$key]['LOWSTART_X'];
        $LOWSTART_Z = $opentotedataarray[$key]['LOWSTART_Z'];
        $HIGHSTART_X = $opentotedataarray[$key]['HIGHSTART_X'];
        $HIGHSTART_Z = $opentotedataarray[$key]['HIGHSTART_Z'];
        $BRIDGESTART_X = $opentotedataarray[$key]['BRIDGESTART_X'];
        $BRIDGESTART_Z = $opentotedataarray[$key]['BRIDGESTART_Z'];
        $FIRSTLOC_X = $opentotedataarray[$key]['FIRSTLOC_X'];
        $FIRSTLOC_Z = $opentotedataarray[$key]['FIRSTLOC_Z'];
        $LASTLOC_X = $opentotedataarray[$key]['LASTLOC_X'];
        $LASTLOC_Z = $opentotedataarray[$key]['LASTLOC_Z'];

        If ($key !== 0) { //Not the first record
            $previousloc_X = $opentotedataarray[$key - 1]['LASTLOC_X'];  //previous aisle last location X
            $previousloc_Z = $opentotedataarray[$key - 1]['LASTLOC_Z'];  //previous aisle last location Z
            $previousaisleH_X = $opentotedataarray[$key - 1]['HIGHSTART_X'];  //previous aisle high parking X
            $previousaisleH_Z = $opentotedataarray[$key - 1]['HIGHSTART_Z'];  //previous aisle high parking Z
            $previousaisleL_X = $opentotedataarray[$key - 1]['LOWSTART_X'];  //previous aisle low parking X
            $previousaisleL_Z = $opentotedataarray[$key - 1]['LOWSTART_Z'];  //previous aisle low parking Z
            $previousaisleB_X = $opentotedataarray[$key - 1]['BRIDGESTART_X'];  //previous aisle bridge parking X
            $previousaisleB_Z = $opentotedataarray[$key - 1]['BRIDGESTART_Z'];  //previous aisle bridge parking Z
        } else {//First record in array
            $previousloc_X = $previousloc_Z = $previousaisleH_X = $previousaisleH_Z = $previousaisleL_X = $previousaisleL_Z = $previousaisleB_X = $previousaisleB_Z = 0; //set all to 0 if first record
        }

        if ($key !== $openarraycount) { //Not the last record
            $nextloc_X = $opentotedataarray[$key + 1]['LASTLOC_X'];  //next aisle last location X
            $nextloc_Z = $opentotedataarray[$key + 1]['LASTLOC_Z'];  //next aisle last location Z
            $nextaisleH_X = $opentotedataarray[$key + 1]['HIGHSTART_X'];  //next aisle high parking X
            $nextaisleH_Z = $opentotedataarray[$key + 1]['HIGHSTART_Z'];  //next aisle high parking Z
            $nextaisleL_X = $opentotedataarray[$key + 1]['LOWSTART_X'];  //next aisle low parking X
            $nextaisleL_Z = $opentotedataarray[$key + 1]['LOWSTART_Z'];  //next aisle low parking Z
            $nextaisleB_X = $opentotedataarray[$key + 1]['BRIDGESTART_X'];  //next aisle bridge parking X
            $nextaisleB_Z = $opentotedataarray[$key + 1]['BRIDGESTART_Z'];  //next aisle bridge parking Z
        } else {//Last record in array
            $nextloc_X = $nextloc_Z = $nextaisleH_X = $nextaisleH_Z = $nextaisleL_X = $nextaisleL_Z = $nextaisleB_X = $nextaisleB_Z = 0; //set all to 0 if last record
        }

        $outeraisle_high = _casetravel($previousloc_X, $previousloc_Z, $previousaisleH_X, $previousaisleH_Z, $HIGHSTART_X, $HIGHSTART_Z, $FIRSTLOC_X, $FIRSTLOC_Z);
        $outeraisle_low = _casetravel($previousloc_X, $previousloc_Z, $previousaisleL_X, $previousaisleL_Z, $LOWSTART_X, $LOWSTART_Z, $FIRSTLOC_X, $FIRSTLOC_Z);

        if ($casebatch_whse === 6) {
            //Because the "bridge" is actually a travel aisle with different aisles on either side, must calc L to H and H to L
            $outeraisle_hightoL = _casetravel($previousloc_X, $previousloc_Z, $previousaisleH_X, $previousaisleH_Z, $LOWSTART_X, $LOWSTART_Z, $FIRSTLOC_X, $FIRSTLOC_Z);
            $outeraisle_lowtoH = _casetravel($previousloc_X, $previousloc_Z, $previousaisleL_X, $previousaisleL_Z, $HIGHSTART_X, $HIGHSTART_Z, $FIRSTLOC_X, $FIRSTLOC_Z);
        } else {
            $outeraisle_hightoL = 999999;
            $outeraisle_lowtoH = 999999;
        }

        //Are there any bridge restictions.  Returns $bridge_restriction array
        include 'bridge_restriction.php';
        $bridge_inlcude = 1;  //Reset bridge to include
        foreach ($bridge_restriction_array as $val_bridge) {
            if ($val_bridge > $bridge_prev_coordinate && $val_bridge < $bridge_curr_coordianate) {
                $bridge_inlcude = 0;
                break;
            }
        }

        if ($bridge_inlcude == 1) {
            $outeraisle_bridge = _casetravel($previousloc_X, $previousloc_Z, $previousaisleB_X, $previousaisleB_Z, $BRIDGESTART_X, $BRIDGESTART_Z, $FIRSTLOC_X, $FIRSTLOC_Z);
        } else {
            $outeraisle_bridge = 99999999999;
        }
        $outeraisle_min = min($outeraisle_high, $outeraisle_low, $outeraisle_bridge, $outeraisle_hightoL, $outeraisle_lowtoH);


        if (isset($opentotedataarray[$key + 1]['casebatch_cart'])) {
            $nextcart = intval($opentotedataarray[$key + 1]['casebatch_cart']);
        } else {
            $nextcart = 0;
        }

        //calculate time from this P point to next P point.
        if ($casebatch_cart != $nextcart && $casebatch_cart != intval($opentotedataarray[$key - 1]['casebatch_cart'])) {
            $outeraisle_min = abs($FIRSTLOC_X - $cstart_xcoor) + abs($FIRSTLOC_Z - $cstart_zcoor);
            $outeraisle_min += abs($LASTLOC_X - $cstop_xcoor) + abs($LASTLOC_Z - $cstop_zcoor);
            $openbatchcount = 0;
        } elseif ($openbatchcount == 0) {  //first aisle on batch.  what is distance from CSTART to first location
            $outeraisle_min = abs($FIRSTLOC_X - $cstart_xcoor) + abs($FIRSTLOC_Z - $cstart_zcoor);
            $openbatchcount += 1;
        } elseif ($key == $openarraycount) { //if last line of array do normal calc, go back to CSTOP
            $outeraisle_min += abs($LASTLOC_X - $cstop_xcoor) + abs($LASTLOC_Z - $cstop_zcoor);
            $openbatchcount = 0;
        } elseif ($casebatch_cart != $nextcart && $openbatchcount == 0) {//if new batch is on next line and this is first line of batch, go from CSTART to Location the location back to CSTOP
            $outeraisle_min = abs($FIRSTLOC_X - $cstart_xcoor) + abs($FIRSTLOC_Z - $cstart_zcoor);
            $outeraisle_min += abs($LASTLOC_X - $cstop_xcoor) + abs($LASTLOC_Z - $cstop_zcoor);
            $openbatchcount = 0;
        } elseif ($casebatch_cart != $nextcart) {//if new batch is on next line do normal calc then add distance from last location and go back to CSTOP
            $outeraisle_min += abs($LASTLOC_X - $cstop_xcoor) + abs($LASTLOC_Z - $cstop_zcoor);
            $openbatchcount = 0;
        } else { //calc distance from Last location to mid point , add to next mid point, add to next mid point to first loc of next aisle
            //make excpetion for aisle 49 in Sparks. Go from last location on w49 to first location on next aisle
            if (($whsesel == 3 || $whsesel == 6) && substr($opentotedataarray[$key - 1]['casebatch_lastloc'], 0, 3) == 'W49') {
                $outeraisle_min = abs($opentotedataarray[$key - 1]['LASTLOC_X'] - $FIRSTLOC_X) + abs($opentotedataarray[$key - 1]['LASTLOC_Z'] - $FIRSTLOC_Z);
            } else { //normal calc
                $openbatchcount += 1;
            }
        }

        if (is_null($outeraisle_min)) {
            $outeraisle_min = 0;
        }



        $caseequip_equipment = $opentotedataarray[$key]['caseequip_equipment'];
        $casepm_pickloc = $opentotedataarray[$key]['casepm_pickloc'];
        $casepm_units = $opentotedataarray[$key]['casepm_units'];
        $casepm_indirect = $opentotedataarray[$key]['casepm_indirect'];
        $casepm_dropoff = $opentotedataarray[$key]['casepm_dropoff'];
        $casepm_noncon = $opentotedataarray[$key]['casepm_noncon'];
        $casepm_inch_per_min = $opentotedataarray[$key]['casepm_inch_per_min'];
        $casepm_vertinpermin = $opentotedataarray[$key]['casepm_vert_inch_per_min'];
        $casebatch_cubicinch = $opentotedataarray[$key]['casebatch_cubicinch'];

        $calc_picktimeloc = $casebatch_lines * $casepm_pickloc;
        $calc_picktimeunit = $casebatch_cubicinch * $casepm_units;
        $calc_picktimeindirect = $casebatch_lines * $casepm_indirect;
        $calc_picktimedropoff = $casebatch_lines * $casepm_dropoff;
        //only if case volume  is greater that 10,368 IN2
        $calc_picktimenoncon = $casebatch_noncon * $casepm_noncon;

        if ($casepm_inch_per_min > 0) {
            $calc_taveltime = ($caseaisle_inches_inner + $outeraisle_min) / $casepm_inch_per_min;
        } else {
            $calc_taveltime = 0;
        }

        if ($casebatch_vertdist > 0 && $caseequip_equipment == 'ORDERPICKER') {
            if ($casepm_vertinpermin == 0) {
                echo 't';
            }
            $calc_verttime = $casebatch_vertdist / $casepm_vertinpermin;
        } else {
            $calc_verttime = 0;
        }




        $totaltime = $calc_picktimeloc + $calc_picktimeunit + $calc_picktimeindirect + $calc_picktimedropoff + $calc_picktimenoncon + $calc_taveltime + $calc_verttime;

        $data_totetimes[] = "($casebatch_whse, $casebatch_build, $casebatch_cart, '$casebatch_aisle', '$caseequip_equipment', '$casebatch_trips', $casebatch_lines, '$casebatch_firstloc', '$casebatch_lastloc', '$openbatches_printdatetime', '$calc_picktimeloc', '$calc_picktimeunit', '$calc_picktimeindirect', '$calc_picktimedropoff', '$calc_picktimenoncon', $caseaisle_inches_inner, $outeraisle_min,  '$calc_taveltime', '$calc_verttime', '$totaltime')";
    }

    //Add to table casetote_time

    if (!is_null($data_totetimes)) {
        $values3 = implode(',', $data_totetimes);
        $sql3 = "INSERT INTO printvis.casetote_time ($casetotetimes_cols) VALUES $values3  ON DUPLICATE KEY UPDATE casetote_time_equipment=VALUES(casetote_time_equipment), casetote_time_trips=VALUES(casetote_time_trips) , casetote_time_lines=VALUES(casetote_time_lines) , casetote_time_firstloc=VALUES(casetote_time_firstloc) , casetote_time_lastloc=VALUES(casetote_time_lastloc) , casetote_time_printdate=VALUES(casetote_time_printdate) , casetote_time_pickloctime=VALUES(casetote_time_pickloctime) , casetote_time_pickunittime=VALUES(casetote_time_pickunittime) , casetote_time_indirecttime=VALUES(casetote_time_indirecttime) , casetote_time_dropofftime=VALUES(casetote_time_dropofftime) , casetote_time_noncontime=VALUES(casetote_time_noncontime) , casetote_time_inches_inneraisle=VALUES(casetote_time_inches_inneraisle) , casetote_time_inches_outeraisle=VALUES(casetote_time_inches_outeraisle) , casetote_time_traveltime=VALUES(casetote_time_traveltime) , casetote_time_verttime=VALUES(casetote_time_verttime), casetote_time_totaltime=VALUES(casetote_time_totaltime) ";
        $query3 = $conn1->prepare($sql3);
        $query3->execute();
    }

    //Aggregate tote (aisle) times to the batch level accounting for the initial distance from start and final distance to stop

    $batchsummarydata = $conn1->prepare("SELECT 
                                                                                        casetote_time_whse AS CART_WHSE,
                                                                                        casetote_time_build AS CART_BUILD,
                                                                                        casetote_time_cart AS CART_BATCH,
                                                                                        casetote_time_equipment AS CART_EQUIP,
                                                                                        casetote_time_printdate AS PRINT_DATE,
                                                                                        casepm_kiosk,
                                                                                        casepm_batch,
                                                                                        casepm_comp,
                                                                                        casepm_short,
                                                                                        casepm_trip,
                                                                                        casepm_inch_per_min,
                                                                                        SUM(casetote_time_trips) / casepm_tripdivisor AS TOT_TRIPS,
                                                                                        SUM(casetote_time_lines) AS TOT_LINES,
                                                                                        MIN(casetote_time_firstloc) AS FIRST_LOC,
                                                                                        MAX(casetote_time_lastloc) AS LAST_LOC,
                                                                                        SUM(casetote_time_pickloctime) AS TIME_PICKLOC,
                                                                                        SUM(casetote_time_pickunittime) AS TIME_UNIT,
                                                                                        SUM(casetote_time_indirecttime) AS TIME_INDIRECT,
                                                                                        SUM(casetote_time_dropofftime) AS TIME_DROPOFF,
                                                                                        SUM(casetote_time_noncontime) AS TIME_NONCON,
                                                                                        SUM(casetote_time_inches_inneraisle + casetote_time_inches_outeraisle) AS TOT_INCHES_AISLE,
                                                                                        SUM(casetote_time_traveltime) AS TIME_TRAVEL_AISLE,
                                                                                        SUM(casetote_time_verttime) as TIME_TRAVEL_VERT,
                                                                                        SUM(casetote_time_totaltime) AS TIME_SUB_TOTAL,
                                                                                        ABS((SELECT 
                                                                                                        pickprediction_xcoor
                                                                                                    FROM
                                                                                                        printvis.pickprediction_casemap
                                                                                                    WHERE
                                                                                                        casemap_loc = SUBSTR(MIN(casetote_time_firstloc),
                                                                                                            1,
                                                                                                            6)
                                                                                                            AND casemap_whse = casetote_time_whse
                                                                                                            AND casetote_time_build = casemap_building) - (SELECT 
                                                                                                        pickprediction_xcoor
                                                                                                    FROM
                                                                                                        printvis.pickprediction_casemap
                                                                                                    WHERE
                                                                                                        casemap_loc = 'CSTART'
                                                                                                            AND casemap_whse = casetote_time_whse
                                                                                                            AND casetote_time_build = casemap_building) + ABS((SELECT 
                                                                                                                casemap_zcoor
                                                                                                            FROM
                                                                                                                printvis.pickprediction_casemap
                                                                                                            WHERE
                                                                                                                casemap_loc = SUBSTR(MIN(casetote_time_firstloc),
                                                                                                                    1,
                                                                                                                    6)
                                                                                                                    AND casemap_whse = casetote_time_whse
                                                                                                                    AND casetote_time_build = casemap_building) - (SELECT 
                                                                                                                casemap_zcoor
                                                                                                            FROM
                                                                                                                printvis.pickprediction_casemap
                                                                                                            WHERE
                                                                                                                casemap_loc = 'CSTART'
                                                                                                                    AND casemap_whse = casetote_time_whse
                                                                                                                    AND casetote_time_build = casemap_building))) AS START_TO_FIRSTLOC,
                                                                                        ABS((SELECT 
                                                                                                        pickprediction_xcoor
                                                                                                    FROM
                                                                                                        printvis.pickprediction_casemap
                                                                                                    WHERE
                                                                                                        casemap_loc = SUBSTR(MIN(casetote_time_lastloc), 1, 6)
                                                                                                            AND casemap_whse = casetote_time_whse
                                                                                                            AND casetote_time_build = casemap_building) - (SELECT 
                                                                                                        pickprediction_xcoor
                                                                                                    FROM
                                                                                                        printvis.pickprediction_casemap
                                                                                                    WHERE
                                                                                                        casemap_loc = 'CSTOP'
                                                                                                            AND casemap_whse = casetote_time_whse
                                                                                                            AND casetote_time_build = casemap_building)) + ABS((SELECT 
                                                                                                        casemap_zcoor
                                                                                                    FROM
                                                                                                        printvis.pickprediction_casemap
                                                                                                    WHERE
                                                                                                        casemap_loc = SUBSTR(MIN(casetote_time_lastloc), 1, 6)
                                                                                                            AND casemap_whse = casetote_time_whse
                                                                                                            AND casetote_time_build = casemap_building) - (SELECT 
                                                                                                        casemap_zcoor
                                                                                                    FROM
                                                                                                        printvis.pickprediction_casemap
                                                                                                    WHERE
                                                                                                        casemap_loc = 'CSTOP'
                                                                                                            AND casemap_whse = casetote_time_whse
                                                                                                            AND casetote_time_build = casemap_building)) AS LASTLOC_TO_STOP
                                                                                    FROM
                                                                                        printvis.casetote_time
                                                                                            JOIN
                                                                                        printvis.pm_casetimes ON casepm_whse = casetote_time_whse
                                                                                            AND casepm_build = casetote_time_build
                                                                                            AND casepm_equipment = casetote_time_equipment
                                                                                    WHERE
                                                                                        casetote_time_whse = $whsesel
                                                                                    GROUP BY casetote_time_whse , casetote_time_build , casetote_time_cart , casetote_time_equipment , casetote_time_printdate , casepm_kiosk , casepm_batch , casepm_comp , casepm_short , casepm_trip");
    $batchsummarydata->execute();
    $batchsummaryarray = $batchsummarydata->fetchAll(pdo::FETCH_ASSOC);

    foreach ($batchsummaryarray as $key => $value) {
        $CART_WHSE = intval($batchsummaryarray[$key]['CART_WHSE']);
        $CART_BUILD = intval($batchsummaryarray[$key]['CART_BUILD']);
        $CART_BATCH = intval($batchsummaryarray[$key]['CART_BATCH']);
        $CART_EQUIP = $batchsummaryarray[$key]['CART_EQUIP'];
        $PRINT_DATE = $batchsummaryarray[$key]['PRINT_DATE'];
        $casepm_kiosk = $batchsummaryarray[$key]['casepm_kiosk'];
        $casepm_batch = $batchsummaryarray[$key]['casepm_batch'];
        $casepm_comp = $batchsummaryarray[$key]['casepm_comp'];
        $casepm_short_time = $batchsummaryarray[$key]['casepm_short'];
        $TOT_LINES = intval($batchsummaryarray[$key]['TOT_LINES']);
        if ($CART_EQUIP == 'BELTLINE' && $CART_WHSE == 3) {
            $casepm_short = $casepm_short_time * $TOT_LINES;
        } else {
            $casepm_short = $casepm_short_time;
        }

        $casepm_trip = $batchsummaryarray[$key]['casepm_trip'];
        $TOT_TRIPS = intval(ceil($batchsummaryarray[$key]['TOT_TRIPS']));
        $TIME_TRIPCUBE = $TOT_TRIPS * $casepm_trip;

        $FIRST_LOC = $batchsummaryarray[$key]['FIRST_LOC'];
        $LAST_LOC = $batchsummaryarray[$key]['LAST_LOC'];
        $TIME_PICKLOC = $batchsummaryarray[$key]['TIME_PICKLOC'];
        $TIME_UNIT = $batchsummaryarray[$key]['TIME_UNIT'];
        $TIME_INDIRECT = $batchsummaryarray[$key]['TIME_INDIRECT'];
        $TIME_DROPOFF = $batchsummaryarray[$key]['TIME_DROPOFF'];
        $TIME_NONCON = $batchsummaryarray[$key]['TIME_NONCON'];
        $TOT_INCHES_AISLE = intval($batchsummaryarray[$key]['TOT_INCHES_AISLE']);
        $TIME_TRAVEL_AISLE = $batchsummaryarray[$key]['TIME_TRAVEL_AISLE'];
        $TIME_TRAVEL_VERT = $batchsummaryarray[$key]['TIME_TRAVEL_VERT'];
//        $START_TO_FIRSTLOC = intval($batchsummaryarray[$key]['START_TO_FIRSTLOC']);
//        $LASTLOC_TO_STOP = intval($batchsummaryarray[$key]['LASTLOC_TO_STOP']);
        $START_TO_FIRSTLOC = 0;  //now accounted for in "outer aisle travel"
        $LASTLOC_TO_STOP = 0;  //now accounted for in "outer aisle travel"
        $casepm_inch_per_min = intval($batchsummaryarray[$key]['casepm_inch_per_min']);
//        $TOT_INCHES_STARTSTOP = $START_TO_FIRSTLOC + $LASTLOC_TO_STOP;
//        if ($casepm_inch_per_min > 0) {
//            $TIME_STARTSTOP = $TOT_INCHES_STARTSTOP / $casepm_inch_per_min;
//        } else {
//            $TIME_STARTSTOP = 0;
//        }

        $TOT_INCHES_STARTSTOP = 0; //now accounted for in "outer aisle travel"
        $TIME_STARTSTOP = 0; //now accounted for in "outer aisle travel"
        $TIME_SUB_TOTAL = $batchsummaryarray[$key]['TIME_SUB_TOTAL'];
        $TIME_BATCH_FINAL = $casepm_kiosk + $casepm_batch + $casepm_comp + $casepm_short + $TIME_TRIPCUBE + $TIME_PICKLOC + $TIME_UNIT + $TIME_INDIRECT + $TIME_DROPOFF + $TIME_NONCON + $TIME_TRAVEL_AISLE + $TIME_TRAVEL_VERT + $TIME_STARTSTOP;

        $TIME_BATCH_FINAL_max = intval($TIME_BATCH_FINAL);
        if ($TIME_BATCH_FINAL_max == 0) {
            $TIME_BATCH_FINAL_min = $TIME_BATCH_FINAL_max;
        } else {
            $TIME_BATCH_FINAL_min = intval($TIME_BATCH_FINAL_max - 1);
        }
        $batchcompletedata[] = "($CART_WHSE, $CART_BUILD, $CART_BATCH,  '$CART_EQUIP', '$PRINT_DATE', '$casepm_kiosk', '$casepm_batch', '$casepm_comp', '$casepm_short', '$casepm_trip', $TOT_TRIPS, '$TIME_TRIPCUBE', $TOT_LINES, '$FIRST_LOC', '$LAST_LOC','$TIME_PICKLOC', '$TIME_UNIT', '$TIME_INDIRECT', '$TIME_DROPOFF', '$TIME_NONCON', $TOT_INCHES_AISLE,  '$TIME_TRAVEL_AISLE', '$TIME_TRAVEL_VERT', $START_TO_FIRSTLOC, $LASTLOC_TO_STOP, $casepm_inch_per_min, $TOT_INCHES_STARTSTOP,  '$TIME_STARTSTOP',  '$TIME_BATCH_FINAL' )";

        $batchpadded = str_pad($CART_BATCH, 5, "0", STR_PAD_LEFT);
        $datafortaskpred[] = "('$batchpadded', $CART_WHSE, 'CAS', '$CART_EQUIP', $TIME_BATCH_FINAL_min, $TIME_BATCH_FINAL_max, '$todaydatetime' )";
    }

    //Add to table casebatches_time
    if (!is_null($batchcompletedata)) {
        $values4 = implode(',', $batchcompletedata);
        $sql4 = "INSERT INTO printvis.casebatches_time ($casebatchtimes_cols) VALUES $values4
                        ON DUPLICATE KEY UPDATE 
                            casebatches_equipment=VALUES(casebatches_equipment), 
                            casebatches_printdate =VALUES(casebatches_printdate), 
                            casebatches_time_kiosk =VALUES(casebatches_time_kiosk), 
                            casebatches_time_batch  =VALUES(casebatches_time_batch), 
                            casebatches_time_complete =VALUES(casebatches_time_complete), 
                            casebatches_time_short =VALUES(casebatches_time_short), 
                            casebatches_trip =VALUES(casebatches_trip), 
                            casebatches_totaltrips  =VALUES(casebatches_totaltrips), 
                            casebatches_time_tripcube  =VALUES(casebatches_time_tripcube), 
                            casebatches_lines  =VALUES(casebatches_lines), 
                            casebatches_firstloc =VALUES(casebatches_firstloc), 
                            casebatches_lastloc =VALUES(casebatches_lastloc), 
                            casebatches_time_pickloc =VALUES(casebatches_time_pickloc), 
                            casebatches_time_unit =VALUES(casebatches_time_unit), 
                            casebatches_time_indirect=VALUES(casebatches_time_indirect), 
                            casebatches_time_dropoff =VALUES(casebatches_time_dropoff), 
                            casebatches_time_noncon =VALUES(casebatches_time_noncon), 
                            casebatches_aisleinches  =VALUES(casebatches_aisleinches), 
                            casebatches_time_verttravel  =VALUES(casebatches_time_verttravel), 
                            casebatches_time_aisletravel =VALUES(casebatches_time_aisletravel), 
                            casebatches_starttofirst  =VALUES(casebatches_starttofirst), 
                            casebatches_lasttostop =VALUES(casebatches_lasttostop), 
                            casebatches_inchpermin =VALUES(casebatches_inchpermin), 
                            casebatches_startstopinches =VALUES(casebatches_startstopinches), 
                            casebatches_time_startstop =VALUES(casebatches_time_startstop), 
                            casebatches_time_final=VALUES(casebatches_time_final)";
        $query4 = $conn1->prepare($sql4);
        $query4->execute();

        //Add to table casebatches_time_history
        $values9 = implode(',', $batchcompletedata);
        $sql9 = "INSERT IGNORE INTO printvis.casebatches_time_hist ($casebatchtimes_cols) VALUES $values9  ";
        $query9 = $conn1->prepare($sql9);
        $query9->execute();
    }




    if (!is_null($datafortaskpred)) {
        $taskpredcolumns = 'taskpred_id, taskpred_whse, taskpred_function, taskpred_type, taskpred_mintime, taskpred_maxtime, taskpred_updatetime';
        //Add to table taskpred
        $values5 = implode(',', $datafortaskpred);
        $sql5 = "INSERT  INTO printvis.taskpred ($taskpredcolumns) VALUES $values5 ON DUPLICATE KEY UPDATE taskpred_mintime=VALUES(taskpred_mintime), taskpred_maxtime=VALUES(taskpred_maxtime)";
        $query5 = $conn1->prepare($sql5);
        $query5->execute();
    }





    //Cases manifested by batch
    $casesman = $aseriesconn->prepare("SELECT PBCART,PBPTJD, count(*) as BOX_COUNT, sum(case when PBRLJD > 0 then 1 else 0 end) as REL_COUNT
                                                                                     FROM HSIPCORDTA.NOTWPB
                                                                                     WHERE pbwhse = $whsesel  group by PBCART,PBPTJD ");
    $casesman->execute();
    $casesmanarray = $casesman->fetchAll(pdo::FETCH_ASSOC);

    $casesman_cols = 'boxrel_batch, boxrel_printdate, boxrel_boxcount, boxrel_relcount';
    $casereldata = array();

    foreach ($casesmanarray as $key5 => $value) {
        $rel_cart = $casesmanarray[$key5]['PBCART'];
        $rel_date = $casesmanarray[$key5]['PBPTJD'];
        $rel_count = $casesmanarray[$key5]['BOX_COUNT'];
        $rel_relcount = $casesmanarray[$key5]['REL_COUNT'];

        $casereldata[] = "($rel_cart, $rel_date, $rel_count, $rel_relcount)";
    }


    //Add to table casebatchstarttime
    $values6 = implode(',', $casereldata);
    $sql6 = "INSERT  INTO printvis.case_boxesreleased ($casesman_cols) VALUES $values6  ON DUPLICATE KEY UPDATE boxrel_boxcount=VALUES(boxrel_boxcount), boxrel_relcount=VALUES(boxrel_relcount) ";
    $query6 = $conn1->prepare($sql6);
    $query6->execute();
} //end of whsarray loop
//update building number for sparks
$sqlupdate = "UPDATE printvis.casebatchstarttime
                                    INNER JOIN
                                printvis.casebatches_time ON starttime_batch = casebatches_cart 
                            SET 
                                starttime_build = 1
                            WHERE
                                casebatches_whse = 3
                                    AND casebatches_build = 1";
$queryupdate = $conn1->prepare($sqlupdate);
$queryupdate->execute();


