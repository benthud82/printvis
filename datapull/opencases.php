<?php

//include '../sessioninclude.php';

include '../../globalincludes/usa_asys.php';
include '../../connections/conn_printvis.php';
ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../functions/functions_totetimes.php';

$today = date('Y-m-d');
$dayofweek = date('w', strtotime($today));
if ($dayofweek == 1) {
    $yesterday = date('Y-m-d', strtotime("-3 days"));
} else {
    $yesterday = date('Y-m-d', strtotime("-1 day"));
}
$yesterdaytime = ('16:59:59');
$printcutoff = date('Y-m-d H:i:s', strtotime("$yesterday $yesterdaytime"));
$turninches = intval(120);

$notprintedcolumns = 'notprinted_lp, notprinted_whse, notprinted_build,  notprinted_cubinch,notprinted_location, notprinted_equiptype, notprinted_recdate, notprinted_rectime, notprinted_shipzone, notprinted_shipclass, notprinted_cart, notprinted_printdate, notprinted_printtime, notprinted_twoday, notprinted_cutofftime, notprinted_cutoffgroup, notprinted_availdate, notprinted_availhour';
$notprintedcolumns_temp = 'notprinted_lp, notprinted_whse, notprinted_build,  notprinted_cubinch,notprinted_location, notprinted_equiptype, notprinted_recdate, notprinted_rectime, notprinted_shipzone, notprinted_shipclass, notprinted_cart, notprinted_printdate, notprinted_printtime, notprinted_twoday';
$notprintedcolumns_bybatch = 'notprinted_lp, notprinted_whse, notprinted_build,  notprinted_cubinch,notprinted_location, notprinted_equiptype, notprinted_batch, notprinted_aisle';
$casetotetimes_cols = 'casetote_time_whse, casetote_time_build, casetote_time_cart, casetote_time_aisle, casetote_time_equipment, casetote_time_trips, casetote_time_lines, casetote_time_firstloc,'
        . 'casetote_time_lastloc, casetote_time_pickloctime, casetote_time_pickunittime, casetote_time_indirecttime, casetote_time_dropofftime, casetote_time_noncontime, casetote_time_inchestravel, casetote_time_traveltime, casetote_time_totaltime';

$casebatchtimes_cols = 'casebatches_whse,casebatches_build,casebatches_cart,casebatches_equipment,casebatches_time_kiosk,casebatches_time_batch,casebatches_time_complete,casebatches_time_short,casebatches_trip,casebatches_totaltrips,casebatches_time_tripcube,casebatches_lines,casebatches_firstloc,casebatches_lastloc,casebatches_time_pickloc,casebatches_time_unit,casebatches_time_indirect,casebatches_time_dropoff,casebatches_time_noncon,casebatches_aisleinches,casebatches_time_aisletravel,casebatches_starttofirst,casebatches_lasttostop,casebatches_inchpermin,casebatches_startstopinches,casebatches_time_startstop,casebatches_time_final';

$whsearray = array(6, 3.1, 3.2, 9, 2);

