<?php

//calculates time needed to pick each batch

include '../../globalincludes/usa_asys.php';
include '../../globalincludes/newcanada_asys.php';
include '../../connections/conn_printvis.php';
ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../functions/functions_totetimes.php';

$today = date('Y-m-d');
$startday = date('Y-m-d', (strtotime('-5 days', strtotime($today))));
$startjday = _gregdatetoyyddd($startday);

function _ftpupload($ftpfilename, $ftpwhse) {
    //* Transfer file to FTP server *//
    $serverarray = array(2 => "10.1.112.199", 3 => "10.1.22.212", 6 => "10.1.17.208", 7 => "10.1.18.194", 9 => "10.1.16.206", 11 => "10.10.200.209");
    $server = $serverarray[$ftpwhse];
    //$server = "10.1.16.206";
    $ftp_user_name = "anonymous";
    $ftp_user_pass = "anonymous@hsi.com";
    $dest = "$ftpfilename";
    $source = "./exports/$ftpfilename";
    $connection = ftp_connect($server);
    $login = ftp_login($connection, $ftp_user_name, $ftp_user_pass);
    if (!$connection || !$login) {
        die('Connection attempt failed!');
    }
    //echo "<br /><br />Uploading $ftpfilename for Whse $ftpwhse<br /><br />";
    $upload = ftp_put($connection, $dest, $source, FTP_ASCII);
    if (!$upload) {
        echo 'FTP upload failed!';
    } else {
        echo'FTP Succeeded!';
    }
    print_r(error_get_last());
    ftp_close($connection);
}

//put denver last because the dbh connection could error out while testing the new server
$whsearray = array(6, 3, 7, 9, 2, 11);

//$whsearray = array(6);

$dayofweek = date('w', strtotime($today));
if ($dayofweek == 1) {
    $yesterday = date('Y-m-d', strtotime("-3 days"));
} else {
    $yesterday = date('Y-m-d', strtotime("-1 day"));
}
$yestJdate = _gregdatetoyyddd($yesterday);
$yesterdaytime = ('17:20:59');
$todaydatetime = date('Y-m-d H:i:s');
$printcutoff = date('Y-m-d H:i:s', strtotime("$yesterday $yesterdaytime"));

$sqldelete2 = "DELETE from  printvis.looselines_batchtime WHERE date(batchtime_datetimeadded) < '$yesterday'";
$querydelete2 = $conn1->prepare($sqldelete2);
$querydelete2->execute();

$sqldelete3 = "DELETE from  printvis.looselines_aisletime WHERE date(aisletime_datetimeadded) < '$yesterday'";
$querydelete3 = $conn1->prepare($sqldelete3);
$querydelete3->execute();

$sqldelete3 = "DELETE from  printvis.voice_batchespicked WHERE date(voice_startdatetime) between '$yesterday' and '1901-01-01'";
$querydelete3 = $conn1->prepare($sqldelete3);
$querydelete3->execute();

$sqldelete4 = "DELETE from  printvis.voicepicks WHERE date(DateTimeFirstPick) <= '$yesterday'";
$querydelete4 = $conn1->prepare($sqldelete4);
$querydelete4->execute();

//column listings
$col_loosetemp = 'temploose_whse, temploose_wcs, temploose_cart, temploose_loc, temploose_units, temploose_wcard, temploose_loctype, temploose_locjoin, temploose_picktype, temploose_predshort, temploose_printdatetime, temploose_recdatetime, temploose_shipzone,temploose_colg';
$col_cartspicked = 'voice_whse, voice_batch, voice_startdatetime, voice_userid, voice_cartconfig, voice_cartshelves';
$col_linespicked = 'Pick_ID, Whse, Batch_Num, Status, Short_Status, Location, Sect, Aisle, Bay, Lev, Pos, PickType, LotReq, QtyOrder, QtyPick, PackageUnit, Drug, Ice, Haz, SO, SN, NSI, Ped, ExpyChkReq, ItemCode, NDC_Num, EachWeight, DateTimeFirstPick, DATECREATED, BO, PutAwayFlag, LOCJOIN, WCS_NUM, WORKORDER_NUM, BOX_NUM, TOTELOC, SHIP_ZONE, UserDescription, ReserveUSerID';
$col_linespicked_hist = 'Pick_ID, Whse, Batch_Num, Location, ItemCode, DateTimeFirstPick, WCS_NUM, WORKORDER_NUM, BOX_NUM, UserDescription, ReserveUSerID';

