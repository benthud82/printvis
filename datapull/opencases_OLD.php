<?php

include '../sessioninclude.php';

include '../../globalincludes/usa_asys.php';
include '../../CustomerAudit/connection/connection_details.php';
ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../functions/functions_totetimes.php';
if (isset($_SESSION['MYUSER'])) {
    $var_userid = $_SESSION['MYUSER'];
    $whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
    $whssql->execute();
    $whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

    $var_whse = $whssqlarray[0]['prodvisdb_users_PRIMDC'];
    $whsearray = array($var_whse);

    if ($var_whse == 3) {
        $building = 2;
    } else {
        $building = 1;
    }
} else {
    $whsearray = array(7);
}

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


foreach ($whsearray as $whsesel) {
    //delete cases not printed table
    $sqldelete = "DELETE FROM  printvis.casesnotprinted WHERE notprinted_whse = $whsesel ";
    $querydelete = $conn1->prepare($sqldelete);
    $querydelete->execute();
    //delete cases not printed table
    $sqldelete = "DELETE FROM  printvis.casesnotprinted_temp WHERE notprinted_whse = $whsesel ";
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

    //delete cases not printed table by batch
    $sqldelete3 = "DELETE FROM  printvis.notprintedcasebatches_time WHERE casebatches_whse = $whsesel and casebatches_build = $building";
    $querydelete3 = $conn1->prepare($sqldelete3);
    $querydelete3->execute();

    //operator count by equipment type
    $operatorcount = $conn1->prepare("SELECT 
                                                                            equipcount_equipment, equipcount_count
                                                                        FROM
                                                                            printvis.case_equipmentcount
                                                                        WHERE
                                                                            equipcount_whse = $whsesel;");
    $operatorcount->execute();
    $operatorcountarray = $operatorcount->fetchAll(pdo::FETCH_ASSOC);


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
    $sql2 = "INSERT INTO printvis.casesnotprinted_temp ($notprintedcolumns_temp) VALUES $values2";
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
                                                                            JOIN
                                                                        printvis.printcutoff_case ON notprinted_whse = cutoff_DC
                                                                            AND notprinted_shipzone = cutoff_zone
                                                                    WHERE
                                                                        notprinted_whse = $whsesel and notprinted_build = $building;");
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
                $cutoff_time = 0;
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
        $sql = "INSERT INTO printvis.casesnotprinted ($notprintedcolumns) VALUES $values ";
        $query = $conn1->prepare($sql);
        $query->execute();
        $maxrange += 4000;
    } while ($counter <= $rowcount);




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
                                                                                    MIN(notprinted_location) AS FIRSTLOC,
                                                                                    MAX(notprinted_location) AS LASTLOC,
                                                                                    caseaisle_inches,
                                                                                    notprinted_equiptype,
                                                                                    casepm_pickloc,
                                                                                    casepm_units,
                                                                                    casepm_indirect,
                                                                                    casepm_dropoff,
                                                                                    casepm_noncon,
                                                                                    casepm_inch_per_min,
                                                                                    SUM(notprinted_cubinch) AS CUBICINCH
                                                                                FROM
                                                                                    printvis.casesnotprinted_bybatch
                                                                                        JOIN
                                                                                    printvis.pm_casetimes ON casepm_whse = notprinted_whse
                                                                                        AND casepm_build = notprinted_build
                                                                                        AND casepm_equipment = notprinted_equiptype
                                                                                        LEFT JOIN
                                                                                    pickprediction_aislelength ON notprinted_whse = caseaisle_whse
                                                                                        AND notprinted_build = caseaisle_build
                                                                                        AND notprinted_aisle = caseaisle_id
                                                                                WHERE notprinted_whse = $whsesel and notprinted_build = $building
                                                                                GROUP BY notprinted_whse , notprinted_build , notprinted_batch , notprinted_aisle
                                                                                ORDER BY notprinted_batch , notprinted_location;");
    $opentotedata->execute();
    $opentotedataarray = $opentotedata->fetchAll(pdo::FETCH_ASSOC);

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
        $caseaisle_inches = ($opentotedataarray[$key]['caseaisle_inches'] * 2) + $turninches;  //multiplied by 2 because of print sequence.  Have to go up/down aisle twice
        $caseequip_equipment = $opentotedataarray[$key]['notprinted_equiptype'];
        $casepm_pickloc = $opentotedataarray[$key]['casepm_pickloc'];
        $casepm_units = $opentotedataarray[$key]['casepm_units'];
        $casepm_indirect = $opentotedataarray[$key]['casepm_indirect'];
        $casepm_dropoff = $opentotedataarray[$key]['casepm_dropoff'];
        $casepm_noncon = $opentotedataarray[$key]['casepm_noncon'];
        $casepm_inch_per_min = $opentotedataarray[$key]['casepm_inch_per_min'];
        $casebatch_cubicinch = $opentotedataarray[$key]['CUBICINCH'];

        $calc_picktimeloc = $casebatch_lines * $casepm_pickloc;
        $calc_picktimeunit = $casebatch_cubicinch * $casepm_units;
        $calc_picktimeindirect = $casebatch_lines * $casepm_indirect;
        $calc_picktimedropoff = $casebatch_lines * $casepm_dropoff;
        $calc_picktimenoncon = $casebatch_lines * $casepm_noncon;

        if ($casepm_inch_per_min > 0) {
            $calc_taveltime = $caseaisle_inches / $casepm_inch_per_min;
        } else {
            $calc_taveltime = 0;
        }



        $totaltime = $calc_picktimeloc + $calc_picktimeunit + $calc_picktimeindirect + $calc_picktimedropoff + $calc_picktimenoncon + $calc_taveltime;

        $data_totetimes[] = "($casebatch_whse, $casebatch_build, '$casebatch_cart', '$casebatch_aisle', '$caseequip_equipment', '$casebatch_trips', $casebatch_lines, '$casebatch_firstloc', '$casebatch_lastloc', '$calc_picktimeloc', '$calc_picktimeunit', '$calc_picktimeindirect', '$calc_picktimedropoff', '$calc_picktimenoncon', $caseaisle_inches, '$calc_taveltime', '$totaltime')";
    }

    //Add to table casetote_time
    $values3 = implode(',', $data_totetimes);
    $sql3 = "INSERT IGNORE INTO printvis.notprintedcasetote_time ($casetotetimes_cols) VALUES $values3";
    $query3 = $conn1->prepare($sql3);
    $query3->execute();


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
        $TIME_NONCON = $batchsummaryarray[$key]['TIME_NONCON'];
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

        $TIME_SUB_TOTAL = $batchsummaryarray[$key]['TIME_SUB_TOTAL'];
        $TIME_BATCH_FINAL = $casepm_kiosk + $casepm_batch + $casepm_comp + $casepm_short + $TIME_TRIPCUBE + $TIME_PICKLOC + $TIME_UNIT + $TIME_INDIRECT + $TIME_DROPOFF + $TIME_NONCON + $TIME_TRAVEL_AISLE + $TIME_STARTSTOP;

        $batchcompletedata[] = "($CART_WHSE, $CART_BUILD, '$CART_BATCH',  '$CART_EQUIP',  '$casepm_kiosk', '$casepm_batch', '$casepm_comp', '$casepm_short', '$casepm_trip', $TOT_TRIPS, '$TIME_TRIPCUBE', $TOT_LINES, '$FIRST_LOC', '$LAST_LOC','$TIME_PICKLOC', '$TIME_UNIT', '$TIME_INDIRECT', '$TIME_DROPOFF', '$TIME_NONCON', $TOT_INCHES_AISLE,  '$TIME_TRAVEL_AISLE', $START_TO_FIRSTLOC, $LASTLOC_TO_STOP, $casepm_inch_per_min, $TOT_INCHES_STARTSTOP,  '$TIME_STARTSTOP',  '$TIME_BATCH_FINAL' )";
    }

    //Add to table casetote_time
    $values4 = implode(',', $batchcompletedata);
    $sql4 = "INSERT IGNORE INTO printvis.notprintedcasebatches_time ($casebatchtimes_cols) VALUES $values4";
    $query4 = $conn1->prepare($sql4);
    $query4->execute();
} //end of whse foreach loop
