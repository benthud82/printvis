
<?php

include '../../globalincludes/usa_asys.php';
include '../../globalincludes/newcanada_asys.php';
include '../../connections/conn_printvis.php';
ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../functions/functions_totetimes.php';

$today = date('Y-m-d');
$startday = date('Y-m-d', (strtotime('-5 days', strtotime($today))));
$startjday = _gregdatetoyyddd($startday);

$whsearray = array(2, 7, 3, 6, 9, 11, 12, 16);
//$whsearray = array(16);  //still need to do 3,6,9 then all history has been loaded

foreach ($whsearray as $whse) {

    $mindaysarray = array();
    $asyshistory_array = array();

//Delete from casebatchstarttime table where batches are older than yesterday's cutoff time
    $sqldelete4 = "TRUNCATE  printvis.hist_loosevol_merge ";
    $querydelete4 = $conn1->prepare($sqldelete4);
    $querydelete4->execute();

//Delete from casebatchstarttime table where batches are older than yesterday's cutoff time
    $sqldelete4 = "TRUNCATE  printvis.hist_loosevol ";
    $querydelete4 = $conn1->prepare($sqldelete4);
    $querydelete4->execute();


    switch ($whse) {
        case 11:
            $connection = $aseriesconn_can;
            $schema = 'ARCPCORDTA';
            break;
        case 12:
            $connection = $aseriesconn_can;
            $schema = 'ARCPCORDTA';
            break;
        case 16:
            $connection = $aseriesconn_can;
            $schema = 'ARCPCORDTA';
            break;

        default:
            $connection = $aseriesconn;
            $schema = 'HSIPCORDTA';
            break;
    }

//Pull data from A-system and place in temp table to pull for additional logic
    $result1 = $connection->prepare("SELECT 
                                                                            PBWHSE,
                                                                            1 AS PBBUILD,
                                                                            CASE
                                                                                WHEN PCCVOL = 0 THEN PCEVOL * .0610237
                                                                                ELSE PCCVOL * .0610237
                                                                            END AS CUBIC_INCH,
                                                                            (PDLOC#) AS LOCATION,
                                                                            CASE
                                                                                WHEN LMTIER = 'L01' THEN 'FULLPALLET'
                                                                                WHEN LMTIER = 'L02' THEN 'FLOWRACK'
                                                                                WHEN LMTIER = 'L04' THEN 'BLUEBIN'
                                                                                WHEN LMTIER = 'L06' THEN 'DOGPOUND'
                                                                                ELSE 'OTHER'
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
                                                                            $schema.NOTWPT A
                                                                                JOIN
                                                                            $schema.NPFCPC ON PCITEM = PDITEM
                                                                                JOIN
                                                                            $schema.NOTWPS ON PDWCS# = PBWCS# AND PDWKNO = PBWKNO
                                                                                AND PBBOX# = PDBOX#
                                                                                LEFT JOIN
                                                                            $schema.NPFLSM ON LMWHSE = PDWHSE AND LMLOC# = PDLOC#
                                                                        WHERE
                                                                            PDWHSE = $whse AND PDBXSZ <> 'CSE'
                                                                                AND PDLOC# NOT LIKE '%SDS%'
                                                                                AND PCWHSE = 0
                                                                              AND   PBRCJD >= $startjday
                                                                         --       AND PBRCJD > 18366 and PBRCJD <= 19017");
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
            $PDITEM = intval($mindaysarray[$counter]['PDITEM']);
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
        $sql = "INSERT IGNORE INTO printvis.hist_loosevol_merge ($columns) VALUES $values";
        $query = $conn1->prepare($sql);
        $query->execute();
        $maxrange += 4000;
    } while ($counter <= $rowcount);

$mindaysarray = array();

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
                                                                printvis.hist_loosevol_merge
                                                                    LEFT JOIN
                                                                printvis.printcutoff ON PBWHSE = cutoff_DC
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
        $sql = "INSERT  INTO printvis.hist_loosevol ($columns) VALUES $values ON DUPLICATE KEY UPDATE hist_printdate=VALUES(hist_printdate), hist_printhrmn=VALUES(hist_printhrmn), hist_printhr=VALUES(hist_printhr), hist_reldate=VALUES(hist_reldate), hist_relhrmin=VALUES(hist_relhrmin), predicted_availdate=VALUES(predicted_availdate), predicted_availhour=VALUES(predicted_availhour)";
        $query = $conn1->prepare($sql);
        $query->execute();
        $maxrange += 4000;
    } while ($counter <= $rowcount);



    $sqlinsert = "INSERT INTO printvis.hist_loosevol_summary(
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
                                printvis.hist_loosevol
                            WHERE predicted_availdate >= '$startday'
                            GROUP BY hist_whse , hist_build , hist_equip , predicted_availdate , predicted_availhour) 
                            on duplicate key update loosevol_lines=VALUES(loosevol_lines),loosevol_cube=VALUES(loosevol_cube)";
    $queryinsert = $conn1->prepare($sqlinsert);
    $queryinsert->execute();
}

//update first time pick table
$sqlinsert2 = "INSERT IGNORE INTO printvis.tsm_firstpick
                            SELECT 
                                ReserveUSerID, MIN(DATE(DateTimeFirstPick))
                            FROM
                                printvis.voicepicks
                                    LEFT JOIN
                                printvis.tsm_firstpick ON PICK_TMSNUM = ReserveUSerID
                            WHERE
                                UserDescription <> ' ' and PICK_TMSNUM is null
                            GROUP BY ReserveUSerID";
$queryinsert2 = $conn1->prepare($sqlinsert2);
$queryinsert2->execute();

//update first time pack table
$sqlinsert3 = "INSERT IGNORE INTO printvis.tsm_firstpack 
SELECT      
cartstart_tsm, MIN(dateaddedtotable) 
FROM     printvis.allcart_history 
GROUP BY cartstart_tsm
 ";
$queryinsert3 = $conn1->prepare($sqlinsert3);
$queryinsert3->execute();