foreach ($whsearray as $whse) {
    if ($whse == 3.1) {
        $building = 1;
        $whsesel = 3;
    } elseif ($whse == 3.2) {
        $building = 2;
        $whsesel = 3;
    } else {
        $building = 1;
        $whsesel = $whse;
    }
    //delete cases not printed table
    $sqldelete = "DELETE FROM  printvis.casesnotprinted WHERE notprinted_whse = $whsesel and notprinted_build =  $building";
    $querydelete = $conn1->prepare($sqldelete);
    $querydelete->execute();
    //delete cases not printed table
    $sqldelete = "DELETE FROM  printvis.casesnotprinted_temp WHERE notprinted_whse = $whsesel  and notprinted_build =  $building";
    $querydelete = $conn1->prepare($sqldelete);
    $querydelete->execute();

    //delete cases not printed table by batch
    $sqldelete2 = "DELETE FROM  printvis.casesnotprinted_bybatch WHERE notprinted_whse = $whsesel and notprinted_build = $building";
    $querydelete2 = $conn1->prepare($sqldelete2);
    $querydelete2->execute();

    //delete cases not printed table by batch
    $sqldelete3 = "DELETE FROM  printvis.notprintedcasetote_time WHERE casetote_time_whse = $whsesel and casetote_time_build = $building";
    $querydelete3 = $conn1->prepare($sqldelete3);
    $querydelete3->execute();





//pull in open case batches that have not been printed
    $casesnotprinted = $aseriesconn->prepare("SELECT
                                                                                            PBLP9D,
                                                                                            PBWHSE,
                                                                                            CASE WHEN PBWHSE = 3 and PDLOC# >= 'W400000' then 2 else 1 end as PBBUILD,
                                                                                            PBPTJD,
                                                                                            PBPTHR,
                                                                                            case when PCCVOL = 0 then PCEVOL  * .0610237 else PCCVOL * .0610237 end as CUBIC_INCH,
                                                                                            (PDLOC#) as LOCATION,
                                                                                            case 
                                                                                                when LMTIER = 'C01' then 'PALLETJACK' 
                                                                                                when LMTIER = 'C02' then 'BELTLINE' 
                                                                                                when LMTIER = 'C03' and substr(LMLOC#,6,1) = '1' then 'PALLETJACK' 
                                                                                                when LMTIER in ('C05', 'C06') and substr(LMLOC#,6,1) >= '2'  then 'ORDERPICKER'  
                                                                                                else 'ORDERPICKER' 
                                                                                            end as EQUIP_TYPE,
                                                                                            PBRCJD,
                                                                                            PBRCHM,
                                                                                            PBSHPZ,
                                                                                            PBSHPC,
                                                                                            PBCART,
                                                                                            PBPTJD,
                                                                                            PBPTHM,
                                                                                            CASE
                                                                                                WHEN SUBSTRING(PBTX01, 3, 1) = '2' THEN 1
                                                                                                ELSE 0
                                                                                            END AS TWODAY
                                                                                            FROM HSIPCORDTA.NOTWPD A 
                                                                                            JOIN HSIPCORDTA.NPFCPC on PCITEM = PDITEM
                                                                                            JOIN HSIPCORDTA.NOTWPB on PDWCS# = PBWCS# and PDWKNO = PBWKNO and PBBOX# = PDBOX#
                                                                                            LEFT JOIN HSIPCORDTA.NPFLSM on LMWHSE = PDWHSE and LMLOC# = PDLOC#
                                                                                            WHERE PDWHSE = $whsesel
                                                                                                and CASE WHEN PBWHSE = 3 and PDLOC# >= 'W400000' then 2 else 1 end = $building
                                                                                                and PDBXSZ = 'CSE'
                                                                                                and LMTIER like 'C%'
                                                                                                and PDLOC# not like '%SDS%'
                                                                                                and PCWHSE = 0");
    $casesnotprinted->execute();
    $casesnotprintedarray = $casesnotprinted->fetchAll(pdo::FETCH_ASSOC);



    foreach ($casesnotprintedarray as $key => $value) {
        //write all cases not printed to table for further analysis
        $notprinted_lp = $casesnotprintedarray[$key]['PBLP9D'];
        $notprinted_whse = $casesnotprintedarray[$key]['PBWHSE'];
        $notprinted_build = $casesnotprintedarray[$key]['PBBUILD'];
        $notprinted_cubinch = $casesnotprintedarray[$key]['CUBIC_INCH'];
        $notprinted_location = $casesnotprintedarray[$key]['LOCATION'];
        $notprinted_equiptype = $casesnotprintedarray[$key]['EQUIP_TYPE'];
        $notprinted_recdate = $casesnotprintedarray[$key]['PBRCJD'];
        $notprinted_rectime = $casesnotprintedarray[$key]['PBRCHM'];
        $notprinted_shipzone = $casesnotprintedarray[$key]['PBSHPZ'];
        $notprinted_shipclass = $casesnotprintedarray[$key]['PBSHPC'];
        $notprinted_cart = $casesnotprintedarray[$key]['PBCART'];
        $notprinted_printdate = $casesnotprintedarray[$key]['PBPTJD'];
        $notprinted_printtime = $casesnotprintedarray[$key]['PBPTHM'];
        $notprinted_twoday = $casesnotprintedarray[$key]['TWODAY'];


        $notprinteddata[] = "($notprinted_lp, $notprinted_whse, $notprinted_build,  '$notprinted_cubinch', '$notprinted_location', '$notprinted_equiptype',$notprinted_recdate, $notprinted_rectime, '$notprinted_shipzone', '$notprinted_shipclass', $notprinted_cart,$notprinted_printdate, $notprinted_printtime, $notprinted_twoday )";
    }

    //post the unprinted boxes into a temp file so a join can be performed with the cutoff times table to determine when the order should be available
    $values2 = implode(',', $notprinteddata);
    $sql2 = "INSERT IGNORE INTO printvis.casesnotprinted_temp ($notprintedcolumns_temp) VALUES $values2";
    $query2 = $conn1->prepare($sql2);
    $query2->execute();


    //pull data from the casesnotprinted_temp table and the cutoff time table to determine when order will be available for printing
    $casesavail = $conn1->prepare("SELECT 
                                                                        notprinted_lp,
                                                                        notprinted_whse,
                                                                        notprinted_build,
                                                                        notprinted_cubinch,
                                                                        notprinted_location,
                                                                        notprinted_equiptype,
                                                                        notprinted_recdate,
                                                                        notprinted_rectime,
                                                                        CASE
                                                                            WHEN notprinted_rectime < 999 THEN SUBSTR(notprinted_rectime, 1, 1)
                                                                            ELSE SUBSTR(notprinted_rectime, 1, 2)
                                                                        END  AS hist_rechour,
                                                                        notprinted_shipzone,
                                                                        notprinted_shipclass,
                                                                        notprinted_cart,
                                                                        notprinted_printdate,
                                                                        notprinted_printtime,
                                                                        cutoff_time,
                                                                        cutoff_group,
                                                                        notprinted_twoday
                                                                    FROM
                                                                        printvis.casesnotprinted_temp
                                                                            LEFT JOIN
                                                                        printvis.printcutoff_case ON notprinted_whse = cutoff_DC
                                                                            AND substr(notprinted_shipzone,1,2) = substr(cutoff_zone,1,2)
                                                                    WHERE
                                                                        notprinted_whse = $whsesel and notprinted_build = $building ");
    $casesavail->execute();
    $casesavailarray = $casesavail->fetchAll(pdo::FETCH_ASSOC);


    //loop through and predict order available time.  Write to casesnotprintedtable.
    $values = array();

    $maxrange = 3999;
    $counter = 0;
    $rowcount = count($casesavailarray);

    do {
        if ($maxrange > $rowcount) {  //prevent undefined offset
            $maxrange = $rowcount - 1;
        }

        $notprinteddata2 = array();
        $values = array();

        while ($counter <= $maxrange) { //split into 5,000 lines segments to insert into merge table
            $notprinted_lp = $casesavailarray[$counter]['notprinted_lp'];
            $notprinted_whse = $casesavailarray[$counter]['notprinted_whse'];
            $notprinted_build = $casesavailarray[$counter]['notprinted_build'];
            $notprinted_cubinch = $casesavailarray[$counter]['notprinted_cubinch'];
            $notprinted_location = $casesavailarray[$counter]['notprinted_location'];
            $notprinted_equiptype = $casesavailarray[$counter]['notprinted_equiptype'];
            $notprinted_recdate = $casesavailarray[$counter]['notprinted_recdate'];
            $converted_recdate = _1yydddtogregdate($notprinted_recdate);
            $notprinted_rectime = $casesavailarray[$counter]['notprinted_rectime'];
            $notprinted_rechour = $casesavailarray[$counter]['hist_rechour'];
            $notprinted_shipzone = $casesavailarray[$counter]['notprinted_shipzone'];
            $notprinted_shipclass = $casesavailarray[$counter]['notprinted_shipclass'];
            $notprinted_cart = $casesavailarray[$counter]['notprinted_cart'];
            $notprinted_printdate = $casesavailarray[$counter]['notprinted_printdate'];
            $converted_printdate = _1yydddtogregdate($notprinted_printdate);
            $notprinted_printtime = $casesavailarray[$counter]['notprinted_printtime'];
            $notprinted_twoday = $casesavailarray[$counter]['notprinted_twoday'];
            $cutoff_time = $casesavailarray[$counter]['cutoff_time'];
            if (is_null($cutoff_time)) {
                $cutoff_time = 1700;
            }
            $cutoff_group = $casesavailarray[$counter]['cutoff_group'];

            $predicted_availdate = _printdatepredictor($converted_recdate, $notprinted_rectime, $cutoff_time, $notprinted_shipzone, $notprinted_shipclass, $cutoff_group);
            //set print hour to 7 if predicted print date is greater than red date
            if ($predicted_availdate > $converted_recdate) {
                $predicted_availhour = intval(6);
            } else {
                $predicted_availhour = $notprinted_rechour;
            }

            $notprinteddata2[] = "($notprinted_lp, $notprinted_whse, $notprinted_build,  '$notprinted_cubinch', '$notprinted_location', '$notprinted_equiptype','$converted_recdate', $notprinted_rectime, '$notprinted_shipzone', '$notprinted_shipclass', $notprinted_cart,'$converted_printdate',$notprinted_printtime,  $notprinted_twoday, $cutoff_time,'$cutoff_group', '$predicted_availdate' , $predicted_availhour )";
            $counter += 1;
        }
        $values = implode(',', $notprinteddata2);

        if (empty($values)) {
            break;
        }
        $sql = "INSERT IGNORE INTO printvis.casesnotprinted ($notprintedcolumns) VALUES $values ";
        $query = $conn1->prepare($sql);
        $query->execute();
        $maxrange += 4000;
    } while ($counter <= $rowcount);


    //operator count by equipment type
    $operatorcount = $conn1->prepare("SELECT 
                                                                            equipcount_equipment, equipcount_count
                                                                        FROM
                                                                            printvis.case_equipmentcount
                                                                        WHERE
                                                                            equipcount_whse = $whsesel and equipcount_build = $building ;");
    $operatorcount->execute();
    $operatorcountarray = $operatorcount->fetchAll(pdo::FETCH_ASSOC);

    //loop through available equipment types
    foreach ($operatorcountarray as $key2 => $value) {

        $equipment = $operatorcountarray[$key2]['equipcount_equipment'];
        $equipment_count = $operatorcountarray[$key2]['equipcount_count'];

        //pull data from cases not printed table that matches looped equipment type
        $notprintedbatch = $conn1->prepare("SELECT * FROM printvis.casesnotprinted WHERE notprinted_equiptype = '$equipment' and notprinted_whse = $whsesel and notprinted_build = $building  and notprinted_cart = 0 and notprinted_availdate <= '$today' and notprinted_cutoffgroup not in ('COLGATE','TRUCK') ORDER BY notprinted_location ASC");
        $notprintedbatch->execute();
        $notprintedbatch_array = $notprintedbatch->fetchAll(pdo::FETCH_ASSOC);

        $countbyequipmenttype = count($notprintedbatch_array);
        $batchsizeestimate = ceil($countbyequipmenttype / $equipment_count);
        $batchline = 1;
        $batchcount = 1;
        $notprintedbatchdata = array();
        //estimate batches by number of operators available
        foreach ($notprintedbatch_array as $key3 => $value) {

            if ($batchline > $batchsizeestimate) {  //have reached the max batch line count.  Reset the batch lines and add 1 to the batch count
                $values3 = implode(',', $notprintedbatchdata);
                $sql3 = "INSERT IGNORE INTO printvis.casesnotprinted_bybatch ($notprintedcolumns_bybatch) VALUES $values3";
                $query3 = $conn1->prepare($sql3);
                $query3->execute();

                $notprintedbatchdata = array();
                $batchcount += 1;
                $batchline = 1;
            }
            //create batch estimates until $batchsizeestimate is reached.  Then reset and create more until no open lines remain
            $batchnumb = $equipment . '_' . $batchcount;
            //assign variables and write to table casesnotprinted_bybatch
            $notprinted_lp = $notprintedbatch_array[$key3]['notprinted_lp'];
            $notprinted_whse = $notprintedbatch_array[$key3]['notprinted_whse'];
            $notprinted_build = $notprintedbatch_array[$key3]['notprinted_build'];
            $notprinted_cubinch = $notprintedbatch_array[$key3]['notprinted_cubinch'];
            $notprinted_location = $notprintedbatch_array[$key3]['notprinted_location'];
            $notprinted_equiptype = $notprintedbatch_array[$key3]['notprinted_equiptype'];
            $notprinted_batch = $batchnumb;
            $notprinted_aisle = substr($notprinted_location, 0, 3);



            $notprintedbatchdata[] = "($notprinted_lp, $notprinted_whse, $notprinted_build, '$notprinted_cubinch', '$notprinted_location', '$notprinted_equiptype', '$notprinted_batch', '$notprinted_aisle')";

            if (($countbyequipmenttype - 1) == $key3) {//have reached last line, write to table
                $values3 = implode(',', $notprintedbatchdata);
                $sql3 = "INSERT IGNORE INTO printvis.casesnotprinted_bybatch ($notprintedcolumns_bybatch) VALUES $values3";
                $query3 = $conn1->prepare($sql3);
                $query3->execute();
            }

            $batchline += 1;
        } //end of loop through $notprintedbatch_array
    } //end of loop through $operatorcountarray


    $opentotedata = $conn1->prepare("SELECT 
    notprinted_whse,
    notprinted_build,
    notprinted_batch,
    notprinted_aisle,
    SUM(notprinted_cubinch) AS TRIPS,
    COUNT(*) AS LINE_COUNT,
    @FIRSTLOC:=MIN(notprinted_location) AS FIRSTLOC,
    @LASTLOC:=MAX(notprinted_location) AS LASTLOC,
    casepm_inch_per_min,
    SUM(notprinted_cubinch) AS CUBICINCH,
    @LOWSTART_X:=(SELECT 
            aisle_x
        FROM
            printvis.aisle_coor
                JOIN
            printvis.pickprediction_casemap ON casemap_whse = aisle_whse
                AND casemap_aisle = aisle_id
        WHERE
            casemap_whse = notprinted_whse
                AND casemap_loc = SUBSTR(MAX(notprinted_location), 1, 6)
                AND aisle_location = 'L') AS LOWSTART_X,
    @LOWSTART_Z:=(SELECT 
            aisle_z
        FROM
            printvis.aisle_coor
                JOIN
            printvis.pickprediction_casemap ON casemap_whse = aisle_whse
                AND casemap_aisle = aisle_id
        WHERE
            casemap_whse = notprinted_whse
                AND casemap_loc = SUBSTR(MAX(notprinted_location), 1, 6)
                AND aisle_location = 'L') AS LOWSTART_Z,
    @HIGHSTART_X:=(SELECT 
            aisle_x
        FROM
            printvis.aisle_coor
                JOIN
            printvis.pickprediction_casemap ON casemap_whse = aisle_whse
                AND casemap_aisle = aisle_id
        WHERE
            casemap_whse = notprinted_whse
                AND casemap_loc = SUBSTR(MAX(notprinted_location), 1, 6)
                AND aisle_location = 'H') AS HIGHSTART_X,
    @HIGHSTART_Z:=(SELECT 
            aisle_z
        FROM
            printvis.aisle_coor
                JOIN
            printvis.pickprediction_casemap ON casemap_whse = aisle_whse
                AND casemap_aisle = aisle_id
        WHERE
            casemap_whse = notprinted_whse
                AND casemap_loc = SUBSTR(MAX(notprinted_location), 1, 6)
                AND aisle_location = 'H') AS HIGHSTART_Z,
    @BRIDGESTART_X:=(SELECT 
            aisle_x
        FROM
            printvis.aisle_coor
                JOIN
            printvis.pickprediction_casemap ON casemap_whse = aisle_whse
                AND casemap_aisle = aisle_id
        WHERE
            casemap_whse = notprinted_whse
                AND casemap_loc = SUBSTR(MAX(notprinted_location), 1, 6)
                AND aisle_location = 'B') AS BRIDGESTART_X,
    @BRIDGESTART_Z:=(SELECT 
            aisle_z
        FROM
            printvis.aisle_coor
                JOIN
            printvis.pickprediction_casemap ON casemap_whse = aisle_whse
                AND casemap_aisle = aisle_id
        WHERE
            casemap_whse = notprinted_whse
                AND casemap_loc = SUBSTR(MAX(notprinted_location), 1, 6)
                AND aisle_location = 'B') AS BRIDGESTART_Z,
    @LASTLOC_X:=(SELECT 
            pickprediction_xcoor
        FROM
            printvis.pickprediction_casemap
        WHERE
            SUBSTRING(MAX(notprinted_location),
                1,
                6) = casemap_loc
                AND casemap_whse = notprinted_whse
                AND casemap_building = notprinted_build) AS LASTLOC_X,
    @LASTLOC_Z:=(SELECT 
            casemap_zcoor
        FROM
            printvis.pickprediction_casemap
        WHERE
            SUBSTRING(MAX(notprinted_location),
                1,
                6) = casemap_loc
                AND casemap_whse = notprinted_whse
                AND casemap_building = notprinted_build) AS LASTLOC_Z,
    @FIRSTLOC_X:=(SELECT 
            pickprediction_xcoor
        FROM
            printvis.pickprediction_casemap
        WHERE
            SUBSTRING(MIN(notprinted_location),
                1,
                6) = casemap_loc
                AND casemap_whse = notprinted_whse
                AND casemap_building = notprinted_build) AS FIRSTLOC_X,
    @FIRSTLOC_Z:=(SELECT 
            casemap_zcoor
        FROM
            printvis.pickprediction_casemap
        WHERE
            SUBSTRING(MIN(notprinted_location),
                1,
                6) = casemap_loc
                AND casemap_whse = notprinted_whse
                AND casemap_building = notprinted_build) AS FIRSTLOC_Z,
    (ABS((SELECT 
                    pickprediction_xcoor
                FROM
                    printvis.pickprediction_casemap
                WHERE
                    SUBSTRING(MIN(notprinted_location),
                        1,
                        6) = casemap_loc
                        AND casemap_whse = notprinted_whse
                        AND casemap_building = notprinted_build) - (SELECT 
                    pickprediction_xcoor
                FROM
                    printvis.pickprediction_casemap
                WHERE
                    SUBSTRING(MAX(notprinted_location),
                        1,
                        6) = casemap_loc
                        AND casemap_whse = notprinted_whse
                        AND casemap_building = notprinted_build)) + ABS((SELECT 
                    casemap_zcoor
                FROM
                    printvis.pickprediction_casemap
                WHERE
                    SUBSTRING(MIN(notprinted_location),
                        1,
                        6) = casemap_loc
                        AND casemap_whse = notprinted_whse
                        AND casemap_building = notprinted_build) - (SELECT 
                    casemap_zcoor
                FROM
                    printvis.pickprediction_casemap
                WHERE
                    SUBSTRING(MAX(notprinted_location),
                        1,
                        6) = casemap_loc
                        AND casemap_whse = notprinted_whse
                        AND casemap_building = notprinted_build))) AS INNERAISLETRAVEL,
    notprinted_equiptype,
    casepm_pickloc,
    casepm_units,
    casepm_indirect,
    casepm_dropoff,
    casepm_noncon
FROM
    printvis.casesnotprinted_bybatch
        JOIN
    printvis.pm_casetimes ON casepm_whse = notprinted_whse
        AND casepm_build = notprinted_build
        AND casepm_equipment = notprinted_equiptype
WHERE
    notprinted_whse = $whsesel
        AND notprinted_build = $building
GROUP BY notprinted_whse , notprinted_build , notprinted_batch , notprinted_aisle
ORDER BY notprinted_batch , notprinted_location");
    $opentotedata->execute();
    $opentotedataarray = $opentotedata->fetchAll(pdo::FETCH_ASSOC);


    //What is CSTART
    $cstart = $conn1->prepare("SELECT 
                                                                            pickprediction_xcoor, casemap_zcoor
                                                                        FROM
                                                                            printvis.pickprediction_casemap
                                                                        WHERE
                                                                            casemap_whse = $whsesel
                                                                                AND casemap_building = $building
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
                                                                                AND casemap_building = $building
                                                                                AND casemap_loc = 'CSTOP' ");
    $cstop->execute();
    $cstop_array = $cstop->fetchAll(pdo::FETCH_ASSOC);
    $cstop_xcoor = $cstop_array[0]['pickprediction_xcoor'];
    $cstop_zcoor = $cstop_array[0]['casemap_zcoor'];

    $openbatchcount = 0;
    $openarraycount = count($opentotedataarray) - 1;



    foreach ($opentotedataarray as $key => $value) {

        //calculate estimated completion times by batch/aisle key
        $casebatch_whse = $opentotedataarray[$key]['notprinted_whse'];
        $casebatch_build = $opentotedataarray[$key]['notprinted_build'];
        $casebatch_cart = $opentotedataarray[$key]['notprinted_batch'];
        $casebatch_aisle = $opentotedataarray[$key]['notprinted_aisle'];
        $casebatch_trips = $opentotedataarray[$key]['TRIPS'];
        $casebatch_lines = $opentotedataarray[$key]['LINE_COUNT'];
        $casebatch_firstloc = $opentotedataarray[$key]['FIRSTLOC'];
        $casebatch_lastloc = $opentotedataarray[$key]['LASTLOC'];
        $caseequip_equipment = $opentotedataarray[$key]['notprinted_equiptype'];
        $casepm_pickloc = $opentotedataarray[$key]['casepm_pickloc'];
        $casepm_units = $opentotedataarray[$key]['casepm_units'];
        $casepm_indirect = $opentotedataarray[$key]['casepm_indirect'];
        $casepm_dropoff = $opentotedataarray[$key]['casepm_dropoff'];
        $casepm_noncon = $opentotedataarray[$key]['casepm_noncon'];
        $casepm_inch_per_min = $opentotedataarray[$key]['casepm_inch_per_min'];
        $casebatch_cubicinch = $opentotedataarray[$key]['CUBICINCH'];
        $caseaisle_inches_inner = $opentotedataarray[$key]['INNERAISLETRAVEL'];
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

        if (isset($opentotedataarray[$key + 1]['notprinted_batch'])) {
            $nextcart = intval($opentotedataarray[$key + 1]['notprinted_batch']);
        } else {
            $nextcart = 0;
        }

        //calculate time from this P point to next P point.
        if ($casebatch_cart != $nextcart && $casebatch_cart != intval($opentotedataarray[$key - 1]['notprinted_batch'])) {
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
            if (($whsesel == 3 || $whsesel == 6) && substr($opentotedataarray[$key - 1]['casepm_pickloc'], 0, 3) == 'W49') {
                $outeraisle_min = abs($opentotedataarray[$key - 1]['LASTLOC_X'] - $FIRSTLOC_X) + abs($opentotedataarray[$key - 1]['LASTLOC_Z'] - $FIRSTLOC_Z);
            } else { //normal calc
                $openbatchcount += 1;
            }
        }


        if (is_null($outeraisle_min)) {
            $outeraisle_min = 0;
        }

        $calc_picktimeloc = $casebatch_lines * $casepm_pickloc;
        $calc_picktimeunit = $casebatch_cubicinch * $casepm_units;
        $calc_picktimeindirect = $casebatch_lines * $casepm_indirect;
        $calc_picktimedropoff = $casebatch_lines * $casepm_dropoff;
        $calc_picktimenoncon = $casebatch_lines * $casepm_noncon;

        $caseaisle_inches = ($caseaisle_inches_inner + $outeraisle_min);
        if ($casepm_inch_per_min > 0) {
            $calc_taveltime = $caseaisle_inches / $casepm_inch_per_min;
        } else {
            $calc_taveltime = 0;
        }


        $totaltime = $calc_picktimeloc + $calc_picktimeunit + $calc_picktimeindirect + $calc_picktimedropoff + $calc_picktimenoncon + $calc_taveltime;

        $data_totetimes[] = "($casebatch_whse, $casebatch_build, '$casebatch_cart', '$casebatch_aisle', '$caseequip_equipment', '$casebatch_trips', $casebatch_lines, '$casebatch_firstloc', '$casebatch_lastloc', '$calc_picktimeloc', '$calc_picktimeunit', '$calc_picktimeindirect', '$calc_picktimedropoff', '$calc_picktimenoncon', $caseaisle_inches, '$calc_taveltime', '$totaltime')";
    }

    if (!empty($data_totetimes)) {
        //Add to table casetote_time
        $values3 = implode(',', $data_totetimes);
        $sql3 = "INSERT IGNORE INTO printvis.notprintedcasetote_time ($casetotetimes_cols) VALUES $values3";
        $query3 = $conn1->prepare($sql3);
        $query3->execute();
    }
    //Aggregate tote (aisle) times to the batch level accounting for the initial distance from start and final distance to stop
    $batchsummarydata = $conn1->prepare("SELECT 
                                                                                        casetote_time_whse AS CART_WHSE,
                                                                                        casetote_time_build AS CART_BUILD,
                                                                                        casetote_time_cart AS CART_BATCH,
                                                                                        casetote_time_equipment AS CART_EQUIP,
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
                                                                                        SUM(casetote_time_inchestravel) AS TOT_INCHES_AISLE,
                                                                                        SUM(casetote_time_traveltime) AS TIME_TRAVEL_AISLE,
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
                                                                                        printvis.notprintedcasetote_time
                                                                                            JOIN
                                                                                        printvis.pm_casetimes ON casepm_whse = casetote_time_whse
                                                                                            AND casepm_build = casetote_time_build
                                                                                            AND casepm_equipment = casetote_time_equipment
                                                                                    WHERE
                                                                                        casetote_time_whse = $whsesel
                                                                                    GROUP BY casetote_time_whse , casetote_time_build , casetote_time_cart , casetote_time_equipment ,  casepm_kiosk , casepm_batch , casepm_comp , casepm_short , casepm_trip");
    $batchsummarydata->execute();
    $batchsummaryarray = $batchsummarydata->fetchAll(pdo::FETCH_ASSOC);

    foreach ($batchsummaryarray as $key => $value) {
        $CART_WHSE = intval($batchsummaryarray[$key]['CART_WHSE']);
        $CART_BUILD = intval($batchsummaryarray[$key]['CART_BUILD']);
        $CART_BATCH = ($batchsummaryarray[$key]['CART_BATCH']);
        $CART_EQUIP = $batchsummaryarray[$key]['CART_EQUIP'];
        $casepm_kiosk = $batchsummaryarray[$key]['casepm_kiosk'];
        $casepm_batch = $batchsummaryarray[$key]['casepm_batch'];
        $casepm_comp = $batchsummaryarray[$key]['casepm_comp'];
        $casepm_short_time = $batchsummaryarray[$key]['casepm_short'];
        $TOT_LINES = intval($batchsummaryarray[$key]['TOT_LINES']);
        if ($CART_EQUIP == 'BELTLINE') {
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
        $TIME_NONCON = 1;
        $TOT_INCHES_AISLE = intval($batchsummaryarray[$key]['TOT_INCHES_AISLE']);
        $TIME_TRAVEL_AISLE = $batchsummaryarray[$key]['TIME_TRAVEL_AISLE'];
        $START_TO_FIRSTLOC = intval($batchsummaryarray[$key]['START_TO_FIRSTLOC']);
        $LASTLOC_TO_STOP = intval($batchsummaryarray[$key]['LASTLOC_TO_STOP']);
        $casepm_inch_per_min = intval($batchsummaryarray[$key]['casepm_inch_per_min']);
        $TOT_INCHES_STARTSTOP = $START_TO_FIRSTLOC + $LASTLOC_TO_STOP;
        if ($casepm_inch_per_min > 0) {
            $TIME_STARTSTOP = $TOT_INCHES_STARTSTOP / $casepm_inch_per_min;
        } else {
            $TIME_STARTSTOP = 0;
        }

        if ($CART_EQUIP == 'ORDERPICKER') {
            $verticaltravel = 6;  //hard coded vertical travel time estimate for order picker
        } else {
            $verticaltravel = 0;
        }

        $TIME_SUB_TOTAL = $batchsummaryarray[$key]['TIME_SUB_TOTAL'];
        $TIME_BATCH_FINAL = $casepm_kiosk + $casepm_batch + $casepm_comp + $casepm_short + $TIME_TRIPCUBE + $TIME_PICKLOC + $TIME_UNIT + $TIME_INDIRECT + $TIME_DROPOFF + $TIME_NONCON + $TIME_TRAVEL_AISLE + $TIME_STARTSTOP + $verticaltravel;

        $batchcompletedata[] = "($CART_WHSE, $CART_BUILD, '$CART_BATCH',  '$CART_EQUIP',  '$casepm_kiosk', '$casepm_batch', '$casepm_comp', '$casepm_short', '$casepm_trip', $TOT_TRIPS, '$TIME_TRIPCUBE', $TOT_LINES, '$FIRST_LOC', '$LAST_LOC','$TIME_PICKLOC', '$TIME_UNIT', '$TIME_INDIRECT', '$TIME_DROPOFF', '$TIME_NONCON', $TOT_INCHES_AISLE,  '$TIME_TRAVEL_AISLE', $START_TO_FIRSTLOC, $LASTLOC_TO_STOP, $casepm_inch_per_min, $TOT_INCHES_STARTSTOP,  '$TIME_STARTSTOP',  '$TIME_BATCH_FINAL' )";
    }

    //delete cases not printed table by batch
    $sqldelete3 = "DELETE FROM  printvis.notprintedcasebatches_time WHERE casebatches_whse = $whsesel and casebatches_build = $building";
    $querydelete3 = $conn1->prepare($sqldelete3);
    $querydelete3->execute();

    if (!empty($batchcompletedata)) {
        //Add to table casetote_time
        $values4 = implode(',', $batchcompletedata);
        $sql4 = "INSERT IGNORE INTO printvis.notprintedcasebatches_time ($casebatchtimes_cols) VALUES $values4";
        $query4 = $conn1->prepare($sql4);
        $query4->execute();
    }

    include '../timezoneset.php';
    $now = date('Y-m-d H.i.s');
    $localtime = date('H:i');
    $printcutoff = '17:07';
    $today = date('Y-m-d');

    if ($localtime > $printcutoff) {
        $case_notprintedtime = 0;
        $case_notprintedtime_formatted = _convertToHoursMins($case_notprintedtime);
    } else {  //not after printcutoff time.  Proceed.
        $hdr_casesnotprinted = $conn1->prepare("SELECT sum(casebatches_time_final) as NOTPRINTEDTIME 
                                                                                FROM printvis.notprintedcasebatches_time 
                                                                                WHERE casebatches_whse = $whsesel and casebatches_build = $building");
        $hdr_casesnotprinted->execute();
        $hdr_casesnotprintedarray = $hdr_casesnotprinted->fetchAll(pdo::FETCH_ASSOC);
        $case_notprintedtime = $hdr_casesnotprintedarray[0]['NOTPRINTEDTIME'];
        $case_notprintedtime_formatted = _convertToHoursMins($case_notprintedtime);
    }

    $hdr_casesbeingpicked = $conn1->prepare("SELECT 
                                                                        SUM(CASE
                                                                            WHEN
                                                                                (casebatches_time_final - (TIMESTAMPDIFF(MINUTE,
                                                                                    starttime_starttime,
                                                                                    NOW()) - (CASE
                                                                                    WHEN starttime_whse = 7 THEN 60
                                                                                    WHEN starttime_whse = 3 THEN 180
                                                                                    ELSE 0
                                                                                END))) < 0
                                                                            THEN
                                                                                0
                                                                            ELSE casebatches_time_final - (TIMESTAMPDIFF(MINUTE,
                                                                                starttime_starttime,
                                                                                NOW()) - (CASE
                                                                                WHEN starttime_whse = 7 THEN 60
                                                                                WHEN starttime_whse = 3 THEN 180
                                                                                ELSE 0
                                                                            END))
                                                                        END) AS EST_MIN_REMAINING
                                                                    FROM
                                                                        printvis.casebatches_time
                                                                            JOIN
                                                                        printvis.casebatchstarttime A ON starttime_whse = casebatches_whse
                                                                            AND starttime_build = casebatches_build
                                                                            AND starttime_batch = casebatches_cart
                                                                            LEFT JOIN printvis.casebatchdelete on starttime_batch = casedelete_batch
                                                                    WHERE  casedelete_batch IS NULL and
                                                                        (A.starttime_starttime) IN (SELECT 
                                                                                MAX((B.starttime_starttime))
                                                                            FROM
                                                                                printvis.casebatchstarttime B
                                                                            WHERE
                                                                                A.starttime_batch = B.starttime_batch)
                                                                            AND (A.starttime_starttime) IN (SELECT 
                                                                                MAX((C.starttime_starttime))
                                                                            FROM
                                                                                printvis.casebatchstarttime C
                                                                            WHERE
                                                                                A.starttime_tsm = C.starttime_tsm)
                                                                          AND starttime_whse = $whsesel and starttime_build = $building ");
    $hdr_casesbeingpicked->execute();
    $hdr_casesbeingpickedarray = $hdr_casesbeingpicked->fetchAll(pdo::FETCH_ASSOC);
    $case_beingpickedtime = $hdr_casesbeingpickedarray[0]['EST_MIN_REMAINING'];
    if ($case_beingpickedtime == NULL) {
        $case_beingpickedtime = '0';
        $case_beingpickedtime_formatted = '00:00';
    } else {
        $case_beingpickedtime_formatted = _convertToHoursMins($case_beingpickedtime);
    }




    $hdr_casesprinted = $conn1->prepare("SELECT 
                                                                                SUM(casebatches_time_final * CASE
                                                                                    WHEN (1 - (boxrel_relcount / boxrel_boxcount)) IS NULL THEN 1
                                                                                    ELSE (1 - (boxrel_relcount / boxrel_boxcount))
                                                                                END) AS TOTTIME
                                                                            FROM
                                                                                printvis.casebatches_time
                                                                                    LEFT JOIN
                                                                                printvis.casebatchstarttime A ON starttime_whse = casebatches_whse
                                                                                    AND starttime_build = casebatches_build
                                                                                    AND starttime_batch = casebatches_cart
                                                                                    LEFT JOIN
                                                                                printvis.case_boxesreleased ON boxrel_batch = casebatches_cart
                                                                                    LEFT JOIN
                                                                                printvis.casebatchdelete ON casebatches_cart = casedelete_batch
                                                                            WHERE
                                                                                starttime_tsm IS NULL
                                                                                    AND casedelete_batch IS NULL
                                                                                    AND casebatches_whse = $whsesel
                                                                                    AND casebatches_build = $building");
    $hdr_casesprinted->execute();
    $hdr_casesprintedarray = $hdr_casesprinted->fetchAll(pdo::FETCH_ASSOC);
    $case_printtime = $hdr_casesprintedarray[0]['TOTTIME'];
    if ($case_printtime == NULL) {
        $case_printtime = '0';
        $case_printtime_formatted = '00:00';
    } else {
        $case_printtime_formatted = _convertToHoursMins($case_printtime);
    }


    $case_total_time = _convertToHoursMins($case_printtime + $case_beingpickedtime + $case_notprintedtime);


    switch ($whsesel) {
        case 3:
            $hourssql = "SELECT 
    @SCHEDULED_HOURS:=(UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_ENDTIME)) - UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_STARTTIME))) / (3600) AS SCHEDULED_HOURS,
    @WORK_HOURS:=CASE
        WHEN @SCHEDULED_HOURS < 4 THEN @SCHEDULED_HOURS - .25
        WHEN @SCHEDULED_HOURS < 6.5 THEN @SCHEDULED_HOURS - .75
        WHEN @SCHEDULED_HOURS < 8.75 THEN @SCHEDULED_HOURS - 1
        ELSE @SCHEDULED_HOURS - 1.25
    END AS WORK_HOURS,
    @MAX_EFFECTIVE:=@WORK_HOURS / @SCHEDULED_HOURS AS MAX_EFFECTIVE,
    @PROJECTED_EFFECTIVE:=@MAX_EFFECTIVE * .9 AS PROJECTED_EFFECTIVE,
    @ESTIMATED_ONTASK_HOURS:=@SCHEDULED_HOURS * @PROJECTED_EFFECTIVE AS ESTIMATED_ONTASK_HOURS,
    @ELAPSED_HOURS:=(UNIX_TIMESTAMP(DATE_SUB(CURRENT_TIMESTAMP,
                INTERVAL 3 HOUR)) - UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_STARTTIME))) / (3600) AS ELAPSED_HOURS,
    @ELAPSED_HOURS_ON_TASK_HOURS:=@ELAPSED_HOURS * @PROJECTED_EFFECTIVE AS ELAPSED_HOURS_ON_TASK_HOURS,
    SUM(CASE
        WHEN
            UNIX_TIMESTAMP(DATE_SUB(CURRENT_TIMESTAMP,
                        INTERVAL 3 HOUR)) <= UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_STARTTIME))
        THEN
            t.ESTIMATED_ONTASK_HOURS
        ELSE (t.ESTIMATED_ONTASK_HOURS - t.ELAPSED_HOURS_ON_TASK_HOURS)
    END) AS REMAINHOURS
FROM
    printvis.tsmshift a,
    (SELECT 
        SHIFT_TSMNUM,
            @SCHEDULED_HOURS:=(UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_ENDTIME)) - UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_STARTTIME))) / (3600) AS SCHEDULED_HOURS,
            @WORK_HOURS:=CASE
                WHEN @SCHEDULED_HOURS < 4 THEN @SCHEDULED_HOURS - .25
                WHEN @SCHEDULED_HOURS < 6.5 THEN @SCHEDULED_HOURS - .75
                WHEN @SCHEDULED_HOURS < 8.75 THEN @SCHEDULED_HOURS - 1
                ELSE @SCHEDULED_HOURS - 1.25
            END AS WORK_HOURS,
            @MAX_EFFECTIVE:=@WORK_HOURS / @SCHEDULED_HOURS AS MAX_EFFECTIVE,
            @PROJECTED_EFFECTIVE:=@MAX_EFFECTIVE * .9 AS PROJECTED_EFFECTIVE,
            @ESTIMATED_ONTASK_HOURS:=@SCHEDULED_HOURS * @PROJECTED_EFFECTIVE AS ESTIMATED_ONTASK_HOURS,
            @ELAPSED_HOURS:=(UNIX_TIMESTAMP(DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 3 HOUR)) - UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_STARTTIME))) / (3600) AS ELAPSED_HOURS,
            @ELAPSED_HOURS_ON_TASK_HOURS:=@ELAPSED_HOURS * @PROJECTED_EFFECTIVE AS ELAPSED_HOURS_ON_TASK_HOURS
    FROM
        printvis.tsmshift
    WHERE
        SHIFT_WHSE = 3 AND SHIFT_BUILD = 2
            AND SHIFT_INCLUDEHOURS = 1
            AND SHIFT_CORL = 'CASE') t
