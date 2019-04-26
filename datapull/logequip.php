<?php

//pull in today's logs
$sql_logequip = $aseriesconn->prepare("SELECT eawhse as WHSE, 
                                                            a.EALOG# as LOGNUM, 
                                                            count(*) as TOT_LINES,
                                                            SUM(CASE WHEN LMTIER in('L01', 'L02') then 1 else 0 end) as PUT_FLOW,
                                                            SUM(CASE WHEN LMTIER in ('L04', 'L05') or substring(LOSIZE,1,1) = 'B' then 1 else 0 end) as PUT_CART,
                                                            SUM(CASE WHEN LMTIER = 'L06' then 1 else 0 end) as PUT_DOGP,
                                                            SUM(CASE WHEN EATYPE = 'P' then 1 else 0 end) as PUT_TURR,
                                                            SUM(CASE WHEN EATYPE <> 'P' and (LMTIER like 'C%' or substring(LOSIZE,1,1) in ('P','D','H'))then 1 else 0 end) as PUT_ORDP,
                                                            SUM(PCEVOL * EATRNQ) / count(*) as AVG_REC_VOL
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

    $logequip_whse = $array_logequip[$key]['WHSE'];
    $logequip_lognum = $array_logequip[$key]['LOGNUM'];
    $logequip_totlines = $array_logequip[$key]['TOT_LINES'];
    $logequip_putflow = $array_logequip[$key]['PUT_FLOW'];
    $logequip_putcart = $array_logequip[$key]['PUT_CART'];
    $logequip_putdogp = $array_logequip[$key]['PUT_DOGP'];
    $logequip_putturr = $array_logequip[$key]['PUT_TURR'];
    $logequip_putordp = $array_logequip[$key]['PUT_ORDP'];
    $logequip_recvol = $array_logequip[$key]['AVG_REC_VOL'];
    //call _logequip function
    $logequip_equip = _logequip($logequip_totlines, $logequip_putflow, $logequip_putcart, $logequip_putdogp, $logequip_putturr, $logequip_putordp, $logequip_recvol);
    $data[] = "($logequip_whse, $logequip_lognum, $logequip_totlines, $logequip_putflow, $logequip_putcart, $logequip_putdogp, $logequip_putturr, $logequip_putordp, $logequip_recvol, '$logequip_equip')";
}
$values = implode(',', $data);

if (!empty($values)) {
    $sql = "INSERT  INTO printvis.log_equip ($logcolumns) VALUES $values 
            ON DUPLICATE KEY UPDATE 
            logequip_totlines=VALUES(logequip_totlines),
            logequip_flow=VALUES(logequip_flow),
            logequip_cart=VALUES(logequip_cart),
            logequip_dog=VALUES(logequip_dog),
            logequip_tur=VALUES(logequip_tur),
            logequip_ordp=VALUES(logequip_ordp),
            logequip_vol=VALUES(logequip_vol),
            logequip_equip=VALUES(logequip_equip)
             ";
    $query = $conn1->prepare($sql);
    $query->execute();
}

