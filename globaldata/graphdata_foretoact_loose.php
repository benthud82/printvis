
<?php

include '../sessioninclude.php';
include '../../connections/conn_printvis.php';
include '../functions/functions_totetimes.php';

$var_userid = $_SESSION['MYUSER'];
$whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);
$whsesel = $whssqlarray[0]['prodvisdb_users_PRIMDC'];

$building = 1;

session_write_close();
$today = date('Y-m-d');
$result1 = $conn1->prepare("SELECT 
                                                            fcast_hour, fcast_equip, fcast_lines, loosevol_lines
                                                        FROM
                                                            printvis.loosevol_forecast
                                                                LEFT JOIN
                                                            printvis.today_loosevol_summary ON loosevol_whse = fcast_whse
                                                                AND loosevol_availdate = fcast_date
                                                                AND loosevol_equip = fcast_equip
                                                                AND loosevol_availhour = fcast_hour
                                                        WHERE
                                                            fcast_whse = $whsesel
                                                                AND fcast_date = CURDATE()
                                                                AND fcast_equip in ('BLUEBIN', 'FLOWRACK', 'FULLPALLET')
                                                        ORDER BY fcast_equip , fcast_hour");
$result1->execute();


$result3 = $conn1->prepare("SELECT 
                                                            a.loosevol_availhour,
                                                            (SELECT 
                                                                    SUM(z.loosevol_lines)
                                                                FROM
                                                                    printvis.today_loosevol_summary z
                                                                WHERE
                                                                    z.loosevol_whse = $whsesel
                                                                        AND z.loosevol_availhour <= a.loosevol_availhour
                                                                        AND z.loosevol_equip in ('BLUEBIN', 'FLOWRACK', 'FULLPALLET')
                                                                        AND z.loosevol_availdate = CURDATE()) AS lines_actual
                                                        FROM
                                                            printvis.today_loosevol_summary a
                                                        WHERE
                                                            loosevol_whse = $whsesel
                                                                AND loosevol_availdate = CURDATE()
                                                                AND loosevol_equip in ('BLUEBIN', 'FLOWRACK', 'FULLPALLET')
                                                        GROUP BY loosevol_availhour
                                                        ORDER BY loosevol_availhour ASC");
$result3->execute();

//cummulative lines by hour/equipment
$result4 = $conn1->prepare("SELECT 
                                                            a.loosevol_availhour,
                                                            a.loosevol_equip,
                                                            (SELECT 
                                                                    SUM(z.loosevol_lines)
                                                                FROM
                                                                    printvis.today_loosevol_summary z
                                                                WHERE
                                                                    z.loosevol_whse = $whsesel
                                                                        AND z.loosevol_availhour <= a.loosevol_availhour
                                                                        AND a.loosevol_equip = z.loosevol_equip
                                                                        AND z.loosevol_equip in ('BLUEBIN', 'FLOWRACK', 'FULLPALLET')
                                                                        AND z.loosevol_availdate = CURDATE()) AS lines_actual
                                                        FROM
                                                            printvis.today_loosevol_summary a
                                                        WHERE
                                                            loosevol_whse = $whsesel
                                                                AND loosevol_availdate = CURDATE()
                                                                AND loosevol_equip in ('BLUEBIN', 'FLOWRACK', 'FULLPALLET')
                                                        GROUP BY a.loosevol_availhour , a.loosevol_equip
                                                        ORDER BY a.loosevol_availhour ASC");
$result4->execute();


$result2 = $conn1->prepare("SELECT 
                                                        t.fcast_hour,
                                                 (SELECT 
                                                                SUM(x.fcast_lines)
                                                            FROM
                                                                printvis.loosevol_forecast x
                                                            WHERE
                                                                x.fcast_hour <= t.fcast_hour
                                                                    AND x.fcast_whse = $whsesel
                                                                     AND x.fcast_equip in ('BLUEBIN', 'FLOWRACK', 'FULLPALLET')
                                                                    AND x.fcast_date = CURDATE()) AS cumulative_sum
                                                                    
                                                    FROM
                                                        printvis.loosevol_forecast t
                                                            LEFT JOIN
                                                        printvis.today_loosevol_summary ON loosevol_whse = fcast_whse
                                                            AND loosevol_availdate = fcast_date
                                                            AND loosevol_equip = fcast_equip
                                                            AND loosevol_availhour = fcast_hour
                                                    WHERE
                                                        fcast_whse = $whsesel
                                                            AND fcast_date = CURDATE()
                                                            AND fcast_equip in ('BLUEBIN', 'FLOWRACK', 'FULLPALLET')
                                                    GROUP BY fcast_hour
                                                    ORDER BY fcast_hour ASC");
$result2->execute();




$rows = array();
$rows['name'] = 'Hour';
$rows5 = array();
$rows5['name'] = 'Forecast - Blue Bin';
$rows6 = array();
$rows6['name'] = 'Forecast - Flow Rack';
$rows7 = array();
$rows7['name'] = 'Forecast - Full Pallet';
$rows8 = array();
$rows8['name'] = 'Forecast - Total';
$rows1 = array();
$rows1['name'] = 'Actual - Blue Bin';
$rows2 = array();
$rows2['name'] = 'Actual - Flow Rack';
$rows3 = array();
$rows3['name'] = 'Actual - Full Pallet';
$rows4 = array();
$rows4['name'] = 'Actual - Total';


$bluebinruntot = 0;
$palletruntot = 0;
$flowrackruntot = 0;
$bluebinruntot_act = 0;
$palletruntot_act = 0;
$flowrackruntot_act = 0;
$totruntot = 0;
foreach ($result2 as $row) {
    $rows['data'][] = ($row['fcast_hour']) * 1;
//    $totruntot += $row['cumulative_sum'] * 1;
//    $rows4['data'][] = ($totruntot) * 1;
    $rows8['data'][] = $row['cumulative_sum'] * 1;

}

foreach ($result3 as $row) {

    $rows4['data'][] = $row['lines_actual'] * 1;
}



foreach ($result1 as $row) {
    $equipment = $row['fcast_equip'];
    switch ($equipment) {
        case 'BLUEBIN':
            $bluebinruntot += $row['fcast_lines'] * 1;
          
            $rows5['data'][] = ($bluebinruntot) * 1;
         
            break;
        case 'FLOWRACK':
            $flowrackruntot += $row['fcast_lines'] * 1;
 
            $rows6['data'][] = ($flowrackruntot) * 1;

            break;
        case 'FULLPALLET':
            $palletruntot += $row['fcast_lines'] * 1;

            $rows7['data'][] = ($palletruntot) * 1;
            break;

        default:
            break;
    }
}

foreach ($result4 as $row) {
    $equipment = $row['loosevol_equip'];
    switch ($equipment) {
        case 'BLUEBIN':
            $rows1['data'][] = $row['lines_actual'] * 1;
            break;
        case 'FLOWRACK':
            $rows2['data'][] = $row['lines_actual'] * 1;
            break;
        case 'FULLPALLET':
            $rows3['data'][] = $row['lines_actual'] * 1;
                       break;

        default:
            break;
    }
}




$result = array();
array_push($result, $rows);
array_push($result, $rows1);
array_push($result, $rows2);
array_push($result, $rows3);
array_push($result, $rows4);
array_push($result, $rows5);
array_push($result, $rows6);
array_push($result, $rows7);
array_push($result, $rows8);



print json_encode($result);