WHERE
    SHIFT_WHSE = 3 AND SHIFT_BUILD = 2
        AND SHIFT_INCLUDEHOURS = 1
        AND SHIFT_CORL = 'CASE'
        AND t.SHIFT_TSMNUM = a.SHIFT_TSMNUM";

            break;
        case 7:
            $hourssql = "SELECT 
    @SCHEDULED_HOURS:=(UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_ENDTIME)) - UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_STARTTIME))) / (3600) AS SCHEDULED_HOURS,
    @WORK_HOURS:=CASE
        WHEN @SCHEDULED_HOURS < 4 THEN @SCHEDULED_HOURS - .25
        WHEN @SCHEDULED_HOURS < 6.5 THEN @SCHEDULED_HOURS - .75
        WHEN @SCHEDULED_HOURS < 8.75 THEN @SCHEDULED_HOURS - 1
        ELSE @SCHEDULED_HOURS - 1.25
    END AS WORK_HOURS,
    @MAX_EFFECTIVE:=@WORK_HOURS / @SCHEDULED_HOURS AS MAX_EFFECTIVE,
    @PROJECTED_EFFECTIVE:=@MAX_EFFECTIVE * .9 AS PROJECTED_EFFECTIVE,
    @ESTIMATED_ONTASK_HOURS:=@SCHEDULED_HOURS * @PROJECTED_EFFECTIVE AS ESTIMATED_ONTASK_HOURS,
    @ELAPSED_HOURS:=(UNIX_TIMESTAMP(DATE_SUB(CURRENT_TIMESTAMP,
                INTERVAL 1 HOUR)) - UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_STARTTIME))) / (3600) AS ELAPSED_HOURS,
    @ELAPSED_HOURS_ON_TASK_HOURS:=@ELAPSED_HOURS * @PROJECTED_EFFECTIVE AS ELAPSED_HOURS_ON_TASK_HOURS,
    SUM(CASE
        WHEN
            UNIX_TIMESTAMP(DATE_SUB(CURRENT_TIMESTAMP,
                        INTERVAL 1 HOUR)) <= UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_STARTTIME))
        THEN
            t.ESTIMATED_ONTASK_HOURS
        ELSE (t.ESTIMATED_ONTASK_HOURS - t.ELAPSED_HOURS_ON_TASK_HOURS)
    END) AS REMAINHOURS
