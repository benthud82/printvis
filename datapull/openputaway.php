<?php

include '../../globalincludes/usa_asys.php';
include '../../connections/conn_printvis.php';
ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../functions/functions_totetimes.php';
$today_eraformat = intval(date('1ymd'));
//put in connection includes (as400 printvis)
$truncatetables = array('openputaway', 'openputaway_aisletime', 'temp_openputaway', 'log_equip');
foreach ($truncatetables as $value) {
    $querydelete2 = $conn1->prepare("TRUNCATE printvis.$value");
    $querydelete2->execute();
}

//call log equipment estimator
include 'logequip.php';

$whsearray = array(2, 3, 6, 7,9);
foreach ($whsearray as $whsesel) {
    include '../timezoneset.php';
    $today = date('Y-m-d H:i:s');
    $todaydatetime = date('Y-m-d H:i:s');

//What is START location
    $pcstart = $conn1->prepare("SELECT 
                                                                            putcartmap_xcoor, putcartmap_zcoor
                                                                        FROM
                                                                            printvis.putprediction_putawaycartmap
                                                                        WHERE
                                                                            putcartmap_whse = $whsesel
                                                                        AND putcartmap_location = 'PCSTART' ");
    $pcstart->execute();
    $pcstart_array = $pcstart->fetchAll(pdo::FETCH_ASSOC);
    $pcstart_xcoor = $pcstart_array[0]['putcartmap_xcoor'];
    $pcstart_zcoor = $pcstart_array[0]['putcartmap_zcoor'];

    //What is stop location
    $pcstop = $conn1->prepare("SELECT 
                                                                        putcartmap_xcoor, putcartmap_zcoor
                                                                        FROM
                                                                            printvis.putprediction_putawaycartmap
                                                                        WHERE
                                                                            putcartmap_whse = $whsesel
                                                                        AND putcartmap_location = 'PCSTOP' ");

    $pcstop->execute();
    $pcstop_array = $pcstop->fetchAll(pdo::FETCH_ASSOC);
    $pcstop_xcoor = $pcstop_array[0]['putcartmap_xcoor'];
    $pcstop_zcoor = $pcstop_array[0]['putcartmap_zcoor'];



    $foottravel = $conn1->prepare("select put_foottraveltime from printvis.pm_putawaytimes where put_whse = $whsesel;");

    $foottravel->execute();
    $foottravelarray = $foottravel->fetchAll(pdo::FETCH_ASSOC);
    $foottraveltime = $foottravelarray[0]['put_foottraveltime'];



    $result1 = $aseriesconn->prepare("SELECT eawhse, a.EAITEM, a.EATRN#, a.EATRNQ, a.EATLOC, a.EALOG#, a.EATRND, a.EACMPT, a.EASEQ3, a.EASTAT, d.LOPRIM, a.EATYPE, c.PCCPKU, c.PCIPKU, d.LOPKGU, a.EATYPE, CASE WHEN c.PCCPKU > 0 then int(a.EATRNQ /  c.PCCPKU) else 0 end as CASEHANDLE,  CASE WHEN c.PCCPKU > 0 then mod(a.EATRNQ ,  c.PCCPKU) else (CASE WHEN a.EATRNQ > 100 THEN 100 ELSE A.EATRNQ END) end as EACHHANDLE,  EASP12, EAEXPD FROM HSIPCORDTA.NPFCPC c, HSIPCORDTA.NPFLOC d, HSIPCORDTA.NPFERA a LEFT JOIN HSIPCORDTA.NPFLER E ON A.EATLOC = E.LELOC# AND A.EATRN# = E.LETRND inner join (SELECT EATRN#, max(EASEQ3) as max_seq FROM HSIPCORDTA.NPFERA GROUP BY EATRN#) b on b.EATRN# = a.EATRN# and a.EASEQ3 = max_seq and EASTAT <> 'C'  WHERE PCITEM = EAITEM and PCWHSE = 0 and LOWHSE = EAWHSE and LOLOC# = EATLOC AND EAWHSE = $whsesel");
    $result1->execute();
    $mindaysarray = $result1->fetchAll(pdo::FETCH_ASSOC);

//create table on local
    $columns = 'temp_openputaway_whse,temp_openputaway_item, temp_openputaway_trans, temp_openputaway_status, temp_openputaway_quantity, temp_openputaway_location, temp_openputaway_log, temp_openputaway_transdate, temp_openputaway_comptime, temp_openputaway_seq, temp_openputaway_type, temp_openputaway_casehandle, temp_openputaway_eachhandle,temp_openputaway_lot, temp_openputaway_expiry';
    $columns_aisletime = 'openputaway_aisletime_whse,
                            openputaway_aisletime_log,
                            openputaway_aisletime_aisle,
                            openputaway_aisletime_main,
                            openputaway_aisletime_countline,
                            openputaway_aisletime_countunit,
                            openputaway_aisletime_countcases,
                            openputaway_aisletime_expirycount,
                            openputaway_aisletime_lotcount,
                            openputaway_aisletime_laddercount,
                            openputaway_aisletime_countpulbin,
                            openputaway_aisletime_inneraisletravel,
                            openputaway_aisletime_timeputlocation,
                            openputaway_aisletime_timeputindirect,
                            openputaway_aisletime_timeputladder,
                            openputaway_aisletime_timeputpullbin,
                            openputaway_aisletime_timeputobtainall,
                            openputaway_aisletime_timeputplaceall,
                            openputaway_aisletime_outeraisletravel,
                            openputaway_aisletime_totaltravel,
                            openputaway_aisletime_traveltime,
                            openputaway_aisletime_timeexpirycheck,
                            openputaway_aisletime_timelotcheck,
                            openputaway_aisletime_timedecarton,
                            openputaway_aisletime_timecardboard,                         
                            openputaway_aisletime_putcartmap_smartseq,
                            openputaway_aisletimefirstlocation,
                            openputaway_aisletimelastlocation,
                            openputaway_aisletime_totaltime,
                            openputaway_aisletime_datetime';

//***KEEP**
    $values = array();


    $maxrange = 3999;
    $counter = 0;
    $rowcount = count($mindaysarray);

    do {
        if ($maxrange > $rowcount) {  //prevent undefined offset
            $maxrange = $rowcount - 1;
        }

        $data = array();
        $values = array();
        while ($counter <= $maxrange) { //split into 5,000 lines segments to insert into merge table
            $temp_openputaway_whse = $mindaysarray[$counter]['EAWHSE'];
            $temp_openputaway_item = $mindaysarray[$counter]['EAITEM'];
            $temp_openputaway_trans = $mindaysarray[$counter]['EATRN#'];
            $temp_openputaway_status = $mindaysarray[$counter]['EASTAT'];
            $temp_openputaway_quantity = $mindaysarray[$counter]['EATRNQ'];
            $temp_openputaway_location = substr($mindaysarray[$counter]['EATLOC'], 0, 6);
            $temp_openputaway_log = $mindaysarray[$counter]['EALOG#'];
            $temp_openputaway_transdate = $mindaysarray[$counter]['EATRND'];
            $temp_openputaway_comptime = $mindaysarray[$counter]['EACMPT'];
            $temp_openputaway_seq = $mindaysarray[$counter]['EASEQ3'];
            $temp_openputaway_type = $mindaysarray[$counter]['LOPRIM'];
            $temp_openputaway_casehandle = $mindaysarray[$counter]['CASEHANDLE'];
            $temp_openputaway_eachhandle = $mindaysarray[$counter]['EACHHANDLE'];
            $temp_openputaway_lot = $mindaysarray[$counter]['EASP12'];
            $temp_openputaway_expiry = $mindaysarray[$counter]['EAEXPD'];
            //STOPKEEP


            $data[] = "($temp_openputaway_whse, $temp_openputaway_item,$temp_openputaway_trans, '$temp_openputaway_status', $temp_openputaway_quantity,'$temp_openputaway_location',$temp_openputaway_log, $temp_openputaway_transdate,$temp_openputaway_comptime, $temp_openputaway_seq, '$temp_openputaway_type',$temp_openputaway_casehandle, $temp_openputaway_eachhandle, '$temp_openputaway_lot', $temp_openputaway_expiry)";
            $counter += 1;
        }


        $values = implode(',', $data);

        if (empty($values)) {
            break;
        }
        $sql = "INSERT IGNORE INTO printvis.temp_openputaway ($columns) VALUES $values";
        $query = $conn1->prepare($sql);
        $query->execute();
        $maxrange += 4000;
    } while ($counter <= $rowcount);

    $sql_putawaylines_joined = $conn1->prepare("INSERT IGNORE into printvis.openputaway(SELECT temp_openputaway_whse,
                                                                                            temp_openputaway_item,
                                                                                            temp_openputaway_trans,
                                                                                            temp_openputaway_status,
                                                                                            temp_openputaway_quantity,
                                                                                            temp_openputaway_location,
                                                                                            SUBSTR(putcartmap_location, 1, 3) AS putcartmap_aisle,
                                                                                            putcartmap_main,
                                                                                            putcartmap_xcoor,
                                                                                            putcartmap_ycoor,             
                                                                                            putcartmap_zcoor,
                                                                                            putcartmap_baydistance,
                                                                                            putcartmap_openingheight,
                                                                                            putcartmap_smartseq,
                                                                                            temp_openputaway_log,
                                                                                            temp_openputaway_transdate,
                                                                                            temp_openputaway_comptime,
                                                                                            temp_openputaway_seq,
                                                                                            temp_openputaway_type,
                                                                                            temp_openputaway_casehandle,
                                                                                            temp_openputaway_eachhandle,
                                                                                            temp_openputaway_lot,
                                                                                            temp_openputaway_expiry
                                                                                    FROM printvis.temp_openputaway
                                                                                    JOIN
                                                                                    printvis.putprediction_putawaycartmap ON temp_openputaway_location = putcartmap_location AND temp_openputaway_whse = putcartmap_whse
                                                                                    WHERE temp_openputaway_whse = $whsesel)");

    $sql_putawaylines_joined->execute();

    $sql_aisletimes = $conn1->prepare("INSERT IGNORE INTO  printvis.openputaway_aisletime (SELECT 
    openputaway_whse,
    openputaway_log,
    putcartmap_aisle,
    putcartmap_main,
    COUNT(*) AS LINE_COUNT,
    SUM(openputaway_eachhandle) AS UNIT_COUNT,
    SUM(openputaway_casehandle) AS CASE_COUNT,
    SUM(CASE WHEN openputaway_expiry > 0 then 1 else 0 end) AS EXPIRY_COUNT,
    SUM(CASE WHEN openputaway_lot <> ' ' then 1 else 0 end) AS LOT_COUNT,
    SUM(CASE
        WHEN putcartmap_ycoor > 60 THEN 1
        ELSE 0
    END) AS LADDER_COUNT,
    SUM(CASE
        WHEN putcartmap_openingheight = 6 THEN 1
        ELSE 0
    END) AS PULLBIN_COUNT,
    MAX(putcartmap_baydistance) + (CASE
        WHEN putcartmap_main = 'AISLE' THEN ((COUNT(*) - 1) * 3.68)
        ELSE 0
    END) AS INNERAISLETRAVEL,
    put_location * COUNT(*) AS TIME_PUTLOCATION,
    put_indirect * COUNT(*) AS TIME_PUTINDIRECT,
    put_ladder * SUM(CASE
        WHEN putcartmap_ycoor > 60 THEN 1
        ELSE 0
    END) AS TIME_PUTLADDER,
    put_pullbin * SUM(CASE
        WHEN putcartmap_openingheight = 6 THEN 1
        ELSE 0
    END) AS TIME_PUTPULLBIN,
    put_obtainall * SUM(openputaway_eachhandle + openputaway_casehandle) AS TIME_PUTOBTAINALL,
    put_placeall * SUM(openputaway_eachhandle + openputaway_casehandle) AS TIME_PUTPLACEALL,
    0 AS OUTERAISLE_TRAVEL,
    0 AS TOTAL_TRAVEL,
    0 AS TIME_TOTTRAVEL,
SUM(CASE WHEN openputaway_expiry > 0 THEN put_expcheck ELSE 0 END) AS TIME_EXPCHECK,    
SUM(CASE WHEN openputaway_lot <> ' ' THEN put_lotcheck ELSE 0 END) AS TIME_LOTCHECK,
SUM(CASE WHEN openputaway_type = 'P' THEN (openputaway_casehandle * put_decarton) ELSE 0 END) AS TIME_DECARTON,
SUM(CASE WHEN openputaway_type = 'P' THEN (openputaway_casehandle * put_cardboard) ELSE 0 END) AS TIME_CARDBOARD,      
        putcartmap_smartseq,
    MAX(openputaway_location) as LAST_LOC,
    MIN(openputaway_location) as FIRST_LOC,
    0 as TOTALTIME,
    '$today'
    
FROM
    printvis.openputaway
        JOIN
    printvis.pm_putawaytimes ON put_whse = openputaway_whse
WHERE
    openputaway_whse = $whsesel
        AND openputaway_log <> 0 AND put_function = 'CRT'
GROUP BY openputaway_whse , openputaway_log , putcartmap_aisle , putcartmap_main , put_location , put_indirect , put_ladder , put_pullbin , put_obtainall , put_placeall , put_foottraveltime , putcartmap_smartseq)"
    );
    $sql_aisletimes->execute();

    $openputdata = $conn1->prepare("SELECT 
    openputaway_aisletime.*,
    (SELECT 
            aisle_x
        FROM
            printvis.put_aislecoor
        WHERE
            aisle_location = 'L'
                AND aisle_id = openputaway_aisletime_aisle
                AND aisle_whse = $whsesel) AS LOWSTART_X,
    (SELECT 
            aisle_z
        FROM
            printvis.put_aislecoor
        WHERE
            aisle_location = 'L'
                AND aisle_id = openputaway_aisletime_aisle
                AND aisle_whse = $whsesel) AS LOWSTART_Z,
    (SELECT 
            aisle_x
        FROM
            printvis.put_aislecoor
        WHERE
            aisle_location = 'H'
                AND aisle_id = openputaway_aisletime_aisle
                AND aisle_whse = $whsesel) AS HIGHSTART_X,
    (SELECT 
            aisle_z
        FROM
            printvis.put_aislecoor
        WHERE
            aisle_location = 'H'
                AND aisle_id = openputaway_aisletime_aisle
                AND aisle_whse = $whsesel) AS HIGHSTART_Z,
    (SELECT 
            putcartmap_zcoor
        FROM
            printvis.putprediction_putawaycartmap
        WHERE
            putcartmap_location = SUBSTR(openputaway_aisletimefirstlocation,
                1,
                6)
                AND putcartmap_whse = $whsesel) AS FIRSTLOCATION_Z,
    (SELECT 
            putcartmap_xcoor
        FROM
            printvis.putprediction_putawaycartmap
        WHERE
            putcartmap_location = SUBSTR(openputaway_aisletimefirstlocation,
                1,
                6)
                AND putcartmap_whse = $whsesel) AS FIRSTLOCATION_X,
    (SELECT 
            putcartmap_xcoor
        FROM
            printvis.putprediction_putawaycartmap
        WHERE
            putcartmap_location = SUBSTR(openputaway_aisletimelastlocation,
                1,
                6)
                AND putcartmap_whse = $whsesel) AS LASTLOCATION_X,
    (SELECT 
            putcartmap_zcoor
        FROM
            printvis.putprediction_putawaycartmap
        WHERE
            putcartmap_location = SUBSTR(openputaway_aisletimelastlocation,
                1,
                6)
                AND putcartmap_whse = $whsesel) AS LASTLOCATION_Z
FROM
    printvis.openputaway_aisletime
WHERE
    openputaway_aisletime_whse = $whsesel
ORDER BY openputaway_aisletime_log , openputaway_aisletime_putcartmap_smartseq , openputaway_aisletime_aisle");
    $openputdata->execute();
    $openputdataarray = $openputdata->fetchAll(pdo::FETCH_ASSOC);
    $openarraycount = count($openputdataarray) - 1;

    //initiate batch variable
    $openbatchcount = 0;



    foreach ($openputdataarray as $key => $value) {
        $openputaway_aisletime_whse = $openputdataarray[$key]['openputaway_aisletime_whse'];
        $openputaway_aisletime_log = $openputdataarray[$key]['openputaway_aisletime_log'];
        $openputaway_aisletime_aisle = $openputdataarray[$key]['openputaway_aisletime_aisle'];
        $openputaway_aisletime_main = $openputdataarray[$key]['openputaway_aisletime_main'];
        $openputaway_aisletime_countline = $openputdataarray[$key]['openputaway_aisletime_countline'];
        $openputaway_aisletime_countunit = $openputdataarray[$key]['openputaway_aisletime_countunit'];
        $openputaway_aisletime_countcases = $openputdataarray[$key]['openputaway_aisletime_countcases'];
        $openputaway_aisletime_expirycount = $openputdataarray[$key]['openputaway_aisletime_expirycount'];
        $openputaway_aisletime_lotcount = $openputdataarray[$key]['openputaway_aisletime_lotcount'];
        $openputaway_aisletime_laddercount = $openputdataarray[$key]['openputaway_aisletime_laddercount'];
        $openputaway_aisletime_countpulbin = $openputdataarray[$key]['openputaway_aisletime_countpulbin'];
        $openputaway_aisletime_inneraisletravel = $openputdataarray[$key]['openputaway_aisletime_inneraisletravel'];
        $openputaway_aisletime_timeputlocation = $openputdataarray[$key]['openputaway_aisletime_timeputlocation'];
        $openputaway_aisletime_timeputindirect = $openputdataarray[$key]['openputaway_aisletime_timeputindirect'];
        $openputaway_aisletime_timeputladder = $openputdataarray[$key]['openputaway_aisletime_timeputladder'];
        $openputaway_aisletime_timeputpullbin = $openputdataarray[$key]['openputaway_aisletime_timeputpullbin'];
        $openputaway_aisletime_timeputobtainall = $openputdataarray[$key]['openputaway_aisletime_timeputobtainall'];
        $openputaway_aisletime_timeputplaceall = $openputdataarray[$key]['openputaway_aisletime_timeputplaceall'];
        $openputaway_aisletime_timedecaton = $openputdataarray[$key]['openputaway_aisletime_timedecarton'];
        $openputaway_aisletime_timecardboard = $openputdataarray[$key]['openputaway_aisletime_timecardboard'];
        $openputaway_aisletime_putcartmap_smartseq = $openputdataarray[$key]['openputaway_aisletime_putcartmap_smartseq'];
        if (is_null($openputaway_aisletime_putcartmap_smartseq)) {
            $openputaway_aisletime_putcartmap_smartseq = 0;
        }
        $openputaway_aisletimefirstlocation = $openputdataarray[$key]['openputaway_aisletimefirstlocation'];
        $openputaway_aisletimelastlocation = $openputdataarray[$key]['openputaway_aisletimelastlocation'];
        $openputaway_aisletime_datetime = $openputdataarray[$key]['openputaway_aisletime_datetime'];
        $openputaway_aisletime_timeexpirycheck = $openputdataarray[$key]['openputaway_aisletime_timeexpirycheck'];
        $openputaway_aisletime_timelotcheck = $openputdataarray[$key]['openputaway_aisletime_timelotcheck'];


        $LOWSTART_X = $openputdataarray[$key]['LOWSTART_X'];
        $LOWSTART_Z = $openputdataarray[$key]['LOWSTART_Z'];
        $HIGHSTART_X = $openputdataarray[$key]['HIGHSTART_X'];
        $HIGHSTART_Z = $openputdataarray[$key]['HIGHSTART_Z'];
        $FIRSTLOC_X = $openputdataarray[$key]['FIRSTLOCATION_X'];
        $FIRSTLOC_Z = $openputdataarray[$key]['FIRSTLOCATION_Z'];
        $LASTLOC_X = $openputdataarray[$key]['LASTLOCATION_X'];
        $LASTLOC_Z = $openputdataarray[$key]['LASTLOCATION_Z'];

        If ($key !== 0) { //Not the first record
            $previousloc_X = $openputdataarray[$key - 1]['LASTLOCATION_X'];  //previous aisle last location X
            $previousloc_Z = $openputdataarray[$key - 1]['LASTLOCATION_Z'];  //previous aisle last location Z
            $previousaisleH_X = $openputdataarray[$key - 1]['HIGHSTART_X'];  //previous aisle high parking X
            $previousaisleH_Z = $openputdataarray[$key - 1]['HIGHSTART_Z'];  //previous aisle high parking Z
            $previousaisleL_X = $openputdataarray[$key - 1]['LOWSTART_X'];  //previous aisle low parking X
            $previousaisleL_Z = $openputdataarray[$key - 1]['LOWSTART_Z'];  //previous aisle low parking Z
        } else {//First record in array
            $previousloc_X = $previousloc_Z = $previousaisleH_X = $previousaisleH_Z = $previousaisleL_X = $previousaisleL_Z = 0; //set all to 0 if first record
        }
        if ($key !== $openarraycount) { //Not the last record
            $nextloc_X = $openputdataarray[$key + 1]['LASTLOCATION_X'];  //next aisle last location X
            $nextloc_Z = $openputdataarray[$key + 1]['LASTLOCATION_Z'];  //next aisle last location Z
            $nextaisleH_X = $openputdataarray[$key + 1]['HIGHSTART_X'];  //next aisle high parking X
            $nextaisleH_Z = $openputdataarray[$key + 1]['HIGHSTART_Z'];  //next aisle high parking Z
            $nextaisleL_X = $openputdataarray[$key + 1]['LOWSTART_X'];  //next aisle low parking X
            $nextaisleL_Z = $openputdataarray[$key + 1]['LOWSTART_Z'];  //next aisle low parking Z
        } else {//Last record in array
            $nextloc_X = $nextloc_Z = $nextaisleH_X = $nextaisleH_Z = $nextaisleL_X = $nextaisleL_Z = 0; //set all to 0 if last record
        }
        $outeraisle_high = _casetravel($previousloc_X, $previousloc_Z, $previousaisleH_X, $previousaisleH_Z, $HIGHSTART_X, $HIGHSTART_Z, $FIRSTLOC_X, $FIRSTLOC_Z);
        $outeraisle_low = _casetravel($previousloc_X, $previousloc_Z, $previousaisleL_X, $previousaisleL_Z, $LOWSTART_X, $LOWSTART_Z, $FIRSTLOC_X, $FIRSTLOC_Z);
        $outeraisle_min = min($outeraisle_high, $outeraisle_low);
        if (isset($openputdataarray[$key + 1]['openputaway_aisletime_log'])) {
            $nextcart = intval($openputdataarray[$key + 1]['openputaway_aisletime_log']);
        } else {
            $nextcart = 0;
        }

        //calculate time from this P point to next P point.
        if ($openputaway_aisletime_log != $nextcart && $openputaway_aisletime_log != intval($openputdataarray[$key - 1]['openputaway_aisletime_log'])) {
            $outeraisle_min = abs($FIRSTLOC_X - $pcstart_xcoor) + abs($FIRSTLOC_Z - $pcstart_zcoor);
            $outeraisle_min += abs($LASTLOC_X - $pcstop_xcoor) + abs($LASTLOC_Z - $pcstop_zcoor);
            $openbatchcount = 0;
        } elseif ($openbatchcount == 0) {  //first aisle on batch.  what is distance from CSTART to first location
            $outeraisle_min = abs($FIRSTLOC_X - $pcstart_xcoor) + abs($FIRSTLOC_Z - $pcstart_zcoor);
            $openbatchcount += 1;
        } elseif ($key == $openarraycount) { //if last line of array do normal calc, go back to CSTOP
            $outeraisle_min += abs($LASTLOC_X - $pcstop_xcoor) + abs($LASTLOC_Z - $pcstop_zcoor);
            $openbatchcount = 0;
        } elseif ($openputaway_aisletime_log != $nextcart && $openbatchcount == 0) {//if new batch is on next line and this is first line of batch, go from CSTART to Location the location back to CSTOP
            $outeraisle_min = abs($FIRSTLOC_X - $pcstart_xcoor) + abs($FIRSTLOC_Z - $pcstart_zcoor);
            $outeraisle_min += abs($LASTLOC_X - $pcstop_xcoor) + abs($LASTLOC_Z - $pcstop_zcoor);
            $openbatchcount = 0;
        } elseif ($openputaway_aisletime_log != $nextcart) {//if new batch is on next line do normal calc then add distance from last location and go back to CSTOP
            $outeraisle_min += abs($LASTLOC_X - $pcstop_xcoor) + abs($LASTLOC_Z - $pcstop_zcoor);
            $openbatchcount = 0;
        }
//        //else { //calc distance from Last location to mid point , add to next mid point, add to next mid point to first loc of next aisle
//            //make excpetion for aisle 49 in Sparks. Go from last location on w49 to first location on next aisle
//            if (($whsesel == 3 || $whsesel == 6) && substr($openputdataarray[$key - 1]['casebatch_lastloc'], 0, 3) == 'W49') {
//                $outeraisle_min = abs($openputdataarray[$key - 1]['LASTLOC_X'] - $FIRSTLOC_X) + abs($openputdataarray[$key - 1]['LASTLOC_Z'] - $FIRSTLOC_Z);
//            } else { //normal calc
//                $openbatchcount += 1;
//            }
//        }

        if (is_null($outeraisle_min)) {
            $outeraisle_min = 0;
        }

        $totaltravel = ($outeraisle_min + $openputaway_aisletime_inneraisletravel);
        $totaltravel_time = ($outeraisle_min + $openputaway_aisletime_inneraisletravel) / $foottraveltime;
        $totalaisletime = $openputaway_aisletime_timeputlocation + $openputaway_aisletime_timeputindirect + $openputaway_aisletime_timeputladder + $openputaway_aisletime_timeputpullbin + $openputaway_aisletime_timeputobtainall + $openputaway_aisletime_timeputplaceall + $openputaway_aisletime_timeexpirycheck + $openputaway_aisletime_timelotcheck + $totaltravel_time + $openputaway_aisletime_timedecaton + $openputaway_aisletime_timecardboard;

        $data[] = "($openputaway_aisletime_whse,$openputaway_aisletime_log,'$openputaway_aisletime_aisle','$openputaway_aisletime_main',$openputaway_aisletime_countline,$openputaway_aisletime_countunit,$openputaway_aisletime_countcases,$openputaway_aisletime_expirycount,$openputaway_aisletime_lotcount,$openputaway_aisletime_laddercount,$openputaway_aisletime_countpulbin,$openputaway_aisletime_inneraisletravel,'$openputaway_aisletime_timeputlocation','$openputaway_aisletime_timeputindirect','$openputaway_aisletime_timeputladder','$openputaway_aisletime_timeputpullbin','$openputaway_aisletime_timeputobtainall','$openputaway_aisletime_timeputplaceall',$outeraisle_min,$totaltravel, '$totaltravel_time','$openputaway_aisletime_timeexpirycheck','$openputaway_aisletime_timelotcheck', '$openputaway_aisletime_timedecaton', '$openputaway_aisletime_timecardboard', $openputaway_aisletime_putcartmap_smartseq,'$openputaway_aisletimefirstlocation','$openputaway_aisletimelastlocation','$totalaisletime','$openputaway_aisletime_datetime')";
    }

    //Add to table casebatches_time
    if (!is_null($data)) {
        $values4 = implode(',', $data);
        $sql4 = "INSERT INTO printvis.openputaway_aisletime ($columns_aisletime) VALUES $values4
                        ON DUPLICATE KEY UPDATE 
                            openputaway_aisletime_outeraisletravel=VALUES(openputaway_aisletime_outeraisletravel), 
                            openputaway_aisletime_totaltravel=VALUES(openputaway_aisletime_totaltravel), 
                            openputaway_aisletime_traveltime=VALUES(openputaway_aisletime_traveltime), 
                            openputaway_aisletime_totaltime=VALUES(openputaway_aisletime_totaltime)";
        $query4 = $conn1->prepare($sql4);
        $query4->execute();
    }
}



//new insert into aislehistory
$aislesqlhistory = $conn1->prepare("insert into printvis.openputaway_aisletime_hist 
(SELECT * FROM printvis.openputaway_aisletime) 
on duplicate key update 
openputaway_aisletime_hist.openputaway_aisletime_countline = IF(openputaway_aisletime_hist.openputaway_aisletime_countline < VALUES(openputaway_aisletime_countline), VALUES(openputaway_aisletime_countline), openputaway_aisletime_hist.openputaway_aisletime_countline),
openputaway_aisletime_hist.openputaway_aisletime_countunit = IF(openputaway_aisletime_hist.openputaway_aisletime_countunit < VALUES(openputaway_aisletime_countunit), VALUES(openputaway_aisletime_countunit), openputaway_aisletime_hist.openputaway_aisletime_countunit),
openputaway_aisletime_hist.openputaway_aisletime_countcases = IF(openputaway_aisletime_hist.openputaway_aisletime_countcases < VALUES(openputaway_aisletime_countcases), VALUES(openputaway_aisletime_countcases), openputaway_aisletime_hist.openputaway_aisletime_countcases),
openputaway_aisletime_hist.openputaway_aisletime_expirycount = IF(openputaway_aisletime_hist.openputaway_aisletime_expirycount < VALUES(openputaway_aisletime_expirycount), VALUES(openputaway_aisletime_expirycount), openputaway_aisletime_hist.openputaway_aisletime_expirycount),
openputaway_aisletime_hist.openputaway_aisletime_lotcount = IF(openputaway_aisletime_hist.openputaway_aisletime_lotcount < VALUES(openputaway_aisletime_lotcount), VALUES(openputaway_aisletime_lotcount), openputaway_aisletime_hist.openputaway_aisletime_lotcount),
openputaway_aisletime_hist.openputaway_aisletime_laddercount = IF(openputaway_aisletime_hist.openputaway_aisletime_laddercount < VALUES(openputaway_aisletime_laddercount), VALUES(openputaway_aisletime_laddercount), openputaway_aisletime_hist.openputaway_aisletime_laddercount),
openputaway_aisletime_hist.openputaway_aisletime_countpulbin = IF(openputaway_aisletime_hist.openputaway_aisletime_countpulbin < VALUES(openputaway_aisletime_countpulbin), VALUES(openputaway_aisletime_countline), openputaway_aisletime_hist.openputaway_aisletime_countpulbin),
openputaway_aisletime_hist.openputaway_aisletime_inneraisletravel = IF(openputaway_aisletime_hist.openputaway_aisletime_inneraisletravel < VALUES(openputaway_aisletime_inneraisletravel), VALUES(openputaway_aisletime_countline), openputaway_aisletime_hist.openputaway_aisletime_inneraisletravel),
openputaway_aisletime_hist.openputaway_aisletime_timeputlocation = IF(openputaway_aisletime_hist.openputaway_aisletime_timeputlocation < VALUES(openputaway_aisletime_timeputlocation), VALUES(openputaway_aisletime_timeputlocation), openputaway_aisletime_hist.openputaway_aisletime_timeputlocation),
openputaway_aisletime_hist.openputaway_aisletime_timeputindirect = IF(openputaway_aisletime_hist.openputaway_aisletime_timeputindirect < VALUES(openputaway_aisletime_timeputindirect), VALUES(openputaway_aisletime_timeputindirect), openputaway_aisletime_hist.openputaway_aisletime_timeputindirect),
openputaway_aisletime_hist.openputaway_aisletime_timeputladder = IF(openputaway_aisletime_hist.openputaway_aisletime_timeputladder < VALUES(openputaway_aisletime_timeputladder), VALUES(openputaway_aisletime_timeputladder), openputaway_aisletime_hist.openputaway_aisletime_timeputladder),
openputaway_aisletime_hist.openputaway_aisletime_timeputpullbin = IF(openputaway_aisletime_hist.openputaway_aisletime_timeputpullbin < VALUES(openputaway_aisletime_timeputpullbin), VALUES(openputaway_aisletime_timeputpullbin), openputaway_aisletime_hist.openputaway_aisletime_timeputpullbin),
openputaway_aisletime_hist.openputaway_aisletime_timeputobtainall = IF(openputaway_aisletime_hist.openputaway_aisletime_timeputobtainall < VALUES(openputaway_aisletime_timeputobtainall), VALUES(openputaway_aisletime_timeputobtainall), openputaway_aisletime_hist.openputaway_aisletime_timeputobtainall),
openputaway_aisletime_hist.openputaway_aisletime_timeputplaceall = IF(openputaway_aisletime_hist.openputaway_aisletime_timeputplaceall < VALUES(openputaway_aisletime_timeputplaceall), VALUES(openputaway_aisletime_timeputplaceall), openputaway_aisletime_hist.openputaway_aisletime_timeputplaceall),
openputaway_aisletime_hist.openputaway_aisletime_outeraisletravel = IF(openputaway_aisletime_hist.openputaway_aisletime_outeraisletravel < VALUES(openputaway_aisletime_outeraisletravel), VALUES(openputaway_aisletime_outeraisletravel), openputaway_aisletime_hist.openputaway_aisletime_outeraisletravel),
openputaway_aisletime_hist.openputaway_aisletime_totaltravel = IF(openputaway_aisletime_hist.openputaway_aisletime_totaltravel < VALUES(openputaway_aisletime_totaltravel), VALUES(openputaway_aisletime_totaltravel), openputaway_aisletime_hist.openputaway_aisletime_totaltravel),
openputaway_aisletime_hist.openputaway_aisletime_traveltime = IF(openputaway_aisletime_hist.openputaway_aisletime_traveltime < VALUES(openputaway_aisletime_traveltime), VALUES(openputaway_aisletime_traveltime), openputaway_aisletime_hist.openputaway_aisletime_traveltime),
openputaway_aisletime_hist.openputaway_aisletime_timeexpirycheck = IF(openputaway_aisletime_hist.openputaway_aisletime_timeexpirycheck < VALUES(openputaway_aisletime_timeexpirycheck), VALUES(openputaway_aisletime_timeexpirycheck), openputaway_aisletime_hist.openputaway_aisletime_timeexpirycheck),
openputaway_aisletime_hist.openputaway_aisletime_timelotcheck = IF(openputaway_aisletime_hist.openputaway_aisletime_timelotcheck < VALUES(openputaway_aisletime_timelotcheck), VALUES(openputaway_aisletime_timelotcheck), openputaway_aisletime_hist.openputaway_aisletime_timelotcheck),
openputaway_aisletime_hist.openputaway_aisletime_timedecarton = IF(openputaway_aisletime_hist.openputaway_aisletime_timedecarton < VALUES(openputaway_aisletime_timedecarton), VALUES(openputaway_aisletime_timedecarton), openputaway_aisletime_hist.openputaway_aisletime_timedecarton),
openputaway_aisletime_hist.openputaway_aisletime_putcartmap_smartseq = IF(openputaway_aisletime_hist.openputaway_aisletime_putcartmap_smartseq < VALUES(openputaway_aisletime_putcartmap_smartseq), VALUES(openputaway_aisletime_putcartmap_smartseq), openputaway_aisletime_hist.openputaway_aisletime_putcartmap_smartseq),
openputaway_aisletime_hist.openputaway_aisletimefirstlocation = IF(openputaway_aisletime_hist.openputaway_aisletimefirstlocation < VALUES(openputaway_aisletimefirstlocation), VALUES(openputaway_aisletimefirstlocation), openputaway_aisletime_hist.openputaway_aisletimefirstlocation),
openputaway_aisletime_hist.openputaway_aisletimelastlocation = IF(openputaway_aisletime_hist.openputaway_aisletimelastlocation < VALUES(openputaway_aisletimelastlocation), VALUES(openputaway_aisletimelastlocation), openputaway_aisletime_hist.openputaway_aisletimelastlocation),
openputaway_aisletime_hist.openputaway_aisletime_timecardboard = IF(openputaway_aisletime_hist.openputaway_aisletime_timecardboard < VALUES(openputaway_aisletime_timecardboard), VALUES(openputaway_aisletime_timecardboard), openputaway_aisletime_hist.openputaway_aisletime_timecardboard),
openputaway_aisletime_hist.openputaway_aisletime_totaltime = IF(openputaway_aisletime_hist.openputaway_aisletime_totaltime < VALUES(openputaway_aisletime_totaltime), VALUES(openputaway_aisletime_totaltime), openputaway_aisletime_hist.openputaway_aisletime_totaltime),
openputaway_aisletime_hist.openputaway_aisletime_datetime = IF(openputaway_aisletime_hist.openputaway_aisletime_datetime < VALUES(openputaway_aisletime_datetime), VALUES(openputaway_aisletime_datetime), openputaway_aisletime_hist.openputaway_aisletime_datetime)");

$aislesqlhistory->execute();







$logsql = $conn1->prepare("insert into printvis.openputaway_logtime (SELECT
openputaway_aisletime_whse AS PUTAWAY_WHSE,
openputaway_aisletime_log AS PUTAWAY_LOG,
SUM(openputaway_aisletime_countline) AS TOTAL_LINES,
SUM(openputaway_aisletime_countunit) AS TOTAL_UNITS,
SUM(openputaway_aisletime_countcases) AS TOTAL_CASES,
SUM(openputaway_aisletime_expirycount) AS TOTAL_EXPIRY,
SUM(openputaway_aisletime_lotcount) AS TOTAL_LOT,
SUM(openputaway_aisletime_laddercount) AS TOTAL_LADDER,
SUM(openputaway_aisletime_countpulbin) AS TOTAL_PULLBIN,
SUM(openputaway_aisletime_inneraisletravel)AS TOTAL_INNERTRAVEL,
SUM(openputaway_aisletime_timeputlocation) AS TOTAL_PUTLOCATION,
SUM(openputaway_aisletime_timeputindirect) AS TOTAL_PUTINDIRECT,
SUM(openputaway_aisletime_timeputladder) AS TOTAL_LADDERTIME,
SUM(openputaway_aisletime_timeputpullbin) AS TOTAL_PULLBINTIME,
SUM(openputaway_aisletime_timeputobtainall) AS TOTAL_OBTAINALLTIME,
SUM(openputaway_aisletime_timeputplaceall) AS TOTAL_PLACEALLTIME,
SUM(openputaway_aisletime_outeraisletravel) AS TOTAL_OUTERTRAVEL, 
SUM(openputaway_aisletime_totaltravel)AS TOTAL_TRAVELFEET,
SUM(openputaway_aisletime_traveltime)AS TOTAL_TRAVELTIME,
SUM(openputaway_aisletime_timeexpirycheck)AS TOTAL_EXPIRYTIME,
SUM(openputaway_aisletime_timelotcheck)AS TOTAL_LOTTIME,
SUM(openputaway_aisletime_timedecarton)AS TOTAL_DECARTONTIME,
SUM(openputaway_aisletime_timecardboard)AS TOTAL_CARDBOARDTIME,
SUM(openputaway_aisletime_totaltime) + put_signon + put_complete AS TOTAL_LOGTIME,
'$today'
FROM
    printvis.openputaway_aisletime
        JOIN
    printvis.log_equip ON logequip_log = openputaway_aisletime_log
        JOIN
    printvis.pm_putawaytimes ON put_whse = openputaway_aisletime_whse
        AND logequip_equip = put_function
GROUP BY openputaway_aisletime_whse , openputaway_aisletime_log)
ON DUPLICATE KEY UPDATE 
openputaway_logtime_countline=VALUES(openputaway_logtime_countline),
openputaway_logtime_countunit=VALUES(openputaway_logtime_countunit),
openputaway_logtime_countcases=VALUES(openputaway_logtime_countcases),
openputaway_logtime_expirycount=VALUES(openputaway_logtime_expirycount),
openputaway_logtime_lotcount=VALUES(openputaway_logtime_lotcount),
openputaway_logtime_laddercount=VALUES(openputaway_logtime_laddercount),
openputaway_logtime_countpulbin=VALUES(openputaway_logtime_countpulbin),
openputaway_logtime_inneraisletravel=VALUES(openputaway_logtime_inneraisletravel),
openputaway_logtime_timeputlocation=VALUES(openputaway_logtime_timeputlocation),
openputaway_logtime_timeputindirect=VALUES(openputaway_logtime_timeputindirect),
openputaway_logtime_timeputladder=VALUES(openputaway_logtime_timeputladder),
openputaway_logtime_timeputpullbin=VALUES(openputaway_logtime_timeputpullbin),
openputaway_logtime_timeputobtainall=VALUES(openputaway_logtime_timeputobtainall),
openputaway_logtime_timeputplaceall=VALUES(openputaway_logtime_timeputplaceall),
openputaway_logtime_outeraisletravel=VALUES(openputaway_logtime_outeraisletravel),
openputaway_logtime_totaltravel=VALUES(openputaway_logtime_totaltravel),
openputaway_logtime_traveltime=VALUES(openputaway_logtime_traveltime),
openputaway_logtime_timeexpirycheck=VALUES(openputaway_logtime_timeexpirycheck),
openputaway_logtime_timelotcheck=VALUES(openputaway_logtime_timelotcheck),
openputaway_logtime_timedecarton=VALUES(openputaway_logtime_timedecarton),
openputaway_logtime_timecardboard=VALUES(openputaway_logtime_timecardboard),
openputaway_logtime_totaltime=VALUES(openputaway_logtime_totaltime)
");

$logsql->execute();


//only insert into log history
$logsqlhistory = $conn1->prepare("insert into printvis.openputaway_logtime_hist 
(SELECT * FROM printvis.openputaway_logtime) 
on duplicate key update 
openputaway_logtime_hist.openputaway_logtime_countline = IF(openputaway_logtime_hist.openputaway_logtime_countline < VALUES(openputaway_logtime_countline), VALUES(openputaway_logtime_countline), openputaway_logtime_hist.openputaway_logtime_countline),
openputaway_logtime_hist.openputaway_logtime_countunit = IF(openputaway_logtime_hist.openputaway_logtime_countunit < VALUES(openputaway_logtime_countunit), VALUES(openputaway_logtime_countunit), openputaway_logtime_hist.openputaway_logtime_countunit),
openputaway_logtime_hist.openputaway_logtime_countcases = IF(openputaway_logtime_hist.openputaway_logtime_countcases < VALUES(openputaway_logtime_countcases), VALUES(openputaway_logtime_countcases), openputaway_logtime_hist.openputaway_logtime_countcases),
openputaway_logtime_hist.openputaway_logtime_expirycount = IF(openputaway_logtime_hist.openputaway_logtime_expirycount < VALUES(openputaway_logtime_expirycount), VALUES(openputaway_logtime_expirycount), openputaway_logtime_hist.openputaway_logtime_expirycount),
openputaway_logtime_hist.openputaway_logtime_lotcount = IF(openputaway_logtime_hist.openputaway_logtime_lotcount < VALUES(openputaway_logtime_lotcount), VALUES(openputaway_logtime_lotcount), openputaway_logtime_hist.openputaway_logtime_lotcount),
openputaway_logtime_hist.openputaway_logtime_laddercount = IF(openputaway_logtime_hist.openputaway_logtime_laddercount < VALUES(openputaway_logtime_laddercount), VALUES(openputaway_logtime_laddercount), openputaway_logtime_hist.openputaway_logtime_laddercount),
openputaway_logtime_hist.openputaway_logtime_countpulbin = IF(openputaway_logtime_hist.openputaway_logtime_countpulbin < VALUES(openputaway_logtime_countpulbin), VALUES(openputaway_logtime_countpulbin), openputaway_logtime_hist.openputaway_logtime_countpulbin),
openputaway_logtime_hist.openputaway_logtime_inneraisletravel = IF(openputaway_logtime_hist.openputaway_logtime_inneraisletravel < VALUES(openputaway_logtime_inneraisletravel), VALUES(openputaway_logtime_inneraisletravel), openputaway_logtime_hist.openputaway_logtime_inneraisletravel),
openputaway_logtime_hist.openputaway_logtime_timeputlocation = IF(openputaway_logtime_hist.openputaway_logtime_timeputlocation < VALUES(openputaway_logtime_timeputlocation), VALUES(openputaway_logtime_timeputlocation), openputaway_logtime_hist.openputaway_logtime_timeputlocation),
openputaway_logtime_hist.openputaway_logtime_timeputindirect = IF(openputaway_logtime_hist.openputaway_logtime_timeputindirect < VALUES(openputaway_logtime_timeputindirect), VALUES(openputaway_logtime_timeputindirect), openputaway_logtime_hist.openputaway_logtime_timeputindirect),
openputaway_logtime_hist.openputaway_logtime_timeputladder = IF(openputaway_logtime_hist.openputaway_logtime_timeputladder < VALUES(openputaway_logtime_timeputladder), VALUES(openputaway_logtime_timeputladder), openputaway_logtime_hist.openputaway_logtime_timeputladder),
openputaway_logtime_hist.openputaway_logtime_timeputpullbin = IF(openputaway_logtime_hist.openputaway_logtime_timeputpullbin < VALUES(openputaway_logtime_timeputpullbin), VALUES(openputaway_logtime_timeputpullbin), openputaway_logtime_hist.openputaway_logtime_timeputpullbin),
openputaway_logtime_hist.openputaway_logtime_timeputobtainall = IF(openputaway_logtime_hist.openputaway_logtime_timeputobtainall < VALUES(openputaway_logtime_timeputobtainall), VALUES(openputaway_logtime_timeputobtainall), openputaway_logtime_hist.openputaway_logtime_timeputobtainall),
openputaway_logtime_hist.openputaway_logtime_timeputplaceall = IF(openputaway_logtime_hist.openputaway_logtime_timeputplaceall < VALUES(openputaway_logtime_timeputplaceall), VALUES(openputaway_logtime_timeputplaceall), openputaway_logtime_hist.openputaway_logtime_timeputplaceall),
openputaway_logtime_hist.openputaway_logtime_outeraisletravel = IF(openputaway_logtime_hist.openputaway_logtime_outeraisletravel < VALUES(openputaway_logtime_outeraisletravel), VALUES(openputaway_logtime_outeraisletravel), openputaway_logtime_hist.openputaway_logtime_outeraisletravel),
openputaway_logtime_hist.openputaway_logtime_totaltravel = IF(openputaway_logtime_hist.openputaway_logtime_totaltravel < VALUES(openputaway_logtime_totaltravel), VALUES(openputaway_logtime_totaltravel), openputaway_logtime_hist.openputaway_logtime_totaltravel),
openputaway_logtime_hist.openputaway_logtime_traveltime = IF(openputaway_logtime_hist.openputaway_logtime_traveltime < VALUES(openputaway_logtime_traveltime), VALUES(openputaway_logtime_traveltime), openputaway_logtime_hist.openputaway_logtime_traveltime),
openputaway_logtime_hist.openputaway_logtime_timeexpirycheck = IF(openputaway_logtime_hist.openputaway_logtime_timeexpirycheck < VALUES(openputaway_logtime_timeexpirycheck), VALUES(openputaway_logtime_timeexpirycheck), openputaway_logtime_hist.openputaway_logtime_timeexpirycheck),
openputaway_logtime_hist.openputaway_logtime_timelotcheck = IF(openputaway_logtime_hist.openputaway_logtime_timelotcheck < VALUES(openputaway_logtime_timelotcheck), VALUES(openputaway_logtime_timelotcheck), openputaway_logtime_hist.openputaway_logtime_timelotcheck),
openputaway_logtime_hist.openputaway_logtime_timedecarton = IF(openputaway_logtime_hist.openputaway_logtime_timedecarton < VALUES(openputaway_logtime_timedecarton), VALUES(openputaway_logtime_timedecarton), openputaway_logtime_hist.openputaway_logtime_timedecarton),
openputaway_logtime_hist.openputaway_logtime_timecardboard = IF(openputaway_logtime_hist.openputaway_logtime_timecardboard < VALUES(openputaway_logtime_timecardboard), VALUES(openputaway_logtime_timecardboard), openputaway_logtime_hist.openputaway_logtime_timecardboard),
openputaway_logtime_hist.openputaway_logtime_totaltime = IF(openputaway_logtime_hist.openputaway_logtime_totaltime < VALUES(openputaway_logtime_totaltime), VALUES(openputaway_logtime_totaltime), openputaway_logtime_hist.openputaway_logtime_totaltime),
openputaway_logtime_hist.openputaway_logtime_datetime = IF(openputaway_logtime_hist.openputaway_logtime_datetime < VALUES(openputaway_logtime_datetime), VALUES(openputaway_logtime_datetime), openputaway_logtime_hist.openputaway_logtime_datetime)");

$logsqlhistory->execute();


//add to taskpred table
$taskpredcolumns = 'taskpred_id, taskpred_whse, taskpred_function, taskpred_type, taskpred_mintime, taskpred_maxtime, taskpred_updatetime';
$sql_looselines_taskpred = $conn1->prepare("INSERT INTO printvis.taskpred
                                                                                            SELECT 
                                                                                                openputaway_logtime_log AS BATCH,
                                                                                                openputaway_logtime_whse,
                                                                                                'CRT',
                                                                                                'PUTAWAY',
                                                                                                CASE
                                                                                                    WHEN CAST(openputaway_logtime_totaltime AS UNSIGNED) - 1 > 999 THEN 999
                                                                                                    ELSE CAST(openputaway_logtime_totaltime AS UNSIGNED) - 1
                                                                                                END AS MINTIME,
                                                                                                CASE
                                                                                                    WHEN CAST(openputaway_logtime_totaltime AS UNSIGNED) > 999 THEN 999
                                                                                                    ELSE CAST(openputaway_logtime_totaltime AS UNSIGNED)
                                                                                                END AS MAXTIME,
                                                                                                '$todaydatetime'
                                                                                            FROM
                                                                                                printvis.openputaway_logtime_hist
                                                                                                JOIN printvis.log_equip on openputaway_logtime_log = logequip_log and logequip_whse = openputaway_logtime_whse
                                                                                            WHERE
                                                                                                openputaway_logtime_whse in (6,3,7,9)
                                                                                                    AND DATE(openputaway_logtime_datetime) = CURDATE()
                                                                                                    and logequip_equip = 'CRT'
                                                                                                    ON DUPLICATE KEY UPDATE taskpred_mintime = values(taskpred_mintime),taskpred_maxtime = values(taskpred_maxtime)");
$sql_looselines_taskpred->execute();
