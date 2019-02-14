
<?php

//calculates time needed to pick each box that has not been printed. Cart = 0

include '../../globalincludes/usa_asys.php';
include '../../connections/conn_printvis.php';
ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../functions/functions_totetimes.php';

$whsearray = array(7, 2, 3, 6, 9);
//$whsearray = array(6);
$today = date('Y-m-d');
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

$sqldelete2 = "DELETE from  printvis.looselines_batchtime_open WHERE date(batchtime_datetimeadded) < '$yesterday'";
$querydelete2 = $conn1->prepare($sqldelete2);
$querydelete2->execute();

$sqldelete3 = "DELETE from  printvis.looselines_aisletime_open WHERE date(aisletime_datetimeadded) < '$yesterday'";
$querydelete3 = $conn1->prepare($sqldelete3);
$querydelete3->execute();
//column listings
$col_loosetemp = 'temploose_whse, temploose_wcs, temploose_box, temploose_loc, temploose_recdatetime, temploose_shipzone, temploose_units, temploose_wcard, temploose_loctype, temploose_locjoin, temploose_picktype, temploose_predshort,temploose_colg';


foreach ($whsearray as $whsesel) {
    include '../timezoneset.php';
    $todaydatetime = date('Y-m-d H:i:s');
    $sqldelete1 = "DELETE from  printvis.temp_looselines_open WHERE temploose_whse = $whsesel ";
    $querydelete1 = $conn1->prepare($sqldelete1);
    $querydelete1->execute();

    $sqldelete2 = "DELETE from  printvis.looselines_open WHERE loose_whse = $whsesel ";
    $querydelete2 = $conn1->prepare($sqldelete2);
    $querydelete2->execute();
//pull in all loose lines and write to temporary table to join later with pickprediction_loosepickmap table
    $sql_looselines = $aseriesconn->prepare("SELECT
                                                                            PDWHSE, 
                                                                            PDWCS#,
                                                                            PBBOX#, 
                                                                            PDLOC#, 
                                                                            PBRCJD,
                                                                            PBRCHM,
                                                                            PBSHPZ,
                                                                           CASE 
                                                                                WHEN MIN(LOONHD, (PDPCKS/PDPKGU)) < 0 
                                                                                then 0 
                                                                                else MIN(LOONHD, (PDPCKS/PDPKGU)) end as UNITS,
                                                                           CASE WHEN MIN(LOONHD, (PDPCKS/PDPKGU)) = LOONHD and MIN(LOONHD, (PDPCKS/PDPKGU)) <> (PDPCKS/PDPKGU) THEN 1 else 0 end as PREDSHORT,
                                                                            (SELECT sum(case when PCEWCP <> ' ' then 1 else 0 end) FROM HSIPCORDTA.NPFCPC where PCWHSE in(0,$whsesel) and PCITEM = PDITEM ) as WATCHCOUNT, 
                                                                            IMLOCT as LOCTYPE,
                                                                            LMTIER,
                                                                            CASE WHEN PDFL11 = 'C' then 'COLGATE' else ' ' end as COLGATE
                                                                        FROM HSIPCORDTA.NOTWPD  
                                                                            JOIN HSIPCORDTA.NPFIMS on IMITEM = PDITEM   
                                                                            JOIN HSIPCORDTA.NPFLSM on PDWHSE = LMWHSE and LMITEM = PDITEM and LMLOC# = PDLOC# 
                                                                            JOIN HSIPCORDTA.NOTWPB on PDWCS# = PBWCS# and PDWKNO = PBWKNO and PBBOX# = PDBOX#
                                                                            JOIN HSIPCORDTA.NPFLOC on PDWHSE = LOWHSE and LOITEM = PDITEM and LOLOC# = PDLOC# 
                                                                        WHERE PDWHSE = $whsesel and 
                                                                            PDBXSZ <> 'CSE' and 
                                                                            PDCART = 0 and 
                                                                            PDLOC# not like '%SDS%'  and 
                                                                       --     LMTIER not in ('L09','L06') and 
                                                                       --     LMTIER not like 'C%' and 
                                                                            PDLOC# not like 'M%'");
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
            $temploose_box = intval($array_looselines[$counter]['PBBOX#']);
            $temploose_loc = $array_looselines[$counter]['PDLOC#'];
            $temploose_recdate = _jdatetomysqldate($array_looselines[$counter]['PBRCJD']);
            $temploose_rechourmin = intval($array_looselines[$counter]['PBRCHM']);
            if ($temploose_rechourmin < 999) {
                $temploose_rechourmin = '0' . $temploose_rechourmin;
            }
            $temploose_recdatetime = date('Y-m-d H:i:s', strtotime("$temploose_recdate $temploose_rechourmin"));
            $temploose_shipzone = $array_looselines[$counter]['PBSHPZ'];
            $temploose_units = intval($array_looselines[$counter]['UNITS']);
            $temploose_wcard = intval($array_looselines[$counter]['WATCHCOUNT']);
            $temploose_loctype = $array_looselines[$counter]['LOCTYPE'];
            $tier = $array_looselines[$counter]['LMTIER'];
            $predshort = intval($array_looselines[$counter]['PREDSHORT']);
            $temploose_locjoin = substr($temploose_loc, 0, 6);
            $temploose_colg = $array_looselines[$counter]['COLGATE'];
            //need to add logic for colgate
            $temploose_picktype = _picktype($temploose_loctype, $tier);

            $data[] = "($temploose_whse, $temploose_wcs, $temploose_box, '$temploose_loc', '$temploose_recdatetime',  '$temploose_shipzone', $temploose_units, $temploose_wcard, '$temploose_loctype', '$temploose_locjoin','$temploose_picktype',$predshort,'$temploose_colg')";
            $counter += 1;
        }
        $values_loosetemp = implode(', ', $data);
        if (empty($values_loosetemp)) {
            break;
        }
        $sql = "INSERT IGNORE INTO printvis.temp_looselines_open ($col_loosetemp) VALUES $values_loosetemp";
        $query = $conn1->prepare($sql);
        $query->execute();
        $maxrange += 4000;

        //end of dowhile loop to add loose lines to temp_looselines table
    } while ($counter <= $rowcount);

    //Join the temp_looselines to the pickprediction_loosepickmap to get additional info of x,y,z, opening height and distance
    //The pickprediction_loosepickmap should be automatically downloaded from the NV server once access is gained.
    //Write to the looselines table
    $sql_looselines_joined = $conn1->prepare("INSERT IGNORE into printvis.looselines_open(
                                                                                                            SELECT 
                                                                                                                temploose_whse,
                                                                                                                temploose_wcs,
                                                                                                                temploose_box,
                                                                                                                temploose_picktype,
                                                                                                                temploose_loc,
                                                                                                                loosemap_main,
                                                                                                                temploose_recdatetime,
                                                                                                                temploose_shipzone,
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
                                                                                                                temploose_colg
                                                                                                            FROM
                                                                                                                printvis.temp_looselines_open
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

    $sql_aisletimes = $conn1->prepare("INSERT INTO  printvis.looselines_aisletime_open (  SELECT 
                                                                                                                                                                                            loose_whse,
                                                                                                                                                                                            loose_wcs,
                                                                                                                                                                                            loose_box,
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
                                                                                                                                                                                            loose_recdatetime,
                                                                                                                                                                                            MIN(case when cutoff_rank is null then 999 else cutoff_rank end) as SHIPZONERANK,
                                                                                                                                                                                            sum(case when loose_colg = 'COLGATE' then 1 else 0 end)  as COLG_COUNT
                                                                                                                                                                                        FROM
                                                                                                                                                                                            printvis.looselines_open
                                                                                                                                                                                                JOIN
                                                                                                                                                                                            printvis.pm_voicetimes ON loose_picktype = voice_function and loose_whse = voice_whse
                                                                                                                                                                                            LEFT JOIN printvis.printcutoff on substr(loose_shipzone,1,2) = substr(cutoff_zone,1,2)  and cutoff_DC = loose_whse
                                                                                                                                                                                            WHERE loose_whse = $whsesel
                                                                                                                                                                                        GROUP BY loose_whse ,loose_wcs, loose_box , SUBSTR(loose_loc, 1, 3) , loose_main) 
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
    $sql_batchtimes = $conn1->prepare("INSERT INTO printvis.looselines_batchtime_open(
                                                                     SELECT 
    aisletime_whse,
    aisletime_wcs,
    aisletime_box,
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
    voice_scanon / voiceTPC_Totes AS TIME_SCANON,
    voice_prep / voiceTPC_Totes AS TIME_PREP,
    voice_complete / voiceTPC_Totes AS TIME_COMPLETE,
    voice_scanoff / voiceTPC_Totes AS TIME_SCANOFF,
    5 / voiceTPC_Totes AS TIME_HIGHWAY,
    SUM(aisletime_time_pickline) + SUM(aisletime_time_wcard) + SUM(aisletime_time_fridge) + SUM(aisletime_time_pullbin) + SUM(aisletime_time_unit) + SUM(aisletime_time_ladder) + SUM(aisletime_time_putline) + SUM(aisletime_time_putunit) + SUM(aisletime_time_aisletravel) + (sum(aisletime_predshort) * voice_shortcomplete) + (voice_scanon / voiceTPC_Totes) + (voice_prep / voiceTPC_Totes) + (voice_complete / voiceTPC_Totes) + (voice_scanoff / voiceTPC_Totes) + (5 / voiceTPC_Totes) AS TIME_TOTAL,
    SUM(aisletime_predshort) AS PREDSHORT,
    sum(aisletime_predshort) * voice_shortcomplete as TIME_SHORT,
    '$todaydatetime',
    aisletime_recdatetime,
    MIN(aisletime_shipzone),
    SUM(aisletime_colgcount) as COLG_COUNT
FROM
    printvis.looselines_aisletime_open
        JOIN
    printvis.pm_voicetimes ON aisletime_picktype = voice_function
        AND aisletime_whse = voice_whse
        JOIN
    printvis.pickprediction_voicetpc ON voiceTPC_Whse = aisletime_whse
        AND voiceTPC_Hour = HOUR(NOW())
WHERE
    aisletime_whse = $whsesel
GROUP BY aisletime_whse , aisletime_wcs , aisletime_box)
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

    //write to aisle time historical file
    $sql_looselines_aisletimehist = $conn1->prepare("INSERT IGNORE INTO printvis.looselines_aisletime_open_hist
                                                                                                (SELECT * FROM printvis.looselines_aisletime_open where aisletime_whse = $whsesel)");
    $sql_looselines_aisletimehist->execute();
    //write to batch time historical file
    $sql_looselines_batchtimehist = $conn1->prepare("INSERT IGNORE INTO printvis.looselines_batchtime_open_hist
                                                                                                (SELECT * FROM printvis.looselines_batchtime_open where batchtime_whse = $whsesel)");
    $sql_looselines_batchtimehist->execute();
   
    
    
    
} //end of whsarray loop
