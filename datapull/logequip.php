<?php

//pull in today's logs
$sql_logequip = $aseriesconn->prepare("SELECT eawhse as LOGEQUIP_WHSE, 
                                                            a.EALOG# as LOGEQUIP_LOG, 
                                                            count(*) as LOGEQUIP_TOTLINES,
                                                            SUM(CASE WHEN LMTIER in('L01', 'L02') then 1 else 0 end) as LOGEQUIP_FLOW,
                                                            SUM(CASE WHEN LMTIER in ('L04', 'L05') or substring(LOSIZE,1,1) = 'B' then 1 else 0 end) as LOGEQUIP_CART,
                                                            SUM(CASE WHEN LMTIER = 'L06' then 1 else 0 end) as LOGEQUIP_DOG,
                                                            SUM(CASE WHEN EATYPE = 'P' then 1 else 0 end) as LOGEQUIP_TUR,
                                                            SUM(CASE WHEN EATYPE <> 'P' and (LMTIER like 'C%' or substring(LOSIZE,1,1) in ('P','D','H'))then 1 else 0 end) as LOGEQUIP_ORDP,
                                                            SUM(PCEVOL * EATRNQ) / count(*) as LOGEQUIP_VOL
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
                                                            AND EALOG# <> 0
                                                            and EATRND >= $today_eraformat
                                                    GROUP BY EAWHSE, EALOG#");
$sql_logequip->execute();
$array_logequip = $sql_logequip->fetchAll(pdo::FETCH_ASSOC);

$data = array();
$logcolumns = 'logequip_whse,logequip_log,logequip_totlines,logequip_flow,logequip_cart,logequip_dog,logequip_tur,logequip_ordp,logequip_vol,logequip_equip';
foreach ($array_logequip as $key => $value) {

    $logequip_whse = $array_logequip[$key]['LOGEQUIP_WHSE'];
    $logequip_lognum = $array_logequip[$key]['LOGEQUIP_LOG'];
    $logequip_totlines = $array_logequip[$key]['LOGEQUIP_TOTLINES'];
    $logequip_putflow = $array_logequip[$key]['LOGEQUIP_FLOW'];
    $logequip_putcart = $array_logequip[$key]['LOGEQUIP_CART'];
    $logequip_putdogp = $array_logequip[$key]['LOGEQUIP_DOG'];
    $logequip_putturr = $array_logequip[$key]['LOGEQUIP_TUR'];
    $logequip_putordp = $array_logequip[$key]['LOGEQUIP_ORDP'];
    $logequip_recvol = $array_logequip[$key]['LOGEQUIP_VOL'];
    //call _logequip function
    $logequip_equip = _logequip($logequip_totlines, $logequip_putflow, $logequip_putcart, $logequip_putdogp, $logequip_putturr, $logequip_putordp, $logequip_recvol);
    $array_logequip[$key]['LOGEQUIP_EQUIP'] = $logequip_equip;
    
}


$mysqltable = 'log_equip';
$schema = 'printvis';
$arraychunk = 10000; //each result array will be split into 1000 line chunks to prevent memory over allocation
//insert into table
pdoMultiInsert($mysqltable, $schema, $array_logequip, $conn1, $arraychunk);