FROM
    printvis.tsmshift a,
    (SELECT 
        SHIFT_TSMNUM,
            @SCHEDULED_HOURS:=(UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_ENDTIME)) - UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_STARTTIME))) / (3600) AS SCHEDULED_HOURS,
            @WORK_HOURS:=CASE
                WHEN @SCHEDULED_HOURS < 4 THEN @SCHEDULED_HOURS - .25
                WHEN @SCHEDULED_HOURS < 6.5 THEN @SCHEDULED_HOURS - .75
                WHEN @SCHEDULED_HOURS < 8.75 THEN @SCHEDULED_HOURS - 1
                ELSE @SCHEDULED_HOURS - 1.25
            END AS WORK_HOURS,
            @MAX_EFFECTIVE:=@WORK_HOURS / @SCHEDULED_HOURS AS MAX_EFFECTIVE,
            @PROJECTED_EFFECTIVE:=@MAX_EFFECTIVE * .9 AS PROJECTED_EFFECTIVE,
            @ESTIMATED_ONTASK_HOURS:=@SCHEDULED_HOURS * @PROJECTED_EFFECTIVE AS ESTIMATED_ONTASK_HOURS,
            @ELAPSED_HOURS:=(UNIX_TIMESTAMP(DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 1 HOUR)) - UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_STARTTIME))) / (3600) AS ELAPSED_HOURS,
            @ELAPSED_HOURS_ON_TASK_HOURS:=@ELAPSED_HOURS * @PROJECTED_EFFECTIVE AS ELAPSED_HOURS_ON_TASK_HOURS
    FROM
        printvis.tsmshift
    WHERE
        SHIFT_WHSE = 7 AND SHIFT_BUILD = 1
            AND SHIFT_INCLUDEHOURS = 1
            AND SHIFT_CORL = 'CASE') t
