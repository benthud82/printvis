<?php

//include '../../globalincludes/usa_asys.php';
//include '../../connections/conn_printvis.php';
//ini_set('max_execution_time', 99999);
//ini_set('memory_limit', '-1');
//include '../functions/functions_totetimes.php';
$today_eraformat = intval(date('1ymd'));


//pull in today's logs
$putawayopen = $aseriesconn->prepare("SELECT eawhse as LOGEQUIPOPEN_WHSE,
                                                            CASE WHEN eaWHSE = 3 and EATLOC >= 'W400000' then 2 else 1 end as LOGEQUIPOPEN_BUILDING,
                                                            a.EALOG# as LOGEQUIPOPEN_LOG,
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
                                                            AND EALOG# = 0
                                                            and EATRND >= $today_eraformat
                                                    GROUP BY EAWHSE, CASE WHEN EAWHSE = 3 and EATLOC >= 'W400000' then 2 else 1 end, A.EALOG#, CASE WHEN LMTIER in('L01', 'L02') then 'PUTFLW'
                                                                 WHEN LMTIER in ('L04', 'L05') or substring(LOSIZE,1,1) = 'B' then 'PUTCRT'
                                                                 WHEN LMTIER = 'L06' then 'PUTDOG' 
                                                                 WHEN EATYPE = 'P' then 'PUTTUR' 
                                                                 WHEN EATYPE <> 'P' and (LMTIER like 'C%' or substring(LOSIZE,1,1) in ('P','D','H'))then 'PUTPKR' else 'PUTOPEN' END");

$putawayopen->execute();
$array_logequipopen = $putawayopen->fetchAll(pdo::FETCH_ASSOC);

$data = array();
$logcolumns = 'logequipopen_whse,logequipopen_building,logequipopen_log, logequipopen_equip, logequipopen_totlines';
foreach ($array_logequipopen as $key => $value) {

    $logequipopen_whse = $array_logequipopen[$key]['LOGEQUIPOPEN_WHSE'];
    $logequipopen_building = $array_logequipopen[$key]['LOGEQUIPOPEN_BUILDING'];
    $logequipopen_log = $array_logequipopen[$key]['LOGEQUIPOPEN_LOG'];
    $logequipopen_equip = $array_logequipopen[$key]['LOGEQUIPOPEN_EQUIP'];
    $logequipopen_totlines = $array_logequipopen[$key]['LOGEQUIPOPEN_TOTLINES'];
    
    
}


$mysqltable = 'log_equipopen';
$schema = 'printvis';
$arraychunk = 10000; //each result array will be split into 1000 line chunks to prevent memory over allocation
//insert into table
pdoMultiInsert($mysqltable, $schema, $array_logequipopen, $conn1, $arraychunk);



{

$PUTOPENTIMES = $conn1->prepare("SELECT LOGEQUIPOPEN_WHSE AS LOGEQUIPOPENTIMES_WHSE,
                                       LOGEQUIPOPEN_BUILDING AS LOGEQUIPOPENTIMES_BUILDING, 
                                       LOGEQUIPOPEN_LOG AS LOGEQUIPOPENTIMES_LOG,
                                       LOGEQUIPOPEN_EQUIP AS LOGEQUIPOPENTIMES_EQUIP,
                                       LOGEQUIPOPEN_TOTLINES AS LOGEQUIPOPENTIMES_TOTLINES,
                                       SUM(LOGEQUIPOPEN_TOTLINES * FORECAST_TIMEPERLINE) AS LOGEQUIPOPENTIMES_TOTTIME
                                       
                                       FROM
                                       printvis.LOG_EQUIPOPEN
                                       
                                       JOIN
                                       printvis.OPEN_FORECASTTIMES
                                       
                                       ON                         
                                       LOGEQUIPOPEN_WHSE = FORECAST_WHSE AND 
                                       LOGEQUIPOPEN_BUILDING = FORECAST_BUILDING AND 
                                       LOGEQUIPOPEN_EQUIP = FORECAST_FUNCTION
                                       
                                                                         
                                       GROUP BY LOGEQUIPOPENTIMES_WHSE, LOGEQUIPOPENTIMES_BUILDING, LOGEQUIPOPENTIMES_LOG, LOGEQUIPOPENTIMES_EQUIP");
                                       
  $PUTOPENTIMES->execute();  
  $array_logequipopentimes = $PUTOPENTIMES->fetchAll(pdo::FETCH_ASSOC);

$data1 = array();
$logcolumns1 = 'logequipopentimes_whse,logequipopentimes_building,logequipopentimes_log, logequipopentimes_equip, logequipopentimes_totlines,LOGEQUIPOPENTIMES_TOTTIME';
foreach ($array_logequipopentimes as $key => $value) {

    $logequipopentimes_whse = $array_logequipopentimes[$key]['LOGEQUIPOPENTIMES_WHSE'];
    $logequipopentimes_building = $array_logequipopentimes[$key]['LOGEQUIPOPENTIMES_BUILDING'];
    $logequipopentimes_log = $array_logequipopentimes[$key]['LOGEQUIPOPENTIMES_LOG'];
    $logequipopentimes_equip = $array_logequipopentimes[$key]['LOGEQUIPOPENTIMES_EQUIP'];
    $logequipopentimes_totlines = $array_logequipopentimes[$key]['LOGEQUIPOPENTIMES_TOTLINES'];
    $logequipopentimes_tottime = $array_logequipopentimes[$key]['LOGEQUIPOPENTIMES_TOTTIME'];
    
    
}
}

$mysqltable1 = 'log_equipopentimes';
$schema1 = 'printvis';
$arraychunk1 = 10000; //each result array will be split into 1000 line chunks to prevent memory over allocation
//insert into table
pdoMultiInsert($mysqltable1, $schema, $array_logequipopentimes, $conn1, $arraychunk);








//pull in today's moves

{
$movesopen = $aseriesconn->prepare("SELECT A.LOWHSE as FORECASTMOVE_WHSE,
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

$movesopen->execute();
$array_movesopen = $movesopen->fetchAll(pdo::FETCH_ASSOC);

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
pdoMultiInsert($mysqltable2, $schema2, $array_movesopen, $conn1, $arraychunk2);




{

$MOVEOPENTIMES = $conn1->prepare("SELECT FORECASTMOVE_WHSE AS forecastmovetimes_whse,
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
                                       
  $MOVEOPENTIMES->execute();  
  $array_MOVEOPENTIMES = $MOVEOPENTIMES->fetchAll(pdo::FETCH_ASSOC);

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

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
