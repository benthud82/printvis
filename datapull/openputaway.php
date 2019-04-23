<?php

include '../../globalincludes/usa_asys.php';
include '../../connections/conn_printvis.php';
ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../functions/functions_totetimes.php';
//put in connection includes (as400 printvis)
$whsearray = array(2, 3, 6, 7, 9);
foreach ($whsearray as $whsesel) {
    include '../timezoneset.php';



    $today = date('Y-m-d H:i:s');
    $result1 = $aseriesconn->prepare("SELECT eawhse, a.EAITEM, a.EATRN#, a.EATRNQ, a.EATLOC, a.EALOG#, a.EATRND, a.EACMPT, a.EASEQ3, a.EASTAT, a.EATYPE, c.PCCPKU, c.PCIPKU, d.LOPKGU, d.LOPRIM, CASE WHEN c.PCCPKU > 0 then int(a.EATRNQ /  c.PCCPKU) else 0 end as CASEHANDLE,  CASE WHEN c.PCCPKU > 0 then mod(a.EATRNQ ,  c.PCCPKU) else a.EATRNQ end as EACHHANDLE,  EASP12, EAEXPD FROM HSIPCORDTA.NPFCPC c, HSIPCORDTA.NPFLOC d, HSIPCORDTA.NPFERA a LEFT JOIN HSIPCORDTA.NPFLER E ON A.EATLOC = E.LELOC# AND A.EATRN# = E.LETRND inner join (SELECT EATRN#, max(EASEQ3) as max_seq FROM HSIPCORDTA.NPFERA GROUP BY EATRN#) b on b.EATRN# = a.EATRN# and a.EASEQ3 = max_seq and EASTAT <> 'C'  WHERE PCITEM = EAITEM and PCWHSE = 0 and LOWHSE = EAWHSE and LOLOC# = EATLOC AND EAWHSE = $whsesel");
    $result1->execute();
    $mindaysarray = $result1->fetchAll(pdo::FETCH_ASSOC);

//create table on local
    $columns = 'temp_openputaway_whse,temp_openputaway_item, temp_openputaway_trans, temp_openputaway_status, temp_openputaway_quantity, temp_openputaway_location, temp_openputaway_log, temp_openputaway_transdate, temp_openputaway_comptime, temp_openputaway_seq, temp_openputaway_type, temp_openputaway_casehandle, temp_openputaway_eachhandle,temp_openputaway_lot, temp_openputaway_expiry';

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
            $temp_openputaway_type = $mindaysarray[$counter]['EATYPE'];
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

    $sql_aisletimes = $conn1->prepare("INSERT INTO  printvis.openputaway_aisletime (SELECT 
    openputaway_whse,
    openputaway_log,
    putcartmap_aisle,
    putcartmap_main,
    COUNT(*) AS LINE_COUNT,
    SUM(openputaway_eachhandle) AS UNIT_COUNT,
    SUM(openputaway_casehandle) AS CASE_COUNT,
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
    (put_foottraveltime * 12 + MAX(putcartmap_baydistance) + (CASE
        WHEN putcartmap_main = 'AISLE' THEN ((COUNT(*) - 1) * 3.68)
        ELSE 0
    END)) AS TIME_AISLETRAVEL,
    putcartmap_smartseq,
    MAX(openputaway_location) as LAST_LOC,
    MIN(openputaway_location) as FIRST_LOC,
    '$today'
    
FROM
    printvis.openputaway
        JOIN
    printvis.pm_putawaytimes ON put_whse = openputaway_whse
WHERE
    openputaway_whse = $whsesel
        AND openputaway_log <> 0
GROUP BY openputaway_whse , openputaway_log , putcartmap_aisle , putcartmap_main , put_location , put_indirect , put_ladder , put_pullbin , put_obtainall , put_placeall , put_foottraveltime , putcartmap_smartseq)");
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
                AND aisle_whse = 6) AS LOWSTART_X,
    (SELECT 
            aisle_z
        FROM
            printvis.put_aislecoor
        WHERE
            aisle_location = 'L'
                AND aisle_id = openputaway_aisletime_aisle
                AND aisle_whse = 6) AS LOWSTART_Z,
    (SELECT 
            aisle_x
        FROM
            printvis.put_aislecoor
        WHERE
            aisle_location = 'H'
                AND aisle_id = openputaway_aisletime_aisle
                AND aisle_whse = 6) AS HIGHSTART_X,
    (SELECT 
            aisle_z
        FROM
            printvis.put_aislecoor
        WHERE
            aisle_location = 'H'
                AND aisle_id = openputaway_aisletime_aisle
                AND aisle_whse = 6) AS HIGHSTART_Z,
    (SELECT 
            putcartmap_zcoor
        FROM
            printvis.putprediction_putawaycartmap
        WHERE
            putcartmap_location = SUBSTR(openputaway_aisletimefirstlocation,
                1,
                6)
                AND putcartmap_whse = 6) AS FIRSTLOCATION_Z,
    (SELECT 
            putcartmap_xcoor
        FROM
            printvis.putprediction_putawaycartmap
        WHERE
            putcartmap_location = SUBSTR(openputaway_aisletimefirstlocation,
                1,
                6)
                AND putcartmap_whse = 6) AS FIRSTLOCATION_X,
    (SELECT 
            putcartmap_xcoor
        FROM
            printvis.putprediction_putawaycartmap
        WHERE
            putcartmap_location = SUBSTR(openputaway_aisletimelastlocation,
                1,
                6)
                AND putcartmap_whse = 6) AS LASTLOCATION_X,
    (SELECT 
            putcartmap_zcoor
        FROM
            printvis.putprediction_putawaycartmap
        WHERE
            putcartmap_location = SUBSTR(openputaway_aisletimelastlocation,
                1,
                6)
                AND putcartmap_whse = 6) AS LASTLOCATION_Z