WHERE
    SHIFT_WHSE = 7 AND SHIFT_BUILD = 1
        AND SHIFT_INCLUDEHOURS = 1
        AND SHIFT_CORL = 'CASE'
        AND t.SHIFT_TSMNUM = a.SHIFT_TSMNUM";

            break;
        case 9:
            $hourssql = "SELECT 
    @SCHEDULED_HOURS:=(UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_ENDTIME)) - UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_STARTTIME))) / (3600) AS SCHEDULED_HOURS,
    @WORK_HOURS:=CASE
        WHEN @SCHEDULED_HOURS < 4 THEN @SCHEDULED_HOURS - .25
        WHEN @SCHEDULED_HOURS < 6.5 THEN @SCHEDULED_HOURS - .75
        WHEN @SCHEDULED_HOURS < 8.75 THEN @SCHEDULED_HOURS - 1
        ELSE @SCHEDULED_HOURS - 1.25
    END AS WORK_HOURS,
    @MAX_EFFECTIVE:=@WORK_HOURS / @SCHEDULED_HOURS AS MAX_EFFECTIVE,
    @PROJECTED_EFFECTIVE:=@MAX_EFFECTIVE * .9 AS PROJECTED_EFFECTIVE,
    @ESTIMATED_ONTASK_HOURS:=@SCHEDULED_HOURS * @PROJECTED_EFFECTIVE AS ESTIMATED_ONTASK_HOURS,
    @ELAPSED_HOURS:=(UNIX_TIMESTAMP(DATE_SUB(CURRENT_TIMESTAMP,
                INTERVAL 0 HOUR)) - UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_STARTTIME))) / (3600) AS ELAPSED_HOURS,
    @ELAPSED_HOURS_ON_TASK_HOURS:=@ELAPSED_HOURS * @PROJECTED_EFFECTIVE AS ELAPSED_HOURS_ON_TASK_HOURS,
    SUM(CASE
        WHEN
            UNIX_TIMESTAMP(DATE_SUB(CURRENT_TIMESTAMP,
                        INTERVAL 0 HOUR)) <= UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_STARTTIME))
        THEN
            t.ESTIMATED_ONTASK_HOURS
        ELSE (t.ESTIMATED_ONTASK_HOURS - t.ELAPSED_HOURS_ON_TASK_HOURS)
    END) AS REMAINHOURS
