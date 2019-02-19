<?php

//this is the push from sublime text
include '../../globalincludes/usa_asys.php';
include '../../connections/conn_printvis.php';
ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../functions/functions_totetimes.php';

$today = date('Y-m-d');
$startday = date('Y-m-d', (strtotime('-15 days', strtotime($today))));
$startjday = _gregdatetoyyddd($startday);

$whsearray = array(7, 2, 3, 6, 9);
//$whsearray = array(6);

foreach ($whsearray as $whse) {




//Delete from casebatchstarttime table where batches are older than yesterday's cutoff time
    $sqldelete4 = "TRUNCATE  printvis.hist_casevol_merge ";
    $querydelete4 = $conn1->prepare($sqldelete4);
    $querydelete4->execute();


//Pull data from A-system and place in temp table to pull for additional logic
    $result1 = $aseriesconn->prepare("SELECT 
                                                                            PBWHSE,
                                                                            CASE
                                                                                WHEN PBWHSE = 3 AND PDLOC# >= 'W400000' THEN 2
                                                                                ELSE 1
                                                                            END AS PBBUILD,
                                                                            CASE
                                                                                WHEN PCCVOL = 0 THEN PCEVOL * .0610237
                                                                                ELSE PCCVOL * .0610237
                                                                            END AS CUBIC_INCH,
                                                                            (PDLOC#) AS LOCATION,
                                                                            CASE
                                                                                WHEN LMTIER = 'C01' THEN 'PALLETJACK'
                                                                                WHEN LMTIER = 'C02' THEN 'BELTLINE'
                                                                                WHEN
                                                                                    LMTIER = 'C03'
                                                                                        AND SUBSTR(LMLOC#, 6, 1) = '1'
                                                                                THEN
                                                                                    'PALLETJACK'
                                                                                WHEN
                                                                                    LMTIER IN ('C05' , 'C06')
                                                                                        AND SUBSTR(LMLOC#, 6, 1) >= '2'
                                                                                THEN
                                                                                    'ORDERPICKER'
                                                                                ELSE 'ORDERPICKER'
                                                                            END AS EQUIP_TYPE,
                                                                            PDITEM, 
                                                                            PBPRIO,
                                                                            PBSHPZ,
                                                                            PBSHPC,
                                                                            PBWHTO,
                                                                            PBRCJD,
                                                                            PBRCHM,
                                                                            PBRCHR,
                                                                            PBPTJD,
                                                                            PBPTHM,
                                                                            PBPTHR,
                                                                            PBPTEM,
                                                                            PBRLJD,
                                                                            PBRLHM,
                                                                            PBRLHR,
                                                                            PBWCS#,
                                                                            PBORJD,
                                                                            PBORHM,
                                                                            PBLP9D,
                                                                            CASE
                                                                                WHEN SUBSTRING(PBTX01, 3, 1) = '2' THEN 1
                                                                                ELSE 0
                                                                            END AS TWODAY,
                                                                            PBTX02
                                                                        FROM
                                                                            HSIPCORDTA.NOTWPT A
                                                                                JOIN
                                                                            HSIPCORDTA.NPFCPC ON PCITEM = PDITEM
                                                                                JOIN
                                                                            HSIPCORDTA.NOTWPS ON PDWCS# = PBWCS# AND PDWKNO = PBWKNO
                                                                                AND PBBOX# = PDBOX#
                                                                                LEFT JOIN
                                                                            HSIPCORDTA.NPFLSM ON LMWHSE = PDWHSE AND LMLOC# = PDLOC#
                                                                        WHERE
                                                                            PDWHSE = $whse AND PDBXSZ = 'CSE'
                                                                                AND PDLOC# NOT LIKE '%SDS%'
                                                                                AND PCWHSE = 0
                                                                              AND   PBRCJD >= $startjday
                                                                          --      AND PBRCJD > 17227 and PBRCJD <= 17262");
    $result1->execute();
    $mindaysarray = $result1->fetchAll(pdo::FETCH_ASSOC);


    $columns = 'PBWHSE, PBBUILD, CUBIC_INCH, LOCATION, EQUIP_TYPE, PDITEM, PBPRIO, PBSHPZ, PBSHPC, PBWHTO, PBRCJD,  PBRCHM, PBRCHR, PBPTJD, PBPTHM, PBPTHR, PBPTEM, PBRLJD, PBRLHM, PBRLHR, PBWCS, PBORJD, PBORHM, PBLP9D, TWODAY, PBTX02';


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
            $PBWHSE = $mindaysarray[$counter]['PBWHSE'];
            $PBBUILD = $mindaysarray[$counter]['PBBUILD'];
            $CUBIC_INCH = $mindaysarray[$counter]['CUBIC_INCH'];
            $LOCATION = $mindaysarray[$counter]['LOCATION'];
            $EQUIP_TYPE = $mindaysarray[$counter]['EQUIP_TYPE'];
            $PDITEM = $mindaysarray[$counter]['PDITEM'];
            $PBPRIO = $mindaysarray[$counter]['PBPRIO'];
            $PBSHPZ = $mindaysarray[$counter]['PBSHPZ'];
            $PBSHPC = $mindaysarray[$counter]['PBSHPC'];
            $PBWHTO = $mindaysarray[$counter]['PBWHTO'];
            $PBRCJD = $mindaysarray[$counter]['PBRCJD'];
            $PBRCHM = $mindaysarray[$counter]['PBRCHM'];
            $PBRCHR = $mindaysarray[$counter]['PBRCHR'];
            $PBPTJD = $mindaysarray[$counter]['PBPTJD'];
            $PBPTHM = $mindaysarray[$counter]['PBPTHM'];
            $PBPTHR = $mindaysarray[$counter]['PBPTHR'];
            $PBPTEM = $mindaysarray[$counter]['PBPTEM'];
            $PBRLJD = $mindaysarray[$counter]['PBRLJD'];
            $PBRLHM = $mindaysarray[$counter]['PBRLHM'];
            $PBRLHR = $mindaysarray[$counter]['PBRLHR'];
            $PBWCS = $mindaysarray[$counter]['PBWCS#'];
            $PBORJD = $mindaysarray[$counter]['PBORJD'];
            $PBORHM = $mindaysarray[$counter]['PBORHM'];
            $PBLP9D = $mindaysarray[$counter]['PBLP9D'];
            $TWODAY = $mindaysarray[$counter]['TWODAY'];
            $PBTX02 = $mindaysarray[$counter]['PBTX02'];


            $data[] = "($PBWHSE, $PBBUILD, '$CUBIC_INCH', '$LOCATION', '$EQUIP_TYPE', $PDITEM, '$PBPRIO', '$PBSHPZ', '$PBSHPC',  $PBWHTO, $PBRCJD, $PBRCHM, $PBRCHR, $PBPTJD, $PBPTHM , $PBPTHR, $PBPTEM, $PBRLJD, $PBRLHM, $PBRLHR, $PBWCS, $PBORJD, $PBORHM, $PBLP9D, $TWODAY, '$PBTX02')";
            $counter += 1;
        }


        $values = implode(',', $data);

        if (empty($values)) {
            break;
        }
        $sql = "INSERT IGNORE INTO printvis.hist_casevol_merge ($columns) VALUES $values";
        $query = $conn1->prepare($sql);
        $query->execute();
        $maxrange += 4000;
    } while ($counter <= $rowcount);



//Pull data from temp table just created and join with print cutoff times to determine when the order should be printed.
    $asyshistory = $conn1->prepare("SELECT 
                                                                PBWHSE as hist_whse,
                                                                PBWHTO as hist_xferwhs,
                                                                PBBUILD as hist_build,
                                                                CUBIC_INCH as hist_cubeinch,
                                                                LOCATION as hist_loc,
                                                                EQUIP_TYPE as hist_equip,
                                                                PDITEM as hist_item,
                                                                PBPRIO as hist_prior,
                                                                PBSHPZ as hist_shipzone,
                                                                PBSHPC as hist_shipclass,
                                                                PBRCJD as hist_recdate,
                                                                PBRCHM as hist_rechrmin,
                                                                CASE
                                                                    WHEN PBRCHM < 999 THEN SUBSTR(PBRCHM, 1, 1)
                                                                    ELSE SUBSTR(PBRCHM, 1, 2)
                                                                END AS hist_rechour,
                                                                    PBPTJD as hist_printdate,
                                                                PBPTHM as hist_printhrmn,
                                                                CASE
                                                                    WHEN PBPTHM < 999 THEN SUBSTR(PBPTHM, 1, 1)
                                                                    ELSE SUBSTR(PBPTHM, 1, 2)
                                                                END AS hist_printhr,
                                                                PBRLJD as hist_reldate,
                                                                PBRLHM as hist_relhrmin,
                                                                PBWCS as hist_invnum,
                                                                PBORJD as hist_orddate,
                                                                PBORHM as hist_ordhrmin,
                                                                PBLP9D as hist_lp,
                                                                TWODAY as hist_twoday,
                                                                PBTX02 as hist_tx02,
                                                                cutoff_time,
                                                                cutoff_group
                                                            FROM
                                                                printvis.hist_casevol_merge
                                                                    LEFT JOIN
                                                                printvis.printcutoff_case ON PBWHSE = cutoff_DC
                                                                    AND substr(PBSHPZ,1,2) = substr(cutoff_zone,1,2)
                                                            WHERE
                                                                PBWHSE = $whse");
    $asyshistory->execute();
    $asyshistory_array = $asyshistory->fetchAll(pdo::FETCH_ASSOC);




    $columns = 'hist_whse, hist_xferwhs, hist_build, hist_cubeinch, hist_loc, hist_equip, hist_item, hist_prior, hist_shipzone, hist_shipclass, hist_recdate, hist_rechrmin, hist_rechour,hist_printdate,hist_printhrmn, hist_printhr, hist_reldate, hist_relhrmin,hist_invnum,hist_orddate,hist_ordhrmin,hist_lp,hist_twoday,hist_tx02,cutoff_time,cutoff_group, predicted_availdate, predicted_availhour';


    $values = array();

    $maxrange = 3999;
    $counter = 0;
    $rowcount = count($asyshistory_array);

    do {
        if ($maxrange > $rowcount) {  //prevent undefined offset
            $maxrange = $rowcount - 1;
        }

        $data = array();
        $values = array();

        while ($counter <= $maxrange) { //split into 5,000 lines segments to insert into merge table
            $hist_whse = $asyshistory_array[$counter]['hist_whse'];
            $hist_xferwhs = $asyshistory_array[$counter]['hist_xferwhs'];
            $hist_build = $asyshistory_array[$counter]['hist_build'];
            $hist_cubeinch = $asyshistory_array[$counter]['hist_cubeinch'];
            $hist_loc = $asyshistory_array[$counter]['hist_loc'];
            $hist_equip = $asyshistory_array[$counter]['hist_equip'];
            $hist_item = $asyshistory_array[$counter]['hist_item'];
            $hist_prior = $asyshistory_array[$counter]['hist_prior'];
            $hist_shipzone = $asyshistory_array[$counter]['hist_shipzone'];
            $hist_shipclass = $asyshistory_array[$counter]['hist_shipclass'];
            $hist_recdate = $asyshistory_array[$counter]['hist_recdate'];
            $converted_recdate = _1yydddtogregdate($hist_recdate);
            $hist_rechrmin = $asyshistory_array[$counter]['hist_rechrmin'];
            $hist_rechour = $asyshistory_array[$counter]['hist_rechour'];
            $hist_printdate = $asyshistory_array[$counter]['hist_printdate'];
            $converted_printdate = _1yydddtogregdate($hist_printdate);
            $hist_printhrmn = $asyshistory_array[$counter]['hist_printhrmn'];
            $hist_printhr = $asyshistory_array[$counter]['hist_printhr'];
            $hist_reldate = $asyshistory_array[$counter]['hist_reldate'];
            $converted_reldate = _1yydddtogregdate($hist_reldate);
            $hist_relhrmin = $asyshistory_array[$counter]['hist_relhrmin'];
            $hist_invnum = $asyshistory_array[$counter]['hist_invnum'];
            $hist_orddate = $asyshistory_array[$counter]['hist_orddate'];
            $converted_orderdate = _1yydddtogregdate($hist_orddate);
            $hist_ordhrmin = $asyshistory_array[$counter]['hist_ordhrmin'];
            $hist_lp = $asyshistory_array[$counter]['hist_lp'];
            $hist_twoday = $asyshistory_array[$counter]['hist_twoday'];
            $hist_tx02 = $asyshistory_array[$counter]['hist_tx02'];
            $cutoff_time = $asyshistory_array[$counter]['cutoff_time'];
            if (is_null($cutoff_time)) {
                $cutoff_time = 1700;
            }
            $cutoff_group = $asyshistory_array[$counter]['cutoff_group'];

            $predicted_availdate = _printdatepredictor($converted_recdate, $hist_rechrmin, $cutoff_time, $hist_shipzone, $hist_shipclass, $cutoff_group);

//set print hour to 6 if predicted print date is greater than red date
            if ($predicted_availdate > $converted_recdate) {
                $predicted_availhour = intval(6);
            } else {
                $predicted_availhour = $hist_rechour;
            }
            $data[] = "($hist_whse, $hist_xferwhs, $hist_build, '$hist_cubeinch', '$hist_loc', '$hist_equip', $hist_item, '$hist_prior', '$hist_shipzone', '$hist_shipclass', '$converted_recdate', $hist_rechrmin, $hist_rechour, '$converted_printdate', $hist_printhrmn, $hist_printhr, '$converted_reldate', $hist_relhrmin, $hist_invnum, '$converted_orderdate', $hist_ordhrmin, $hist_lp, $hist_twoday, '$hist_tx02', $cutoff_time, '$cutoff_group', '$predicted_availdate', $predicted_availhour)";
            $counter += 1;
        }
        $values = implode(',', $data);

        if (empty($values)) {
            break;
        }
        $sql = "INSERT  INTO printvis.hist_casevol ($columns) VALUES $values ON DUPLICATE KEY UPDATE hist_printdate=VALUES(hist_printdate), hist_printhrmn=VALUES(hist_printhrmn), hist_printhr=VALUES(hist_printhr), hist_reldate=VALUES(hist_reldate), hist_relhrmin=VALUES(hist_relhrmin), predicted_availdate=VALUES(predicted_availdate), predicted_availhour=VALUES(predicted_availhour)";
        $query = $conn1->prepare($sql);
        $query->execute();
        $maxrange += 4000;
    } while ($counter <= $rowcount);




    $sqlinsert = "INSERT INTO printvis.hist_casevol_summary(
                            SELECT 
                                hist_whse,
                                hist_build,
                                hist_equip,
                                predicted_availdate,
                                CASE
                                    WHEN predicted_availhour < 6 THEN 6
                                    WHEN predicted_availhour > 17 THEN 17
                                    ELSE predicted_availhour
                                END as predicted_availhour,
                                COUNT(*) AS COUNT_LINE,
                                SUM(hist_cubeinch) AS SUM_VOLUME
                            FROM
                                printvis.hist_casevol
                            GROUP BY hist_whse , hist_build , hist_equip , predicted_availdate , predicted_availhour) 
                            on duplicate key update casevol_lines=VALUES(casevol_lines),casevol_cube=VALUES(casevol_cube)";
    $queryinsert = $conn1->prepare($sqlinsert);
    $queryinsert->execute();
}