<?php

include '../../globalincludes/usa_asys.php';
include '../../connections/conn_printvis.php';
ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../functions/functions_totetimes.php';
$today_eraformat = intval(date('1ymd'));
$truncatetables = array('log_equipopen', 'log_equipopentimes', 'openmoves');
foreach ($truncatetables as $value) {
    $querydelete2 = $conn1->prepare("TRUNCATE printvis.$value");
    $querydelete2->execute();
}


//pull in all open putaway (logged and non logged)
$putawayopen = $aseriesconn->prepare("SELECT eawhse as LOGEQUIPOPEN_WHSE,
                                                            CASE WHEN EAWHSE = 3 AND EAUS08 IN ('NVSK005W', 'NVSK016W') THEN 2 ELSE 1 END AS LOGEQUIPOPEN_FROMBLDG,
                                                            CASE WHEN eaWHSE = 3 and EATLOC >= 'W400000' then 2 else 1 end as LOGEQUIPOPEN_TOBLDG,
                                                            CASE WHEN LMTIER in('L01', 'L02') then 'PUTFLW' 
                                                                 WHEN LMTIER in ('L04', 'L05') or substring(LOSIZE,1,1) = 'B' then 'PUTCRT'
                                                                 WHEN LMTIER = 'L06' then 'PUTDOG'
                                                                 WHEN EATYPE = 'P' then 'PUTTUR'
                                                                 WHEN EATYPE <> 'P' and (LMTIER like 'C%' or substring(LOSIZE,1,1) in ('P','D','H')) then 'PUTPKR' else 'PUTOPEN' END as LOGEQUIPOPEN_EQUIP,                                                           
                                                             count(*) as LOGEQUIPOPEN_TOTLINES
                                                            
                                                    FROM HSIPCORDTA.NPFCPC c, 
                                                    HSIPCORDTA.NPFLOC d, 
                                                    HSIPCORDTA.NPFERA a LEFT JOIN 
                                                    HSIPCORDTA.NPFLER E ON A.EATLOC = E.LELOC# AND A.EATRN# = E.LETRND 
                                                    inner join (SELECT EATRN#, max(EASEQ3) as max_seq 
                                                    FROM HSIPCORDTA.NPFERA GROUP BY EATRN#) b on b.EATRN# = a.EATRN# 
                                                    and a.EASEQ3 = max_seq   
                                                    JOIN HSIPCORDTA.NPFLSM on LMWHSE = EAWHSE and EATLOC = LMLOC# 
                                                    WHERE PCITEM = EAITEM and 
                                                            PCWHSE = 0 and 
                                                            LOWHSE = EAWHSE and 
                                                            LOLOC# = EATLOC 
                                                            and EASTAT <> 'C'
                                                            and EATRND >= 1200301
                                                    GROUP BY EAWHSE, CASE WHEN EAWHSE = 3 AND EAUS08 IN ('NVSK005W', 'NVSK016W') THEN 2 ELSE 1 END, CASE WHEN eaWHSE = 3 and EATLOC >= 'W400000' then 2 else 1 end, CASE WHEN LMTIER in('L01', 'L02') then 'PUTFLW'
                                                                 WHEN LMTIER in ('L04', 'L05') or substring(LOSIZE,1,1) = 'B' then 'PUTCRT'
                                                                 WHEN LMTIER = 'L06' then 'PUTDOG' 
                                                                 WHEN EATYPE = 'P' then 'PUTTUR' 
                                                                 WHEN EATYPE <> 'P' and (LMTIER like 'C%' or substring(LOSIZE,1,1) in ('P','D','H'))then 'PUTPKR' else 'PUTOPEN' END");

$putawayopen->execute();

$array_logequipopen = $putawayopen->fetchAll(pdo::FETCH_ASSOC);

$data = array();
$logcolumns = 'logequipopen_whse, logequipopen_frombldg, logequipopen_tobldg, logequipopen_equip, logequipopen_totlines';
foreach ($array_logequipopen as $key => $value) {

    $logequipopen_whse = $array_logequipopen[$key]['LOGEQUIPOPEN_WHSE'];
    $logequipopen_frombldg = $array_logequipopen[$key]['LOGEQUIPOPEN_FROMBLDG'];
    $logequipopen_tobldg = $array_logequipopen[$key]['LOGEQUIPOPEN_TOBLDG'];
    $logequipopen_equip = $array_logequipopen[$key]['LOGEQUIPOPEN_EQUIP'];
    $logequipopen_totlines = $array_logequipopen[$key]['LOGEQUIPOPEN_TOTLINES'];
}


$mysqltable = 'log_equipopen';
$schema = 'printvis';
$arraychunk = 10000; //each result array will be split into 1000 line chunks to prevent memory over allocation
//insert into table
pdoMultiInsert($mysqltable, $schema, $array_logequipopen, $conn1, $arraychunk);

$PUTOPENTIMES = $conn1->prepare("SELECT LOGEQUIPOPEN_WHSE AS LOGEQUIPOPENTIMES_WHSE,
                                       LOGEQUIPOPEN_FROMBLDG AS LOGEQUIPOPENTIMES_FROMBLDG,
                                       LOGEQUIPOPEN_TOBLDG AS LOGEQUIPOPENTIMES_TOBLDG,
                                       LOGEQUIPOPEN_EQUIP AS LOGEQUIPOPENTIMES_EQUIP,
                                       LOGEQUIPOPEN_TOTLINES AS LOGEQUIPOPENTIMES_TOTLINES,
                                       SUM(LOGEQUIPOPEN_TOTLINES * FORECAST_TIMEPERLINE) AS LOGEQUIPOPENTIMES_TOTTIME
                                       
                                       FROM
                                       printvis.LOG_EQUIPOPEN
                                       
                                       JOIN
                                       printvis.OPEN_FORECASTTIMES
                                       
                                       ON                         
                                       LOGEQUIPOPEN_WHSE = FORECAST_WHSE AND 
                                       LOGEQUIPOPEN_TOBLDG = FORECAST_BUILDING AND 
                                       LOGEQUIPOPEN_EQUIP = FORECAST_FUNCTION
                                       
                                                                         
                                       GROUP BY LOGEQUIPOPENTIMES_WHSE, LOGEQUIPOPENTIMES_FROMBLDG, LOGEQUIPOPENTIMES_TOBLDG, LOGEQUIPOPENTIMES_EQUIP");

$PUTOPENTIMES->execute();
$array_logequipopentimes = $PUTOPENTIMES->fetchAll(pdo::FETCH_ASSOC);

$data1 = array();
$logcolumns1 = 'logequipopentimes_whse,logequipopentimes_frombldg,logequipopentimes_tobldg,logequipopentimes_log, logequipopentimes_equip, logequipopentimes_totlines,LOGEQUIPOPENTIMES_TOTTIME';
foreach ($array_logequipopentimes as $key => $value) {

    $logequipopentimes_whse = $array_logequipopentimes[$key]['LOGEQUIPOPENTIMES_WHSE'];
    $logequipopentimes_frombldg = $array_logequipopentimes[$key]['LOGEQUIPOPENTIMES_FROMBLDG'];
    $logequipopentimes_tobldg = $array_logequipopentimes[$key]['LOGEQUIPOPENTIMES_TOBLDG'];
    $logequipopentimes_equip = $array_logequipopentimes[$key]['LOGEQUIPOPENTIMES_EQUIP'];
    $logequipopentimes_totlines = $array_logequipopentimes[$key]['LOGEQUIPOPENTIMES_TOTLINES'];
    $logequipopentimes_tottime = $array_logequipopentimes[$key]['LOGEQUIPOPENTIMES_TOTTIME'];
}

$mysqltable1 = 'log_equipopentimes';
$schema1 = 'printvis';
$arraychunk1 = 10000; //each result array will be split into 1000 line chunks to prevent memory over allocation
//insert into table
pdoMultiInsert($mysqltable1, $schema1, $array_logequipopentimes, $conn1, $arraychunk1);








//pull in today's predicted moves
{
    $predmovesopen = $aseriesconn->prepare("SELECT A.LOWHSE as FORECASTMOVE_WHSE,
                                        CASE WHEN LOWHSE = 3 and LOLOC# >= 'W400000' then 2 else 1 end as FORECASTMOVE_BUILDING, 
                                        CASE WHEN LMTIER IN ('L01', 'L15') then 'MVEL01'
                                             WHEN LMTIER IN ('L02', 'L03', 'L19') then 'MVEFLW'
                                             WHEN LMTIER IN ('L04', 'L05', 'L10') or substring(LOSIZE,1,1) = 'B' then 'MVEBIN'
                                             WHEN LMTIER = 'L06' then 'MVEDOG'
                                             WHEN LMTIER IN ('C01', 'C02', 'C03') then 'MVETUR'
                                             WHEN LMTIER IN ('C04', 'C05', 'C06') then 'MVEPKR' else 'MVEOPEN' END as FORECASTMOVE_EQUIP,
                                        count(*) as FORECASTMOVE_TOTLINES   
                                    FROM HSIPCORDTA.NPFLOC A 
                                    JOIN HSIPCORDTA.NPFLSM B ON LOWHSE = LMWHSE AND LOITEM = LMITEM AND LOLOC# = LMLOC#
                                    LEFT JOIN HSIPCORDTA.NPFWRS C ON A.LOWHSE = C.WRSWHS AND A.LOITEM = C.WRSITM  
                                            WHERE LOPRIM = 'P'
                                                                                       
                                            AND LOITEM >= '1000000'
                                               AND WRSROH > 0   
                                               AND ((LOONHD + LORCVQ) <= LOMINC) 
                                    GROUP BY LOWHSE, CASE WHEN LOWHSE = 3 and LOLOC# >= 'W400000' then 2 else 1 end,CASE WHEN LMTIER IN ('L01', 'L15') then 'MVEL01'  
                                    WHEN LMTIER IN ('L02', 'L03', 'L19') then 'MVEFLW'
                                    WHEN LMTIER IN ('L04', 'L05', 'L10') or substring(LOSIZE,1,1) = 'B' then 'MVEBIN'
                                    WHEN LMTIER = 'L06' then 'MVEDOG' 
                                    WHEN LMTIER IN ('C01', 'C02', 'C03') then 'MVETUR' 
                                    WHEN LMTIER IN ('C04', 'C05', 'C06') then 'MVEPKR' else 'MVEOPEN' END ");

    $predmovesopen->execute();
    $array_movesopen = $predmovesopen->fetchAll(pdo::FETCH_ASSOC);

    $data2 = array();
    $mvecolumns = 'forecastmove_whse,forecastmove_building, forecastmove_equip, forecastmove_totlines';
    foreach ($array_movesopen as $key => $value) {

        $forecastmove_whse = $array_movesopen[$key]['FORECASTMOVE_WHSE'];
        $forecastmove_building = $array_movesopen[$key]['FORECASTMOVE_BUILDING'];
        $forecastmove_equip = $array_movesopen[$key]['FORECASTMOVE_EQUIP'];
        $forecastmove_totlines = $array_movesopen[$key]['FORECASTMOVE_TOTLINES'];
    }
}


$mysqltable2 = 'forecastmoves';
$schema2 = 'printvis';
$arraychunk2 = 10000; //each result array will be split into 1000 line chunks to prevent memory over allocation
//insert into table
pdoMultiInsert($mysqltable2, $schema2, $array_movesopen, $conn1, $arraychunk2); {

    $PREDMOVEOPENTIMES = $conn1->prepare("SELECT FORECASTMOVE_WHSE AS forecastmovetimes_whse,
                                       FORECASTMOVE_BUILDING AS forecastmovetimes_building, 
                                       FORECASTMOVE_EQUIP AS forecastmovetimes_equip,
                                       FORECASTMOVE_TOTLINES AS forecastmovetimes_totlines,
                                       SUM(FORECASTMOVE_TOTLINES * FORECAST_TIMEPERLINE) AS forecastmovetimes_tottime
                                       
                                       FROM
                                       printvis.FORECASTMOVES
                                       
                                       JOIN
                                       printvis.OPEN_FORECASTTIMES
                                       
                                       ON                         
                                       FORECASTMOVE_WHSE = FORECAST_WHSE AND 
                                       FORECASTMOVE_BUILDING = FORECAST_BUILDING AND 
                                       FORECASTMOVE_EQUIP = FORECAST_FUNCTION
                                       
                                                                         
                                       GROUP BY FORECASTMOVETIMES_WHSE, FORECASTMOVETIMES_BUILDING, FORECASTMOVETIMES_EQUIP");

    $PREDMOVEOPENTIMES->execute();
    $array_MOVEOPENTIMES = $PREDMOVEOPENTIMES->fetchAll(pdo::FETCH_ASSOC);

    $data3 = array();
    $logcolumns3 = 'forecastmovetimes_whse,forecastmovetimes_building, forecastmovetimes_equip, forecastmovetimes_totlines,forecastmovetimes_tottime';
    foreach ($array_MOVEOPENTIMES as $key => $value) {

        $forecastmovetimes_whse_whse = $array_MOVEOPENTIMES[$key]['forecastmovetimes_whse'];
        $forecastmovetimes_building = $array_MOVEOPENTIMES[$key]['forecastmovetimes_building'];
        $forecastmovetimes_equip = $array_MOVEOPENTIMES[$key]['forecastmovetimes_equip'];
        $forecastmovetimes_totlines = $array_MOVEOPENTIMES[$key]['forecastmovetimes_totlines'];
        $forecastmovetimes_tottime = $array_MOVEOPENTIMES[$key]['forecastmovetimes_tottime'];
    }
}

$mysqltable3 = 'forecastmoves_opentimes';
$schema3 = 'printvis';
$arraychunk3 = 10000; //each result array will be split into 1000 line chunks to prevent memory over allocation
//insert into table
pdoMultiInsert($mysqltable3, $schema3, $array_MOVEOPENTIMES, $conn1, $arraychunk3);

//pull in today's open moves
$openmoves4 = $aseriesconn->prepare("SELECT MVWHSE AS OPENMOVES_WHSE,
                                            CASE WHEN MVTYPE = 'RS' THEN 'AUTO'
                                                 WHEN MVTYPE IN ('SK' , 'SJ') THEN 'ASO'
                                                 WHEN MVTYPE IN ('SO' , 'SP') THEN 'SPEC'
                                                 WHEN MVTYPE = 'CM' THEN 'CONSOL'
                                                 ELSE 'UNDEFINED' END AS OPENMOVES_TYPE,
                                             CASE WHEN MVWHSE = 3 and MVFLC# >= 'W400000' then 2 else 1 end as OPENMOVES_FROMBLDG,
                                             CASE WHEN MVWHSE = 3 and MVTLC# >= 'W400000' then 2 else 1 end as OPENMOVES_TOBLDG,
                                             CASE WHEN MVFZNE IN ('1', '2') AND MVTZNE IN ('1', '2') AND MVFLC# = MVPLC# THEN 'CRTCRT'
                                                  WHEN MVFZNE IN ('7', '8', '9' ) AND MVTZNE IN ('1', '2') AND LMTIER IN ('L02', 'L03', 'L19') AND MVFLC# = MVPLC# THEN 'PKRFLW'
                                                  WHEN MVFZNE IN ('7', '8', '9' ) AND MVTZNE IN ('1', '2') AND LMTIER IN ('L04') AND MVFLC# = MVPLC# THEN 'PKRBIN'
                                                  WHEN MVFZNE IN ('7', '8', '9' ) AND MVTZNE IN ('7', '8', '9') AND LMTIER IN ('C04', 'C06') AND MVFLC# = MVPLC# THEN 'PKRPKR'
                                                  WHEN MVFZNE IN ('7', '8', '9' ) AND MVTZNE IN ('7', '8', '9') AND LMTIER IN ('C01', 'C02', 'C03') AND MVFLC# = MVPLC# THEN 'TURTUR'
                                                  WHEN MVFZNE IN ('7', '8', '9' ) AND MVTZNE IN ('1', '2') AND LMTIER IN ('L01', 'L15') AND MVFLC# = MVPLC# THEN 'TURHJK'
                                                  WHEN MVFLC# <> MVPLC# AND LMTIER IN ('L04') THEN 'DRPCRT'
                                                  WHEN MVFLC# <> MVPLC# AND LMTIER IN ('L02', 'L03', 'L19') THEN 'DRPFLW'
                                                  WHEN MVFLC# <> MVPLC# AND LMTIER IN ('L01', 'L15') THEN 'DRPHJK'
                                                  WHEN MVFLC# <> MVPLC# AND LMTIER IN ('C04', 'C06') THEN 'DRPPKR'
                                                  WHEN MVFLC# <> MVPLC# AND LMTIER IN ('C01', 'C02', 'C03') THEN 'DRPTUR'
                                                  ELSE 'OPEN' END AS OPENMOVES_EQUIP,
                                            count(*) as OPENMOVES_TOTLINES      


                                    FROM HSIPCORDTA.NPFMVE
                                    JOIN HSIPCORDTA.NPFLSM ON LMWHSE = MVWHSE AND MVTLC# = LMLOC#
                                    WHERE MVSTAT <> 'C' 
                                                                                 
                                    group by mvwhse, CASE WHEN MVTYPE = 'RS' THEN 'AUTO'
                                                 WHEN MVTYPE IN ('SK' , 'SJ') THEN 'ASO'
                                                 WHEN MVTYPE IN ('SO' , 'SP') THEN 'SPEC'
                                                 WHEN MVTYPE = 'CM' THEN 'CONSOL'
                                                 ELSE 'UNDEFINED' END,                            
						CASE WHEN MVWHSE = 3 and MVFLC# >= 'W400000' then 2 else 1 end,
						CASE WHEN MVWHSE = 3 and MVTLC# >= 'W400000' then 2 else 1 end,
						CASE WHEN MVFZNE IN ('1', '2') AND MVTZNE IN ('1', '2') AND MVFLC# = MVPLC# THEN 'CRTCRT'
                                                  WHEN MVFZNE IN ('7', '8', '9' ) AND MVTZNE IN ('1', '2') AND LMTIER IN ('L02', 'L03', 'L19') AND MVFLC# = MVPLC# THEN 'PKRFLW'
                                                  WHEN MVFZNE IN ('7', '8', '9' ) AND MVTZNE IN ('1', '2') AND LMTIER IN ('L04') AND MVFLC# = MVPLC# THEN 'PKRBIN'
                                                  WHEN MVFZNE IN ('7', '8', '9' ) AND MVTZNE IN ('7', '8', '9') AND LMTIER IN ('C04', 'C06') AND MVFLC# = MVPLC# THEN 'PKRPKR'
                                                  WHEN MVFZNE IN ('7', '8', '9' ) AND MVTZNE IN ('7', '8', '9') AND LMTIER IN ('C01', 'C02', 'C03') AND MVFLC# = MVPLC# THEN 'TURTUR'
                                                  WHEN MVFZNE IN ('7', '8', '9' ) AND MVTZNE IN ('1', '2') AND LMTIER IN ('L01', 'L15') AND MVFLC# = MVPLC# THEN 'TURHJK'
                                                  WHEN MVFLC# <> MVPLC# AND LMTIER IN ('L04') THEN 'DRPCRT'
                                                  WHEN MVFLC# <> MVPLC# AND LMTIER IN ('L02', 'L03', 'L19') THEN 'DRPFLW'
                                                  WHEN MVFLC# <> MVPLC# AND LMTIER IN ('L01', 'L15') THEN 'DRPHJK'
                                                  WHEN MVFLC# <> MVPLC# AND LMTIER IN ('C04', 'C06') THEN 'DRPPKR'
                                                  WHEN MVFLC# <> MVPLC# AND LMTIER IN ('C01', 'C02', 'C03') THEN 'DRPTUR'
                                                  ELSE 'OPEN' END");

$openmoves4->execute();
$array_movesopen4 = $openmoves4->fetchAll(pdo::FETCH_ASSOC);

$data4 = array();
$opencolumns = 'OPENMOVES_WHSE, OPENMOVES_TYPE, OPENMOVES_FROMBLDG, OPENMOVES_TOBLDG, OPENMOVES_EQUIP, OPENMOVES_TOTLINES';
foreach ($array_movesopen4 as $key => $value) {

    $openmoves_whse = $array_movesopen4[$key]['OPENMOVES_WHSE'];
    $openmoves_type = $array_movesopen4[$key]['OPENMOVES_TYPE'];
    $openmoves_frombldg = $array_movesopen4[$key]['OPENMOVES_FROMBLDG'];
    $openmoves_tobldg = $array_movesopen4[$key]['OPENMOVES_TOBLDG'];
    $openmoves_equip = $array_movesopen4[$key]['OPENMOVES_EQUIP'];
    $openmoves_totlines = $array_movesopen4[$key]['OPENMOVES_TOTLINES'];
}


$mysqltable4 = 'openmoves';
$schema4 = 'printvis';
$arraychunk4 = 10000; //each result array will be split into 1000 line chunks to prevent memory over allocation
//insert into table
pdoMultiInsert($mysqltable4, $schema4, $array_movesopen4, $conn1, $arraychunk4);



//calculate times for open moves

$OPENMOVETIMES = $conn1->prepare("SELECT OPENMOVES_WHSE AS openmovetimes_whse,
                                       OPENMOVES_TYPE as openmovetimes_type, 
                                       OPENMOVES_FROMBLDG as openmovetimes_frombldg,
                                       OPENMOVES_TOBLDG AS openmovetimes_tobldg, 
                                       OPENMOVES_EQUIP AS openmovetimes_equip,
                                       OPENMOVES_TOTLINES AS openmovetimes_totlines,
                                       SUM(OPENMOVES_TOTLINES * FORECAST_TIMEPERLINE) AS openmovetimes_tottime
                                       
                                       FROM
                                       printvis.OPENMOVES
                                       
                                       JOIN
                                       printvis.OPEN_FORECASTTIMES
                                       
                                       ON                         
                                       OPENMOVES_WHSE = FORECAST_WHSE AND 
                                       OPENMOVES_TOBLDG = FORECAST_BUILDING AND 
                                       OPENMOVES_EQUIP = FORECAST_FUNCTION
                                       
                                                                         
                                       GROUP BY openmovetimes_whse, openmovetimes_type, openmovetimes_frombldg, openmovetimes_tobldg, openmovetimes_equip, openmovetimes_totlines");

$OPENMOVETIMES->execute();
$array_OPENMOVETIMES = $OPENMOVETIMES->fetchAll(pdo::FETCH_ASSOC);

$data10 = array();
$logcolumns10 = 'openmovetimes_whse, openmovetimes_type, openmovetimes_frombldg, openmovetimes_tobldg, openmovetimes_equip,openmovetimes_totlines, openmovetimes_tottime ';
foreach ($array_OPENMOVETIMES as $key => $value) {

    $openmovetimes_whse = $array_OPENMOVETIMES[$key]['openmovetimes_whse'];
    $openmovetimes_type = $array_OPENMOVETIMES[$key]['openmovetimes_type'];
    $openmovetimes_frombldg = $array_OPENMOVETIMES[$key]['openmovetimes_frombldg'];
    $openmovetimes_tobldg = $array_OPENMOVETIMES[$key]['openmovetimes_tobldg'];
    $openmovetimes_equip = $array_OPENMOVETIMES[$key]['openmovetimes_equip'];
    $openmovetimes = $array_OPENMOVETIMES[$key]['openmovetimes_totlines'];
    $openmovetimes_tottime = $array_OPENMOVETIMES[$key]['openmovetimes_tottime'];
}


$mysqltable10 = 'OPENMOVES_TIMES';
$schema10 = 'printvis';
$arraychunk10 = 10000; //each result array will be split into 1000 line chunks to prevent memory over allocation
//insert into table
pdoMultiInsert($mysqltable10, $schema10, $array_OPENMOVETIMES, $conn1, $arraychunk10);




/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