FROM
    printvis.tsmshift a,
    (SELECT 
        SHIFT_TSMNUM,
            @SCHEDULED_HOURS:=(UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_ENDTIME)) - UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_STARTTIME))) / (3600) AS SCHEDULED_HOURS,
            @WORK_HOURS:=CASE
                WHEN @SCHEDULED_HOURS < 4 THEN @SCHEDULED_HOURS - .25
                WHEN @SCHEDULED_HOURS < 6.5 THEN @SCHEDULED_HOURS - .75
                WHEN @SCHEDULED_HOURS < 8.75 THEN @SCHEDULED_HOURS - 1
                ELSE @SCHEDULED_HOURS - 1.25
            END AS WORK_HOURS,
            @MAX_EFFECTIVE:=@WORK_HOURS / @SCHEDULED_HOURS AS MAX_EFFECTIVE,
            @PROJECTED_EFFECTIVE:=@MAX_EFFECTIVE * .9 AS PROJECTED_EFFECTIVE,
            @ESTIMATED_ONTASK_HOURS:=@SCHEDULED_HOURS * @PROJECTED_EFFECTIVE AS ESTIMATED_ONTASK_HOURS,
            @ELAPSED_HOURS:=(UNIX_TIMESTAMP(DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 0 HOUR)) - UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_STARTTIME))) / (3600) AS ELAPSED_HOURS,
            @ELAPSED_HOURS_ON_TASK_HOURS:=@ELAPSED_HOURS * @PROJECTED_EFFECTIVE AS ELAPSED_HOURS_ON_TASK_HOURS
    FROM
        printvis.tsmshift
    WHERE
        SHIFT_WHSE = 9 AND SHIFT_BUILD = 1
            AND SHIFT_INCLUDEHOURS = 1
            AND SHIFT_CORL = 'CASE') t
