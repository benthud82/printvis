
<?php

include '../sessioninclude.php';
include '../../connections/conn_printvis.php';
include '../functions/functions_totetimes.php';

$var_userid = $_SESSION['MYUSER'];
$whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);
$whsesel = $whssqlarray[0]['prodvisdb_users_PRIMDC'];
if(isset($_GET['building'])){
    $building = intval($_GET['building']);
} else{
    echo '';
}
session_write_close();
$today = date('Y-m-d');
$result1 = $conn1->prepare("SELECT 
                                                    fcase_hour,
                                                    fcase_equipment,
                                                    fcase_lines,
                                                    caseactbyhour_lines
                                                FROM
                                                    printvis.forecast_case
                                                        LEFT JOIN
                                                    printvis.case_actuallaborbyhour ON fcase_hour = caseactbyhour_hour
                                                        AND caseactbyhour_whse = fcase_whse
                                                        AND caseactbyhour_build = fcase_build
                                                        AND caseactbyhour_date = fcase_date
                                                        AND caseactbyhour_equip = fcase_equipment
                                                WHERE
                                                    fcase_date = '$today'
                                                        AND fcase_whse = $whsesel
                                                        AND fcase_build = $building;");
$result1->execute();


$result2 = $conn1->prepare("SELECT 
                                                    t.fcase_hour,
                                                    SUM(caseactbyhour_lines) AS ACTMINTOT,
                                                    (SELECT 
                                                            SUM(x.fcase_lines)
                                                        FROM
                                                            printvis.forecast_case x
                                                        WHERE
                                                            x.fcase_hour <= t.fcase_hour
                                                                AND x.fcase_whse = $whsesel
                                                                AND x.fcase_build = $building
                                                                AND x.fcase_date = '$today') AS cumulative_sum
                                                FROM
                                                    printvis.forecast_case t
                                                        LEFT JOIN
                                                    printvis.case_actuallaborbyhour ON fcase_hour = caseactbyhour_hour
                                                        AND caseactbyhour_whse = fcase_whse
                                                        AND caseactbyhour_build = fcase_build
                                                        AND caseactbyhour_date = fcase_date
                                                        AND caseactbyhour_equip = fcase_equipment
                                                WHERE
                                                    fcase_whse = $whsesel AND fcase_build = $building
                                                        AND fcase_date = '$today'
                                                GROUP BY fcase_hour
                                                ORDER BY fcase_hour ASC;");
$result2->execute();




$rows = array();
$rows['name'] = 'Hour';
$rows5 = array();
$rows5['name'] = 'Forecast - Beltline';
$rows6 = array();
$rows6['name'] = 'Forecast - Pallet Jack';
$rows7 = array();
$rows7['name'] = 'Forecast - Order Picker';
$rows8 = array();
$rows8['name'] = 'Forecast - Total';
$rows1 = array();
$rows1['name'] = 'Actual - Beltline';
$rows2 = array();
$rows2['name'] = 'Actual - Pallet Jack';
$rows3 = array();
$rows3['name'] = 'Actual - Order Picker';
$rows4 = array();
$rows4['name'] = 'Actual - Total';


$beltruntot = 0;
$palletruntot = 0;
$opruntot = 0;
$totruntot = 0;
foreach ($result2 as $row) {
    $rows['data'][] = ($row['fcase_hour']) * 1;
//    $totruntot += $row['cumulative_sum'] * 1;
//    $rows4['data'][] = ($totruntot) * 1;
    $rows8['data'][] = $row['cumulative_sum'] * 1;
    $rows4['data'][] = $row['ACTMINTOT'] * 1;
}



foreach ($result1 as $row) {
    $equipment = $row['fcase_equipment'];
    switch ($equipment) {
        case 'BELTLINE':
            $beltruntot += $row['fcase_lines'] * 1;
            $rows5['data'][] = ($beltruntot) * 1;
            $rows1['data'][] = $row['caseactbyhour_lines'] * 1;
            break;
        case 'PALLETJACK':
            $palletruntot += $row['fcase_lines'] * 1;
            $rows6['data'][] = ($palletruntot) * 1;
            $rows2['data'][] = $row['caseactbyhour_lines'] * 1;
            break;
        case 'ORDERPICKER':
            $opruntot += $row['fcase_lines'] * 1;
            $rows3['data'][] = $row['caseactbyhour_lines'] * 1;
            $rows7['data'][] = ($opruntot) * 1;
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