foreach ($whsearray as $whsesel) {

    switch ($whsesel) {
        case 11:
            $connection = $aseriesconn_can;
            $schema = 'ARCPCORDTA';
            break;

        default:
            $connection = $aseriesconn;
            $schema = 'HSIPCORDTA';
            break;
    }

    include '../timezoneset.php';
    $maxret = 1;
    $dbh = false;
    //if no connection break and continue.  This is just for testing

    include '../../globalincludes/voice_' . $whsesel . '.php';

    $todaydatetime = date('Y-m-d H:i:s');
    $sqldelete1 = "DELETE from  printvis.temp_looselines WHERE temploose_whse = $whsesel ";
    $querydelete1 = $conn1->prepare($sqldelete1);
    $querydelete1->execute();

    $sqldelete2 = "DELETE from  printvis.looselines WHERE loose_whse = $whsesel ";
    $querydelete2 = $conn1->prepare($sqldelete2);
    $querydelete2->execute();

    $printhourmin = intval(date('Hi', strtotime('-20 minutes')));  //this is local to the DC because of timezone set.
    $printhourmin_colon = (date('H:i', strtotime('-20 minutes')));  //this is local to the DC because of timezone set.
    $todayjdate = _gregdatetoyyddd($today);
    $printlimiter = "and PBPTJD = $todayjdate and PBPTHM >= $printhourmin";

//pull in all loose lines and write to temporary table to join later with pickprediction_loosepickmap table
    $sql_looselines = $connection->prepare("SELECT
                                                                            PDWHSE, 
                                                                            PDWCS#,
                                                                            PDCART, 
                                                                            PBPTJD, 
                                                                            PBPTHM,
                                                                            PBRCJD,
                                                                            PBRCHM,
                                                                            PBSHPZ,
                                                                            PDLOC#, 
                                                                           CASE 
                                                                                WHEN MIN(LOONHD, (PDPCKS/PDPKGU)) < 0 
                                                                                then 0 
                                                                                else MIN(LOONHD, (PDPCKS/PDPKGU)) end as UNITS,
                                                                           CASE WHEN MIN(LOONHD, (PDPCKS/PDPKGU)) = LOONHD and MIN(LOONHD, (PDPCKS/PDPKGU)) <> (PDPCKS/PDPKGU) THEN 1 else 0 end as PREDSHORT,
                                                                            (SELECT sum(case when PCEWCP <> ' ' then 1 else 0 end) FROM $schema.NPFCPC where PCWHSE in(0,$whsesel) and PCITEM = PDITEM ) as WATCHCOUNT, 
                                                                            IMLOCT as LOCTYPE,
                                                                            LMTIER,
                                                                            CASE WHEN PDFL11 = 'C' then 'COLGATE' else ' ' end as COLGATE
                                                                        FROM $schema.NOTWPD  
                                                                            JOIN $schema.NPFIMS on IMITEM = PDITEM   
                                                                            JOIN $schema.NPFLSM on PDWHSE = LMWHSE and LMITEM = PDITEM and LMLOC# = PDLOC# 
                                                                            JOIN $schema.NOTWPB on PDWCS# = PBWCS# and PDWKNO = PBWKNO and PBBOX# = PDBOX#
                                                                            JOIN $schema.NPFLOC on PDWHSE = LOWHSE and LOITEM = PDITEM and LOLOC# = PDLOC# 
                                                                        WHERE PDWHSE = $whsesel and 
                                                                            PDBXSZ <> 'CSE' and 
                                                                            PDCART > 0 and 
                                                                            PDLOC# not like '%SDS%' and 
                                                                            PDLOC# not like 'M%'
                                                                            $printlimiter");
    $sql_looselines->execute();
    $array_looselines = $sql_looselines->fetchAll(pdo::FETCH_ASSOC);

//write array to temp table temp_looselines
    $values_loosetemp = array();

    $maxrange = 3999;
    $counter = 0;
    $rowcount = count($array_looselines);

    do {
        if ($maxrange > $rowcount) {  //prevent undefined offset
            $maxrange = $rowcount - 1;
        }
        $data = array();
        $values_loosetemp = array();
        while ($counter <= $maxrange) { //split into 5,000 lines segments to insert into merge table
            $temploose_whse = intval($array_looselines[$counter]['PDWHSE']);
            $temploose_wcs = intval($array_looselines[$counter]['PDWCS#']);
            $temploose_cart = intval($array_looselines[$counter]['PDCART']);
            $temploose_printdate = _jdatetomysqldate($array_looselines[$counter]['PBPTJD']);
            $temploose_printhourmin = intval($array_looselines[$counter]['PBPTHM']);
            if ($temploose_printhourmin < 999) {
                $temploose_printhourmin = '0' . $temploose_printhourmin;
            }
            $temploose_printdatetime = date('Y-m-d H:i:s', strtotime("$temploose_printdate $temploose_printhourmin"));
            $temploose_recdate = _jdatetomysqldate($array_looselines[$counter]['PBRCJD']);
            $temploose_rechourmin = intval($array_looselines[$counter]['PBRCHM']);
            if ($temploose_rechourmin < 999) {
                $temploose_rechourmin = '0' . $temploose_rechourmin;
            }
            $temploose_recdatetime = date('Y-m-d H:i:s', strtotime("$temploose_recdate $temploose_rechourmin"));
            $temploose_shipzone = $array_looselines[$counter]['PBSHPZ'];
            $temploose_loc = $array_looselines[$counter]['PDLOC#'];
            $temploose_units = intval($array_looselines[$counter]['UNITS']);
            $temploose_wcard = intval($array_looselines[$counter]['WATCHCOUNT']);
            $temploose_loctype = $array_looselines[$counter]['LOCTYPE'];
            $temploose_colg = $array_looselines[$counter]['COLGATE'];
            $tier = $array_looselines[$counter]['LMTIER'];
            $predshort = intval($array_looselines[$counter]['PREDSHORT']);
            $temploose_locjoin = substr($temploose_loc, 0, 6);
            //need to add logic for colgate
            $temploose_picktype = _picktype($temploose_loctype, $tier);

            $data[] = "($temploose_whse, $temploose_wcs, $temploose_cart, '$temploose_loc', $temploose_units, $temploose_wcard, '$temploose_loctype', '$temploose_locjoin','$temploose_picktype',$predshort,'$temploose_printdatetime', '$temploose_recdatetime', '$temploose_shipzone','$temploose_colg')";
            $counter += 1;
        }
        $values_loosetemp = implode(', ', $data);
        if (empty($values_loosetemp)) {
            break;
        }
        $sql = "INSERT IGNORE INTO printvis.temp_looselines ($col_loosetemp) VALUES $values_loosetemp";
        $query = $conn1->prepare($sql);
        $query->execute();
        $maxrange += 4000;

        //end of dowhile loop to add loose lines to temp_looselines table
    } while ($counter <= $rowcount);

    //error reproting for locs not in the pickpred map
    $sql_error = $conn1->prepare("INSERT IGNORE INTO printvis.error_loosepickmap 
                                                                        (SELECT DISTINCT
                                                                                    temploose_whse, temploose_locjoin
                                                                                FROM
                                                                                    printvis.temp_looselines
                                                                                        LEFT JOIN
                                                                                    printvis.pickprediction_loosepickmap ON loosemap_whse = temploose_whse
                                                                                        AND loosemap_location = temploose_locjoin
                                                                                WHERE
                                                                                    loosemap_location IS NULL and (temploose_locjoin like 'A%' or temploose_locjoin like 'B%' or temploose_locjoin like 'C%') )");
    $sql_error->execute();

    //Delete records from the error file if they have been added to the pickprediction_loosepickmap
    $sql_error_delete = $conn1->prepare("  DELETE FROM printvis.error_loosepickmap 
                                                                        WHERE
                                                                            error_loc IN (SELECT 
                                                                                loosemap_location
                                                                            FROM
                                                                                printvis.pickprediction_loosepickmap
                                                                                      WHERE
                                                                                loosemap_whse = error_whse and loosemap_location = error_loc)");
    $sql_error_delete->execute();

    //Join the temp_looselines to the pickprediction_loosepickmap to get additional info of x,y,z, opening height and distance
    //The pickprediction_loosepickmap should be automatically downloaded from the NV server once access is gained.
    //Write to the looselines table
    $sql_looselines_joined = $conn1->prepare("INSERT IGNORE into printvis.looselines(
                                            SELECT 
                                                temploose_whse,
                                                temploose_wcs,
                                                temploose_cart,
                                                temploose_picktype,
                                                temploose_loc,
                                                loosemap_main,
                                                temploose_units,
                                                temploose_wcard,
                                                temploose_loctype,
                                                temploose_locjoin,
                                                loosemap_xcoor,
                                                loosemap_ycoor,
                                                loosemap_zcoor,
                                                loosemap_tier,
                                                loosemap_openingheight,
                                                loosemap_baydistance,
                                                temploose_predshort,
                                                temploose_printdatetime,
                                                temploose_recdatetime,
                                                 temploose_shipzone,
                                                 temploose_colg
                                            FROM
                                                printvis.temp_looselines
                                                    JOIN
                                                printvis.pickprediction_loosepickmap ON temploose_locjoin = loosemap_location and temploose_whse = loosemap_whse
                                                WHERE temploose_whse = $whsesel)");
    $sql_looselines_joined->execute();

    //Group the looselines table by aisle to determine maximum travel distance by aisle and calculate aisle time and write to looselines_aisletime table

    $jmputsql = 'CASE
                                WHEN COUNT(*) >= 7 or  SUM(loose_units) >=20 THEN 2
                                WHEN COUNT(*) >= 3 or  SUM(loose_units) >=10 THEN 1
                                ELSE 0
                            END';

    $sql_aisletimes = $conn1->prepare("INSERT INTO  printvis.looselines_aisletime ( SELECT 
    loose_whse,
    loose_cart,
    SUBSTR(loose_loc, 1, 3) AS AISLE,
    loose_main,
    loose_picktype,
    COUNT(*) AS LINE_COUNT,
    SUM(loose_units) AS UNIT_COUNT,
    SUM(loose_wcard) AS WC_COUNT,
    SUM(CASE
        WHEN loose_loctype = 'R' THEN 1
        ELSE 0
    END) AS FRIDGE_COUNT,
    SUM(CASE
        WHEN loose_loctype IN ('RI' , 'RS') THEN 1
        ELSE 0
    END) AS ICE_COUNT,
    SUM(CASE
        WHEN loose_ycoor > 60 THEN 1
        ELSE 0
    END) AS LADDER_COUNT,
    SUM(CASE
        WHEN loose_openheight = 6 THEN 1
        ELSE 0
    END) AS PULLBIN_COUNT,
    $jmputsql AS JMPUT_COUNT,
    MAX(loose_baydistance) + (CASE
        WHEN loose_main = 'AISLE' THEN ((COUNT(*) - 1) * 3.68)
        ELSE 0
    END)  + ((MAX(loose_baydistance)/4) * $jmputsql)  AS INNERAISLETRAVEL,
    voice_pickline * COUNT(*) AS TIME_PICKLINE,
    voice_watchcard * SUM(loose_wcard) AS TIME_WATCHCARD,
    voice_refrigerated * SUM(CASE
        WHEN loose_loctype = 'R' THEN 1
        ELSE 0
    END) AS TIME_FRIDGE,
    voice_pullbin * SUM(CASE
        WHEN loose_openheight = 6 THEN 1
        ELSE 0
    END) AS TIME_PULLBIN,
    voice_pickunit * SUM(loose_units) AS TIME_UNIT,
    voice_ladderr * SUM(CASE
        WHEN loose_ycoor > 60 THEN 1
        ELSE 0
    END) AS TIME_LADDER,
    voice_putline * (1 +  $jmputsql) AS TIME_PUTLINE,
    voice_putunits * SUM(loose_units) AS TIME_PUTUNIT,
    (voice_foottraveltime * 12 * (((MAX(loose_baydistance)/4) * $jmputsql) + MAX(loose_baydistance) + (CASE
        WHEN loose_main = 'AISLE' THEN ((COUNT(*) - 1) * 3.68)
        ELSE 0
    END))) AS TIME_AISLETRAVEL,
    SUM(loose_predshort) as PREDSHORT,
    '$todaydatetime',
    loose_printdatetime,
    loose_recdatetime,
    MIN(case when cutoff_rank is null then 999 else cutoff_rank end) as SHIPZONERANK,
    sum(case when loose_colg = 'COLGATE' then 1 else 0 end)  as COLG_COUNT
FROM
    printvis.looselines
        JOIN
    printvis.pm_voicetimes ON loose_picktype = voice_function and loose_whse = voice_whse
    LEFT JOIN printvis.printcutoff on substr(loose_shipzone,1,2) = substr(cutoff_zone,1,2)  and cutoff_DC = loose_whse
    WHERE loose_whse = $whsesel
GROUP BY     loose_whse,
    loose_cart,
    SUBSTR(loose_loc, 1, 3),
    loose_main,
    loose_picktype,
    '$todaydatetime',
    loose_printdatetime,
    loose_recdatetime)
                                                                        ON DUPLICATE key update 
                                                                        aisletime_count_line=values(aisletime_count_line), 
                                                                        aisletime_count_unit=values(aisletime_count_unit), 
                                                                        aisletime_count_wcard=values(aisletime_count_wcard), 
                                                                        aisletime_count_fridge=values(aisletime_count_fridge), 
                                                                        aisletime_count_ice=values(aisletime_count_ice), 
                                                                        aisletime_count_ladder=values(aisletime_count_ladder), 
                                                                        aisletime_count_pullbin=values(aisletime_count_pullbin), 
                                                                        aisletime_count_jmput=values(aisletime_count_jmput), 
                                                                        aisletime_sum_aisletravel=values(aisletime_sum_aisletravel), 
                                                                        aisletime_time_pickline=values(aisletime_time_pickline), 
                                                                        aisletime_time_wcard=values(aisletime_time_wcard), 
                                                                        aisletime_time_fridge=values(aisletime_time_fridge), 
                                                                        aisletime_time_pullbin=values(aisletime_time_pullbin), 
                                                                        aisletime_time_unit=values(aisletime_time_unit), 
                                                                        aisletime_time_ladder=values(aisletime_time_ladder), 
                                                                        aisletime_time_putline=values(aisletime_time_putline), 
                                                                        aisletime_time_putunit=values(aisletime_time_putunit), 
                                                                        aisletime_predshort=values(aisletime_predshort), 
                                                                        aisletime_shipzone=values(aisletime_shipzone), 
                                                                        aisletime_colgcount=values(aisletime_colgcount), 
                                                                        aisletime_time_aisletravel=values(aisletime_time_aisletravel)");
    $sql_aisletimes->execute();

    //Group looselines_aisle table by batch to determine total batch times.  Add batch header times!



    $sql_batchtimes = $conn1->prepare("INSERT INTO printvis.looselines_batchtime(
                                                                    SELECT 
    aisletime_whse,
    aisletime_cart,
    SUM(aisletime_count_line) AS COUNT_LINE,
    SUM(aisletime_count_unit) AS COUNT_UNIT,
    SUM(aisletime_count_wcard) AS COUNT_WCARD,
    SUM(aisletime_count_fridge) AS COUNT_FRIDGE,
    SUM(aisletime_count_ice) AS COUNT_ICE,
    SUM(aisletime_count_ladder) AS COUNT_LADDER,
    SUM(aisletime_count_pullbin) AS COUNT_PULLBIN,
    SUM(aisletime_count_jmput) AS COUNT_JMPUT,
    SUM(aisletime_sum_aisletravel) AS SUM_AISLETRAVEL,
    SUM(aisletime_time_pickline) AS TIME_PICKLINE,
    SUM(aisletime_time_wcard) AS TIME_WCARD,
    SUM(aisletime_time_fridge) AS TIME_FRIDGE,
    SUM(aisletime_time_pullbin) AS TIME_PULLBIN,
    SUM(aisletime_time_unit) AS TIME_PICKUNIT,
    SUM(aisletime_time_ladder) AS TIME_LADDER,
    SUM(aisletime_time_putline) AS TIME_PUTLINE,
    SUM(aisletime_time_putunit) AS TIME_PUTUNIT,
    SUM(aisletime_time_aisletravel) AS TIME_AISLETRAVEL,
    voice_scanon AS TIME_SCANON,
    voice_prep AS TIME_PREP,
    voice_complete AS TIME_COMPLETE,
    voice_scanoff AS TIME_SCANOFF,
    CASE
        WHEN
            aisletime_whse IN (2,6)
        THEN
            CASE
                WHEN SUBSTR(MIN(aisletime_aisle), 1, 1) <> SUBSTR(MAX(aisletime_aisle), 1, 1) THEN voice_carttraveltime_entire
                WHEN SUBSTR(MIN(aisletime_aisle), 1, 1) = 'B' THEN voice_carttraveltime_B
                ELSE voice_carttraveltime_A
            END
                WHEN
	aisletime_whse = 3
            THEN
                 CASE
	WHEN SUBSTR(MIN(aisletime_aisle),1, 3) > 'B50' THEN voice_carttraveltime_B
	WHEN SUBSTR(MAX(aisletime_aisle),1, 3) < 'B50' THEN voice_carttraveltime_A
	ELSE voice_carttraveltime_entire
                  END
        ELSE voice_carttraveltime_entire
	END AS TIME_HIGHWAY,
    SUM(aisletime_time_pickline) + SUM(aisletime_time_wcard) + SUM(aisletime_time_fridge) + SUM(aisletime_time_pullbin) + SUM(aisletime_time_unit) + SUM(aisletime_time_ladder) + SUM(aisletime_time_putline) + SUM(aisletime_time_putunit) + SUM(aisletime_time_aisletravel) + (sum(aisletime_predshort) * voice_shortcomplete) + voice_scanon + voice_prep + voice_complete + voice_scanoff + CASE
        WHEN
            aisletime_whse IN (2,6)
        THEN
            CASE
                WHEN SUBSTR(MIN(aisletime_aisle), 1, 1) <> SUBSTR(MAX(aisletime_aisle), 1, 1) THEN voice_carttraveltime_entire
                WHEN SUBSTR(MIN(aisletime_aisle), 1, 1) = 'B' THEN voice_carttraveltime_B
                ELSE voice_carttraveltime_A
            END
                WHEN
	aisletime_whse = 3
            THEN
                 CASE
	WHEN SUBSTR(MIN(aisletime_aisle),1, 3) > 'B50' THEN voice_carttraveltime_B
	WHEN SUBSTR(MAX(aisletime_aisle),1, 3) < 'B50' THEN voice_carttraveltime_A
	ELSE voice_carttraveltime_entire
                  END
        ELSE voice_carttraveltime_entire
	END AS TIME_TOTAL,
sum(aisletime_predshort) as PREDSHORT,   
sum(aisletime_predshort) * voice_shortcomplete as TIME_SHORT,
'$todaydatetime',
aisletime_printdatetime,
aisletime_recdatetime,
MIN(aisletime_shipzone),
SUM(aisletime_colgcount) as COLG_COUNT,
0 as exported
FROM
    printvis.looselines_aisletime
        JOIN
    printvis.pm_voicetimes ON aisletime_picktype = voice_function
        AND aisletime_whse = voice_whse
WHERE
    aisletime_whse = $whsesel
GROUP BY aisletime_whse , aisletime_cart,    voice_scanon,
    voice_prep,
    voice_complete,
    voice_scanoff)
                                                                    ON duplicate key update
                                                                    batchtime_count_line=values(batchtime_count_line),
                                                                    batchtime_count_unit=values(batchtime_count_unit),
                                                                    batchtime_count_wcard=values(batchtime_count_wcard),
                                                                    batchtime_count_fridge=values(batchtime_count_fridge),
                                                                    batchtime_count_ice=values(batchtime_count_ice),
                                                                    batchtime_count_ladder=values(batchtime_count_ladder),
                                                                    batchtime_count_pullbin=values(batchtime_count_pullbin),
                                                                    batchtime_count_jmput=values(batchtime_count_jmput),
                                                                    batchtime_sum_aisletravel=values(batchtime_sum_aisletravel),
                                                                    batchtime_time_pickline=values(batchtime_time_pickline),
                                                                    batchtime_time_wcard=values(batchtime_time_wcard),
                                                                    batchtime_time_fridge=values(batchtime_time_fridge),
                                                                    batchtime_time_pullbin=values(batchtime_time_pullbin),
                                                                    batchtime_time_unit=values(batchtime_time_unit),
                                                                    batchtime_time_ladder=values(batchtime_time_ladder),
                                                                    batchtime_time_putline=values(batchtime_time_putline),
                                                                    batchtime_time_putunit=values(batchtime_time_putunit),
                                                                    batchtime_time_aisletravel=values(batchtime_time_aisletravel),
                                                                    batchtime_time_scanon=values(batchtime_time_scanon),
                                                                    batchtime_time_prep=values(batchtime_time_prep),
                                                                    batchtime_time_complete=values(batchtime_time_complete),
                                                                    batchtime_time_scanoff=values(batchtime_time_scanoff),
                                                                    batchtime_time_highway=values(batchtime_time_highway),
                                                                    batchtime_predshort=values(batchtime_predshort),
                                                                    batchtime_shipzone=values(batchtime_shipzone),
                                                                    batchtime_colgcount=values(batchtime_colgcount),
                                                                    batchtime_shorttime=values(batchtime_shorttime),
                                                                    batchtime_time_totaltime=values(batchtime_time_totaltime)");
    $sql_batchtimes->execute();

    //add to taskpred table
//    $taskpredcolumns = 'taskpred_id, taskpred_whse, taskpred_function, taskpred_type, taskpred_mintime, taskpred_maxtime, taskpred_updatetime';
//    $sql_looselines_taskpred = $conn1->prepare("INSERT IGNORE INTO printvis.taskpred($taskpredcolumns)
//                                                                                                SELECT 
//                                                                                                    LPAD(batchtime_cart, 5, '0') AS BATCH,
//                                                                                                    batchtime_whse,
//                                                                                                    'PIK',
//                                                                                                    'LOOSEPICK',
//                                                                                                    CASE
//                                                                                                        WHEN CAST(batchtime_time_totaltime AS UNSIGNED) - 1 > 999 THEN 999
//                                                                                                        ELSE CAST(batchtime_time_totaltime AS UNSIGNED) - 1
//                                                                                                    END AS MINTIME,
//                                                                                                    CASE
//                                                                                                        WHEN CAST(batchtime_time_totaltime AS UNSIGNED) > 999 THEN 999
//                                                                                                        ELSE CAST(batchtime_time_totaltime AS UNSIGNED)
//                                                                                                    END AS MAXTIME,
//                                                                                                    '$todaydatetime'
//                                                                                                FROM
//                                                                                                    printvis.looselines_batchtime 
//                                                                                                WHERE batchtime_whse = $whsesel;");
//    $sql_looselines_taskpred->execute();
    //write to aisle time historical file

    $ftpdatetime = date('Ymd_His');
    $sql_looselines_taskpred = $conn1->prepare("SELECT LPAD(batchtime_cart, 5, '0') AS batchnum, 
		CASE
			WHEN CAST(batchtime_time_totaltime AS UNSIGNED) > 999 THEN '00999'
			ELSE LPAD(CAST(batchtime_time_totaltime AS UNSIGNED), 5, '0')
			END AS MAXTIME
		FROM printvis.looselines_batchtime WHERE (batchtime_whse = $whsesel) AND (batchtime_exported = 0)");

    $sql_looselines_taskpred->execute();
    $numrows = $sql_looselines_taskpred->rowCount();
    if ($numrows > 0 && $whsesel <> 11) {
        $filename = "picktimes_whse" . $whsesel . "_" . $ftpdatetime . ".gol";
        $fp = fopen("./exports/$filename", "w"); //open for write
        $data = "";
        $updatearray = array();
        foreach ($sql_looselines_taskpred as $picktimerow) {
            $data .= $picktimerow['batchnum'] . $picktimerow['MAXTIME'] . "     \r\n";
            $updatearray[] = $picktimerow['batchnum'];
        }
        fwrite($fp, $data);
        fclose($fp);
        $updatewhere = implode(',', $updatearray);
        $updateflag = $conn1->prepare("UPDATE printvis.looselines_batchtime SET batchtime_exported = 1 WHERE batchtime_cart IN($updatewhere)");
        $updateflag->execute();
        $sendftp = _ftpupload($filename, $whsesel);
    }

//END PICK TIME EXPORT	

    $sql_looselines_aisletimehist = $conn1->prepare("INSERT  INTO printvis.looselines_aisletime_hist
                                                                                                (SELECT * FROM printvis.looselines_aisletime where aisletime_whse = $whsesel)ON DUPLICATE key update 
                                                                        aisletime_count_line=values(aisletime_count_line), 
                                                                        aisletime_count_unit=values(aisletime_count_unit), 
                                                                        aisletime_count_wcard=values(aisletime_count_wcard), 
                                                                        aisletime_count_fridge=values(aisletime_count_fridge), 
                                                                        aisletime_count_ice=values(aisletime_count_ice), 
                                                                        aisletime_count_ladder=values(aisletime_count_ladder), 
                                                                        aisletime_count_pullbin=values(aisletime_count_pullbin), 
                                                                        aisletime_count_jmput=values(aisletime_count_jmput), 
                                                                        aisletime_sum_aisletravel=values(aisletime_sum_aisletravel), 
                                                                        aisletime_time_pickline=values(aisletime_time_pickline), 
                                                                        aisletime_time_wcard=values(aisletime_time_wcard), 
                                                                        aisletime_time_fridge=values(aisletime_time_fridge), 
                                                                        aisletime_time_pullbin=values(aisletime_time_pullbin), 
                                                                        aisletime_time_unit=values(aisletime_time_unit), 
                                                                        aisletime_time_ladder=values(aisletime_time_ladder), 
                                                                        aisletime_time_putline=values(aisletime_time_putline), 
                                                                        aisletime_time_putunit=values(aisletime_time_putunit), 
                                                                        aisletime_predshort=values(aisletime_predshort), 
                                                                        aisletime_shipzone=values(aisletime_shipzone), 
                                                                        aisletime_colgcount=values(aisletime_colgcount), 
                                                                        aisletime_time_aisletravel=values(aisletime_time_aisletravel)");
    $sql_looselines_aisletimehist->execute();
    //write to batch time historical file
    $sql_looselines_batchtimehist = $conn1->prepare("INSERT INTO printvis.looselines_batchtime_hist
                                                                                                (SELECT * FROM printvis.looselines_batchtime where batchtime_whse = $whsesel)ON duplicate key update
                                                                    batchtime_count_line=values(batchtime_count_line),
                                                                    batchtime_count_unit=values(batchtime_count_unit),
                                                                    batchtime_count_wcard=values(batchtime_count_wcard),
                                                                    batchtime_count_fridge=values(batchtime_count_fridge),
                                                                    batchtime_count_ice=values(batchtime_count_ice),
                                                                    batchtime_count_ladder=values(batchtime_count_ladder),
                                                                    batchtime_count_pullbin=values(batchtime_count_pullbin),
                                                                    batchtime_count_jmput=values(batchtime_count_jmput),
                                                                    batchtime_sum_aisletravel=values(batchtime_sum_aisletravel),
                                                                    batchtime_time_pickline=values(batchtime_time_pickline),
                                                                    batchtime_time_wcard=values(batchtime_time_wcard),
                                                                    batchtime_time_fridge=values(batchtime_time_fridge),
                                                                    batchtime_time_pullbin=values(batchtime_time_pullbin),
                                                                    batchtime_time_unit=values(batchtime_time_unit),
                                                                    batchtime_time_ladder=values(batchtime_time_ladder),
                                                                    batchtime_time_putline=values(batchtime_time_putline),
                                                                    batchtime_time_putunit=values(batchtime_time_putunit),
                                                                    batchtime_time_aisletravel=values(batchtime_time_aisletravel),
                                                                    batchtime_time_scanon=values(batchtime_time_scanon),
                                                                    batchtime_time_prep=values(batchtime_time_prep),
                                                                    batchtime_time_complete=values(batchtime_time_complete),
                                                                    batchtime_time_scanoff=values(batchtime_time_scanoff),
                                                                    batchtime_time_highway=values(batchtime_time_highway),
                                                                    batchtime_predshort=values(batchtime_predshort),
                                                                    batchtime_shipzone=values(batchtime_shipzone),
                                                                    batchtime_colgcount=values(batchtime_colgcount),
                                                                    batchtime_shorttime=values(batchtime_shorttime),
                                                                    batchtime_time_totaltime=values(batchtime_time_totaltime)");
    $sql_looselines_batchtimehist->execute();

    switch ($whsesel) {
        case 6:

            //pull in batches that have started picking to clean up display
            $cartspicked = $dbh->prepare("SELECT DISTINCT
                                           CAST(JSON_VALUE(T.UserDefinedData, '$.Warehouse') as INT) as Warehouse,
                                               JSON_VALUE(T.UserDefinedData, '$.BatchNum') as Batch_Num,
                                               min([LastEventOccurredDateTimeLocal]) as DateTimeFirstPick,
                                               LastPickedUserLogin as ReserveUserID,
                                               JSON_VALUE(T.UserDefinedData, '$.CartFlag') as CartConfigTemp,
                                               JSON_VALUE(T.UserDefinedData, '$.CartShelves') as CartShelves
                                    FROM dbo.Task T (NOLOCK) INNER JOIN  dbo.TaskState TS (NOLOCK) on T.TaskID = TS.TaskID
                                    WHERE CAST(JSON_VALUE(T.UserDefinedData, '$.Warehouse') as INT) = $whsesel
                                    GROUP BY  CAST(JSON_VALUE(T.UserDefinedData, '$.Warehouse') as INT), JSON_VALUE(T.UserDefinedData, '$.BatchNum'), LastPickedUserLogin, JSON_VALUE(T.UserDefinedData, '$.CartFlag'), JSON_VALUE(T.UserDefinedData, '$.CartShelves')
                                    HAVING  min([LastEventOccurredDateTimeLocal]) >= '$printcutoff'");
            $cartspicked->execute();
            $cartspicked_array = $cartspicked->fetchAll(pdo::FETCH_ASSOC);

            break;

        default:
            //pull in batches that have started picking to clean up display
            $cartspicked = $dbh->prepare("SELECT DISTINCT  Batch.Warehouse, Batch.Batch_Num, Batch.DateTimeFirstPick, Batch.ReserveUserID, Batch.CartConfigTemp, Batch.CartShelves
                                                            FROM HenrySchein.dbo.Batch Batch
                                                            WHERE   Batch.Warehouse = $whsesel and Batch.DATECREATED > '$printcutoff' ");
            $cartspicked->execute();
            $cartspicked_array = $cartspicked->fetchAll(pdo::FETCH_ASSOC);
            break;
    }



    //delete records in open box file that are now printed
    $sql_delete = $conn1->prepare("DELETE FROM t1 USING printvis.looselines_aisletime_open t1
                                                            INNER JOIN
                                                        printvis.looselines t2 ON (t1.aisletime_wcs = t2.loose_wcs) WHERE aisletime_whse = $whsesel;");
    $sql_delete->execute();

    //loop $cartspicked_array and add to voice_batchespicked table
    $cartsdata = array();
    foreach ($cartspicked_array as $key => $value) {
        $batch = $cartspicked_array[$key]['Batch_Num'];
        $datetime = $cartspicked_array[$key]['DateTimeFirstPick'];
        if (is_null($datetime)) {
            $datetime = '1900-01-01 23:59:59';
        }
        $userid = $cartspicked_array[$key]['ReserveUserID'];
        if (is_null($userid)) {
            $userid = 0;
        }
        $cartconfig = $cartspicked_array[$key]['CartConfigTemp'];
        if (is_null($cartconfig)) {
            $cartconfig = ' ';
        }
        $cartshelf = $cartspicked_array[$key]['CartShelves'];
        if (is_null($cartshelf)) {
            $cartshelf = ' ';
        }
        $cartsdata[] = "($whsesel, $batch, '$datetime', $userid, '$cartconfig','$cartshelf')";
    }
    $values_cart = implode(', ', $cartsdata);
    if (!empty($values_cart)) {
        $sql = "INSERT INTO printvis.voice_batchespicked ($col_cartspicked) VALUES $values_cart ON DUPLICATE KEY UPDATE "
                . "voice_startdatetime=values(voice_startdatetime),"
                . "voice_userid=values(voice_userid)";
        $query = $conn1->prepare($sql);
        $query->execute();
    }

    //insert voice picked info into printvis.voicepicks table
    $linespicked = $dbh->prepare("SELECT Pick.Pick_ID, Pick.Batch_Num, Pick.Status, Pick.Short_Status, Pick.Location, Pick.Sect, Pick.Aisle, Pick.Bay, Pick.Lev, Pick.Pos, Pick.PickType, Pick.LotReq, Pick.QtyOrder, Pick.QtyPick, Pick.PackageUnit, Pick.Drug, Pick.Ice, Pick.Haz, Pick.SO, Pick.SN, Pick.NSI, Pick.Ped, Pick.ExpyChkReq, Pick.ItemCode, Pick.NDC_Num, Pick.EachWeight, Pick.DateTimeFirstPick, Pick.DATECREATED, Pick.BO, Pick.PutAwayFlag, substring(Location,1,6) as LOCJOIN, Tote.WCS_NUM, Tote.WORKORDER_NUM, Tote.BOX_NUM, Tote.TOTELOCATION, Tote.SHIP_ZONE, Users.UserDescription, Pick.ReserveUSerID   
                                                        FROM HenrySchein.dbo.Batch Batch JOIN HenrySchein.dbo.Pick Pick on Batch.Batch_ID = Pick.Batch_ID JOIN HenrySchein.dbo.Tote Tote on Tote.Batch_ID = Batch.Batch_ID JOIN JenX.dbo.Users Users on Pick.ReserveUserID = Users.UserName 
                                                        WHERE Tote.Tote_ID = Pick.Tote_ID AND Pick.Batch_ID = Tote.Batch_ID AND ((Pick.DateTimeFirstPick>='$today $printhourmin_colon'))");
    $linespicked->execute();
    $linespicked_array = $linespicked->fetchAll(pdo::FETCH_ASSOC);
    foreach ($linespicked_array as $key => $value) {

        $Pick_ID = $linespicked_array[$key]['Pick_ID'];
        $Batch_Num = $linespicked_array[$key]['Batch_Num'];
        $Status = $linespicked_array[$key]['Status'];
        $Short_Status = $linespicked_array[$key]['Short_Status'];
        $Location = $linespicked_array[$key]['Location'];
        $Sect = $linespicked_array[$key]['Sect'];
        $Aisle = $linespicked_array[$key]['Aisle'];
        $Bay = $linespicked_array[$key]['Bay'];
        $Lev = $linespicked_array[$key]['Lev'];
        $Pos = $linespicked_array[$key]['Pos'];
        $PickType = !empty($linespicked_array[$key]['PickType']) ? $linespicked_array[$key]['PickType'] : "NULL";
        $LotReq = $linespicked_array[$key]['LotReq'];
        $QtyOrder = $linespicked_array[$key]['QtyOrder'];
        $QtyPick = $linespicked_array[$key]['QtyPick'];
        $PackageUnit = $linespicked_array[$key]['PackageUnit'];
        $Drug = $linespicked_array[$key]['Drug'];
        $Ice = $linespicked_array[$key]['Ice'];
        $Haz = $linespicked_array[$key]['Haz'];
        $SO = $linespicked_array[$key]['SO'];
        $SN = $linespicked_array[$key]['SN'];
        $NSI = $linespicked_array[$key]['NSI'];
        $Ped = $linespicked_array[$key]['Ped'];
        $ExpyChkReq = $linespicked_array[$key]['ExpyChkReq'];
        $ItemCode = $linespicked_array[$key]['ItemCode'];
        $NDC_Num = !empty($linespicked_array[$key]['NDC_Num']) ? $linespicked_array[$key]['NDC_Num'] : "NULL";
        $EachWeight = $linespicked_array[$key]['EachWeight'];
        $DateTimeFirstPick = !empty($linespicked_array[$key]['DateTimeFirstPick']) ? $linespicked_array[$key]['DateTimeFirstPick'] : "NULL";
        $DATECREATED = $linespicked_array[$key]['DATECREATED'];
        $BO = $linespicked_array[$key]['BO'];
        $PutAwayFlag = $linespicked_array[$key]['PutAwayFlag'];
        $LOCJOIN = $linespicked_array[$key]['LOCJOIN'];
        $WCS_NUM = $linespicked_array[$key]['WCS_NUM'];
        $WORKORDER_NUM = $linespicked_array[$key]['WORKORDER_NUM'];
        $BOX_NUM = $linespicked_array[$key]['BOX_NUM'];
        $TOTELOC = $linespicked_array[$key]['TOTELOCATION'];
        $SHIP_ZONE = $linespicked_array[$key]['SHIP_ZONE'];
        $UserDescription = htmlspecialchars($linespicked_array[$key]['UserDescription'], ENT_QUOTES);

        $ReserveUSerID = $linespicked_array[$key]['ReserveUSerID'];

        $pickdata[] = "('$Pick_ID', $whsesel, $Batch_Num, $Status, $Short_Status, '$Location', '$Sect', '$Aisle', '$Bay', '$Lev', '$Pos', '$PickType', $LotReq, $QtyOrder, $QtyPick, $PackageUnit, $Drug, $Ice, $Haz, $SO, $SN, $NSI, '$Ped', $ExpyChkReq,  $ItemCode, '$NDC_Num', '$EachWeight', '$DateTimeFirstPick', '$DATECREATED', $BO, $PutAwayFlag, '$LOCJOIN', $WCS_NUM, $WORKORDER_NUM, $BOX_NUM, $TOTELOC,'$SHIP_ZONE', '$UserDescription', $ReserveUSerID)";
        $pickdata_hist[] = "('$Pick_ID', $whsesel, $Batch_Num, '$Location', $ItemCode,'$DateTimeFirstPick', $WCS_NUM, $WORKORDER_NUM, $BOX_NUM, '$UserDescription', $ReserveUSerID)";
    }
    if (!empty($pickdata)) {
        $values_pick = implode(', ', $pickdata);
        $values_pick_hist = implode(', ', $pickdata_hist);

        $sql = "INSERT IGNORE INTO printvis.voicepicks ($col_linespicked) VALUES $values_pick";
        $query = $conn1->prepare($sql);
        $query->execute();
        $sql2 = "INSERT IGNORE INTO printvis.voicepicks_hist ($col_linespicked_hist) VALUES $values_pick_hist";
        $query2 = $conn1->prepare($sql2);
        $query2->execute();
    }
} //end of whsarray loop 
//Delete records from voicepicks table that are currently in packing.  This will increase speed of below queries as day progresses
$sqldelete8 = "DELETE FROM printvis.voicepicks 
                                WHERE
                                    Batch_Num IN (SELECT DISTINCT
                                        batch_start_batch
                                    FROM
                                        printvis.batch_start)";
$querydelete8 = $conn1->prepare($sqldelete8);
$querydelete8->execute();

//write for all DCs the current pick status of carts to looselines_cartsinprocess_temp
//write to a temp table, do a left join on main table to delete carts no longer needed
//do a left join on carts that are in the packing area and delete as no longer in picking if they are currenlty being packed.
$sqldelete5 = "TRUNCATE printvis.looselines_cartsinprocess_temp";
$querydelete5 = $conn1->prepare($sqldelete5);
$querydelete5->execute();

$sql_looselines_batchtimehist = $conn1->prepare("INSERT INTO printvis.looselines_cartsinprocess_temp
                                                                                                (SELECT 
                                                                                                    aisletime_whse,
                                                                                                    aisletime_cart,
                                                                                                    min(aisletime_shipzone) as SHIPZONE,
                                                                                                    min(aisletime_datetimeadded) as DATE_ADDED,
                                                                                                    min(aisletime_printdatetime) as DATE_PRINTDATE,
                                                                                                    min(aisletime_recdatetime) as DATE_RECDATE,
                                                                                                    @TOTALTIME:=(SELECT 
                                                                                                            SUM(aisletime_time_pickline + aisletime_time_wcard + aisletime_time_fridge + aisletime_time_pullbin + aisletime_time_unit + aisletime_time_ladder + aisletime_time_putline + aisletime_time_putunit + aisletime_time_aisletravel)
                                                                                                        FROM
                                                                                                            printvis.looselines_aisletime C
                                                                                                        WHERE
                                                                                                            C.aisletime_whse = A.aisletime_whse
                                                                                                                AND C.aisletime_cart = A.aisletime_cart) AS TOTALTIME,
                                                                                                    @CURRENTAISLE:=(SELECT 
                                                                                                            MAX(SUBSTR(Location, 1, 3))
                                                                                                        FROM
                                                                                                            printvis.voicepicks
                                                                                                        WHERE
                                                                                                            A.aisletime_whse = Whse
                                                                                                                AND A.aisletime_cart = Batch_Num) AS CURRENTAISLE,
                                                                                                    @PICKEDTIME:=(SELECT 
                                                                                                            SUM(aisletime_time_pickline + aisletime_time_wcard + aisletime_time_fridge + aisletime_time_pullbin + aisletime_time_unit + aisletime_time_ladder + aisletime_time_putline + aisletime_time_putunit + aisletime_time_aisletravel)
                                                                                                        FROM
                                                                                                            printvis.looselines_aisletime B
                                                                                                        WHERE
                                                                                                            B.aisletime_whse = A.aisletime_whse
                                                                                                                AND B.aisletime_cart = A.aisletime_cart
                                                                                                                AND B.aisletime_aisle <= @CURRENTAISLE) AS PICKED_TIME,
                                                                                                    @REMAININGTIME:=@TOTALTIME - @PICKEDTIME AS REMAININGTIME,
                                                                                                    @ELAPSEDTIME:=(SELECT 
                                                                                                            TIMESTAMPDIFF(MINUTE,
                                                                                                                    MIN(DateTimeFirstPick),
                                                                                                                    case when aisletime_whse = 3 then DATE_SUB(NOW(), INTERVAL 3 HOUR) when aisletime_whse = 7 then DATE_SUB(NOW(), INTERVAL 1 HOUR) else now() end)  AS ELAPSEDMIN
                                                                                                        FROM
                                                                                                            printvis.voicepicks D
                                                                                                        WHERE
                                                                                                            D.Whse = A.aisletime_whse
                                                                                                                AND D.Batch_Num = A.aisletime_cart
                                                                                                                AND DATE(DateTimeFirstPick) = CURDATE()) AS ELAPSEDTIME,
                                                                                                    CASE
                                                                                                        WHEN @ELAPSEDTIME + @REMAININGTIME - 2 > @TOTALTIME THEN 'LATE'
                                                                                                        ELSE 'ONTIME'
                                                                                                    END AS PICKSTATUS
                                                                                                FROM
                                                                                                    printvis.looselines_aisletime A
                                                                                                GROUP BY aisletime_cart
                                                                                                HAVING @REMAININGTIME > 0)");
$sql_looselines_batchtimehist->execute();

//Delete records no longer needed in looselines_cartsinprocess
$sqldelete7 = "DELETE FROM printvis.looselines_cartsinprocess 
                            WHERE
                                cartpick_cart NOT IN (SELECT DISTINCT
                                    cartpick_cart
                                FROM
                                    printvis.looselines_cartsinprocess_temp)";
$querydelete7 = $conn1->prepare($sqldelete7);
$querydelete7->execute();

//insert new records into looselines_cartsinprocess on duplicate key update
$sql_cartupdate = $conn1->prepare("INSERT INTO printvis.looselines_cartsinprocess 
                                                                (SELECT * FROM printvis.looselines_cartsinprocess_temp) on duplicate key update 
                                                                cartpick_shipzone=values(cartpick_shipzone),
                                                                cartpick_currentaisle=values(cartpick_currentaisle),
                                                                cartpick_picktime=values(cartpick_picktime),
                                                                cartpick_remaintime=values(cartpick_remaintime),
                                                                cartpick_elaptime=values(cartpick_elaptime),
                                                                cartpick_status=values(cartpick_status)");
$sql_cartupdate->execute();

//Cases manifested by batch
$casesman = $connection->prepare("SELECT PBCART,PBPTJD, count(*) as BOX_COUNT, sum(case when PBRLJD > 0 then 1 else 0 end) as REL_COUNT
                                                                                     FROM $schema.NOTWPB
                                                                                      group by PBCART,PBPTJD ");
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

include 'badges_updateshipzone.php';