WHERE
    SHIFT_WHSE = 9 AND SHIFT_BUILD = 1
        AND SHIFT_INCLUDEHOURS = 1
        AND SHIFT_CORL = 'CASE'
        AND t.SHIFT_TSMNUM = a.SHIFT_TSMNUM";

            break;
        case 6:
            $hourssql = "SELECT 
    @SCHEDULED_HOURS:=(UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_ENDTIME)) - UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_STARTTIME))) / (3600) AS SCHEDULED_HOURS,
    @WORK_HOURS:=CASE
        WHEN @SCHEDULED_HOURS < 4 THEN @SCHEDULED_HOURS - .25
        WHEN @SCHEDULED_HOURS < 6.5 THEN @SCHEDULED_HOURS - .75
        WHEN @SCHEDULED_HOURS < 8.75 THEN @SCHEDULED_HOURS - 1
        ELSE @SCHEDULED_HOURS - 1.25
    END AS WORK_HOURS,
    @MAX_EFFECTIVE:=@WORK_HOURS / @SCHEDULED_HOURS AS MAX_EFFECTIVE,
    @PROJECTED_EFFECTIVE:=@MAX_EFFECTIVE * .9 AS PROJECTED_EFFECTIVE,
    @ESTIMATED_ONTASK_HOURS:=@SCHEDULED_HOURS * @PROJECTED_EFFECTIVE AS ESTIMATED_ONTASK_HOURS,
    @ELAPSED_HOURS:=(UNIX_TIMESTAMP(DATE_SUB(CURRENT_TIMESTAMP,
                INTERVAL 0 HOUR)) - UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_STARTTIME))) / (3600) AS ELAPSED_HOURS,
    @ELAPSED_HOURS_ON_TASK_HOURS:=@ELAPSED_HOURS * @PROJECTED_EFFECTIVE AS ELAPSED_HOURS_ON_TASK_HOURS,
    SUM(CASE
        WHEN
            UNIX_TIMESTAMP(DATE_SUB(CURRENT_TIMESTAMP,
                        INTERVAL 0 HOUR)) <= UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_STARTTIME))
        THEN
            t.ESTIMATED_ONTASK_HOURS
        ELSE (t.ESTIMATED_ONTASK_HOURS - t.ELAPSED_HOURS_ON_TASK_HOURS)
    END) AS REMAINHOURS