FROM
    printvis.openputaway_aisletime
WHERE
    openputaway_aisletime_whse = 6
ORDER BY openputaway_aisletime_log , openputaway_aisletime_putcartmap_smartseq , openputaway_aisletime_aisle");
    $openputdata->execute();
    $openputdataarray = $openputdata->fetchAll(pdo::FETCH_ASSOC);
    $openarraycount = count($openputdataarray) - 1;
    foreach ($openputdataarray as $key => $value) {
        $openputaway_aisletime_whse = $openputdataarray[$key]['openputaway_aisletime_whse'];
        $openputaway_aisletime_log = $openputdataarray[$key]['openputaway_aisletime_log'];
        $openputaway_aisletime_aisle = $openputdataarray[$key]['openputaway_aisletime_aisle'];
        $openputaway_aisletime_main = $openputdataarray[$key]['openputaway_aisletime_main'];
        $openputaway_aisletime_countline = $openputdataarray[$key]['openputaway_aisletime_countline'];
        $openputaway_aisletime_countunit = $openputdataarray[$key]['openputaway_aisletime_countunit'];
        $openputaway_aisletime_countcases = $openputdataarray[$key]['openputaway_aisletime_countcases'];
        $openputaway_aisletime_laddercount = $openputdataarray[$key]['openputaway_aisletime_laddercount'];
        $openputaway_aisletime_countpulbin = $openputdataarray[$key]['openputaway_aisletime_countpulbin'];
        $openputaway_aisletime_inneraisletravel = $openputdataarray[$key]['openputaway_aisletime_inneraisletravel'];
        $openputaway_aisletime_timeputlocation = $openputdataarray[$key]['openputaway_aisletime_timeputlocation'];
        $openputaway_aisletime_timeputindirect = $openputdataarray[$key]['openputaway_aisletime_timeputindirect'];
        $openputaway_aisletime_timeputladder = $openputdataarray[$key]['openputaway_aisletime_timeputladder'];
        $openputaway_aisletime_timeputpullbin = $openputdataarray[$key]['openputaway_aisletime_timeputpullbin'];
        $openputaway_aisletime_timeputobtainall = $openputdataarray[$key]['openputaway_aisletime_timeputobtainall'];
        $openputaway_aisletime_timeputplaceall = $openputdataarray[$key]['openputaway_aisletime_timeputplaceall'];
        $openputaway_aisletime_sumaisletravel = $openputdataarray[$key]['openputaway_aisletime_sumaisletravel'];
        $openputaway_aisletime_putcartmap_smartseq = $openputdataarray[$key]['openputaway_aisletime_putcartmap_smartseq'];
        $openputaway_aisletimefirstlocation = $openputdataarray[$key]['openputaway_aisletimefirstlocation'];
        $openputaway_aisletimelastlocation = $openputdataarray[$key]['openputaway_aisletimelastlocation'];
        $openputaway_aisletime_datetime = $openputdataarray[$key]['openputaway_aisletime_datetime'];

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
    }
}

    


