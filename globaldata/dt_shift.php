
<?php

include '../../CustomerAudit/connection/connection_details.php';
include '../sessioninclude.php';
include '../functions/functions_totetimes.php';
if (isset($_SESSION['MYUSER'])) {
    $var_userid = $_SESSION['MYUSER'];
    $whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
    $whssql->execute();
    $whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

    $var_whse = $whssqlarray[0]['prodvisdb_users_PRIMDC'];
}


$sel_position = ($_GET['sel_position']);
if ($sel_position == '*') {
    $positionsql = ' ';
} else {
    $positionsql = " and SHIFT_POSITION = '$sel_position'";
}

$sel_building = intval($_GET['sel_building']);
$sel_building = intval($_GET['sel_building']);

$shiftsql = $conn1->prepare("SELECT * FROM printvis.tsmshift WHERE SHIFT_BUILD = $sel_building $positionsql  and SHIFT_WHSE = $var_whse;");
$shiftsql->execute();
$shiftsql_array = $shiftsql->fetchAll(pdo::FETCH_ASSOC);




foreach ($shiftsql_array as $key => $value) {
    $SHIFT_TSMNUM = $shiftsql_array[$key]['SHIFT_TSMNUM'];
    $SHIFT_FIRSTNAME = $shiftsql_array[$key]['SHIFT_FIRSTNAME'];
    $SHIFT_LASTNAME = $shiftsql_array[$key]['SHIFT_LASTNAME'];
    $SHIFT_WHSE = $shiftsql_array[$key]['SHIFT_WHSE'];
    $SHIFT_BUILD = $shiftsql_array[$key]['SHIFT_BUILD'];
    $SHIFT_POSITION = $shiftsql_array[$key]['SHIFT_POSITION'];
    $SHIFT_STANDHOURS = $shiftsql_array[$key]['SHIFT_STANDHOURS'];
    $SHIFT_STARTTIME = $shiftsql_array[$key]['SHIFT_STARTTIME'];
    $SHIFT_ENDTIME = $shiftsql_array[$key]['SHIFT_ENDTIME'];
    $SHIFT_BREAK1 = $shiftsql_array[$key]['SHIFT_BREAK1'];
    $SHIFT_BREAK2 = $shiftsql_array[$key]['SHIFT_BREAK2'];
    $SHIFT_LUNCH = $shiftsql_array[$key]['SHIFT_LUNCH'];
    $SHIFT_OTHOURS = $shiftsql_array[$key]['SHIFT_OTHOURS'];
    $SHIFT_INCLUDEHOURS = intval($shiftsql_array[$key]['SHIFT_INCLUDEHOURS']);
    $SHIFT_CORL = $shiftsql_array[$key]['SHIFT_CORL'];
    if ($SHIFT_INCLUDEHOURS == 1) {
        $include = 'YES';
    } else {
        $include = 'NO';
    }


    $rowpush = array($SHIFT_TSMNUM, $SHIFT_FIRSTNAME, $SHIFT_LASTNAME, $SHIFT_WHSE, $SHIFT_BUILD, $SHIFT_POSITION, $SHIFT_STANDHOURS, $SHIFT_STARTTIME, $SHIFT_ENDTIME, $SHIFT_BREAK1, $SHIFT_BREAK2, $SHIFT_LUNCH, $SHIFT_OTHOURS, $include, $SHIFT_CORL);
    $row[] = array_values($rowpush);
}





$output = array(
    "aaData" => array()
);



$output['aaData'] = $row;
echo json_encode($output);