FROM
    printvis.tsmshift a,
    (SELECT 
        SHIFT_TSMNUM,
            @SCHEDULED_HOURS:=(UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_ENDTIME)) - UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_STARTTIME))) / (3600) AS SCHEDULED_HOURS,
            @WORK_HOURS:=CASE
                WHEN @SCHEDULED_HOURS < 4 THEN @SCHEDULED_HOURS - .25
                WHEN @SCHEDULED_HOURS < 6.5 THEN @SCHEDULED_HOURS - .75
                WHEN @SCHEDULED_HOURS < 8.75 THEN @SCHEDULED_HOURS - 1
                ELSE @SCHEDULED_HOURS - 1.25
            END AS WORK_HOURS,
            @MAX_EFFECTIVE:=@WORK_HOURS / @SCHEDULED_HOURS AS MAX_EFFECTIVE,
            @PROJECTED_EFFECTIVE:=@MAX_EFFECTIVE * .9 AS PROJECTED_EFFECTIVE,
            @ESTIMATED_ONTASK_HOURS:=@SCHEDULED_HOURS * @PROJECTED_EFFECTIVE AS ESTIMATED_ONTASK_HOURS,
            @ELAPSED_HOURS:=(UNIX_TIMESTAMP(DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 0 HOUR)) - UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_STARTTIME))) / (3600) AS ELAPSED_HOURS,
            @ELAPSED_HOURS_ON_TASK_HOURS:=@ELAPSED_HOURS * @PROJECTED_EFFECTIVE AS ELAPSED_HOURS_ON_TASK_HOURS
    FROM
        printvis.tsmshift
    WHERE
        SHIFT_WHSE = 6 AND SHIFT_BUILD = 1
            AND SHIFT_INCLUDEHOURS = 1
            AND SHIFT_CORL = 'CASE') t
WHERE
    SHIFT_WHSE = 6 AND SHIFT_BUILD = 1
        AND SHIFT_INCLUDEHOURS = 1
        AND SHIFT_CORL = 'CASE'
        AND t.SHIFT_TSMNUM = a.SHIFT_TSMNUM";

            break;
        default:
            $hourssql = "SELECT 
                                sum(
                                    (CASE
                                        WHEN UNIX_TIMESTAMP(DATE_SUB(CURRENT_TIMESTAMP,
                                                    INTERVAL 3 HOUR))  <= UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_STARTTIME))THEN (SHIFT_STANDHOURS + SHIFT_OTHOURS) * 60
        ELSE ((SHIFT_STANDHOURS + SHIFT_OTHOURS) * 60) - (UNIX_TIMESTAMP(DATE_SUB(CURRENT_TIMESTAMP,
                    INTERVAL 0 HOUR)) - UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_STARTTIME)) + 30) / 60 * (SHIFT_STANDHOURS / 8)
                                    END)) AS REMAINHOURS
                                FROM
                                    printvis.tsmshift
                                WHERE
                                    SHIFT_WHSE = $whsesel AND SHIFT_BUILD = $building
                                        AND SHIFT_INCLUDEHOURS = 1
                                        AND SHIFT_CORL = 'CASE';";

            break;
    }
    $hoursavailsql = $conn1->prepare("$hourssql");
    $hoursavailsql->execute();
    $hoursavail_array = $hoursavailsql->fetchAll(pdo::FETCH_ASSOC);

    $hoursremain = $hoursavail_array[0]['REMAINHOURS'] * 60;

//if (intval($case_notprintedtime) > 0) {
//insert times into MySQL table for daily tracking
    $result1 = $conn1->prepare("INSERT INTO printvis.casevis_hourbuckets (hourbucket_whse, hourbucket_build, hourbucket_datetime, hourbucket_notprinted, hourbucket_printed, hourbucket_picking, hourbucket_remaining) values ($whsesel, $building, '$now', '$case_notprintedtime',  '$case_printtime', '$case_beingpickedtime', '$hoursremain')");
    $result1->execute();
//}
////Post to case_actuallaborbyhour for Beltline
    $result2 = $conn1->prepare("INSERT INTO printvis.case_actuallaborbyhour (caseactbyhour_whse,caseactbyhour_build,caseactbyhour_date,caseactbyhour_hour, caseactbyhour_equip, caseactbyhour_minutes)
                                                           SELECT 
                                                                    $whsesel,
                                                                    $building,
                                                                    CURDATE(),
                                                                    CASE
                                                                        WHEN $whsesel = 3 AND HOUR(NOW()) - 3 < 6 THEN 6
                                                                        WHEN $whsesel = 3 THEN HOUR(NOW()) - 3
                                                                        WHEN $whsesel = 7 AND HOUR(NOW()) - 1 < 6 THEN 6
                                                                        WHEN $whsesel = 7 THEN HOUR(NOW()) - 1
                                                                        ELSE HOUR(NOW())
                                                                    END AS CURHOUR,
                                                                    EQUIP,
                                                                    SUM(MINS) AS caseactbyhour_minutes
                                                                FROM
                                                                    (SELECT 
                                                                        casebatches_equipment AS EQUIP,
                                                                            SUM(casebatches_time_final) AS MINS
                                                                    FROM
                                                                        printvis.notprintedcasebatches_time
                                                                    WHERE
                                                                        casebatches_whse = $whsesel
                                                                            AND casebatches_build = $building
                                                                    GROUP BY casebatches_equipment UNION ALL SELECT 
                                                                        casebatches_equipment AS EQUIP,
                                                                            SUM(casebatches_time_final) AS MINS
                                                                    FROM
                                                                        printvis.casebatches_time
                                                                    WHERE
                                                                        casebatches_equipment <> 'REACH'
                                                                            AND casebatches_whse = $whsesel
                                                                            AND casebatches_build = $building
                                                                    GROUP BY casebatches_equipment) x
                                                                GROUP BY EQUIP ON duplicate key update caseactbyhour_minutes=VALUES(caseactbyhour_minutes);");
    $result2->execute();
} //end of whse foreach loop



    