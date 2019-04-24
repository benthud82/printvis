<?php

include '../../globalincludes/usa_asys.php';
include '../../connections/conn_printvis.php';
ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
//put in connection includes (as400 printvis)


$result1 = $aseriesconn->prepare("SELECT eawhse, a.EAITEM, a.EATRN#, a.EATRNQ, a.EATLOC, a.EALOG#, a.EATRND, a.EACMPT, a.EASEQ3, a.EASTAT, a.EATYPE, c.PCCPKU, c.PCIPKU, d.LOPKGU, d.LOPRIM, CASE WHEN c.PCCPKU > 0 then int(a.EATRNQ /  c.PCCPKU) else 0 end as CASEHANDLE,  CASE WHEN c.PCCPKU > 0 then mod(a.EATRNQ ,  c.PCCPKU) else a.EATRNQ end as EACHHANDLE, EAEQPT FROM HSIPCORDTA.NPFCPC c, HSIPCORDTA.NPFLOC d, HSIPCORDTA.NPFERA a LEFT JOIN HSIPCORDTA.NPFLER E ON A.EATLOC = E.LELOC# AND A.EATRN# = E.LETRND inner join (SELECT EATRN#, max(EASEQ3) as max_seq FROM HSIPCORDTA.NPFERA GROUP BY EATRN#) b on b.EATRN# = a.EATRN# and a.EASEQ3 = max_seq and EASTAT = 'C' AND EALOG# <> 0 and EATRND = 1190409 WHERE PCITEM = EAITEM and PCWHSE = 0 and LOWHSE = EAWHSE and LOLOC# = EATLOC");
$result1->execute();
$mindaysarray = $result1->fetchAll(pdo::FETCH_ASSOC);

//create table on local
$columns = 'completedputaway_whse,completedputaway_item, completedputaway_trans, completedputaway_status, completedputaway_quantity, completedputaway_location, completedputaway_log, completedputaway_transdate, completedputaway_comptime, completedputaway_seq, completedputaway_type, completedputaway_casehandle, completedputaway_eachhandle, completedputaway_equiptype';

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
        $completedputaway_whse = $mindaysarray[$counter]['EAWHSE'];
        $completedputaway__item = $mindaysarray[$counter]['EAITEM'];
        $completedputaway_trans = $mindaysarray[$counter]['EATRN#'];
        $completedputaway_status = $mindaysarray[$counter]['EASTAT'];
        $completedputaway_quantity = $mindaysarray[$counter]['EATRNQ'];
        $completedputaway_location = $mindaysarray[$counter]['EATLOC'];
        $completedputaway_log = $mindaysarray[$counter]['EALOG#'];
        $completedputaway_transdate = $mindaysarray[$counter]['EATRND'];
        $completedputaway_comptime = $mindaysarray[$counter]['EACMPT'];
        $completedputaway_seq = $mindaysarray[$counter]['EASEQ3'];
        $completedputaway_type = $mindaysarray[$counter]['EATYPE'];
        $completedputaway_casehandle = $mindaysarray[$counter]['CASEHANDLE'];
        $completedputaway_eachhandle = $mindaysarray[$counter]['EACHHANDLE'];
        $completedputaway_equiptype = $mindaysarray[$counter]['EAEQPT'];
        //STOPKEEP
        

        $data[] = "($completedputaway_whse, $completedputaway__item, $completedputaway_trans, '$completedputaway_status', $completedputaway_quantity,'$completedputaway_location',$completedputaway_log, $completedputaway_transdate,$completedputaway_comptime, $completedputaway_seq, '$completedputaway_type', $completedputaway_casehandle, $completedputaway_eachhandle, '$completedputaway_equiptype')";
        $counter += 1;
    }


    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO printvis.completedputaway ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange += 4000;
} while ($counter <= $rowcount);


