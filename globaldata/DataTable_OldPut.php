<?php
include_once '../../connections/conn_printvis.php';
include '../sessioninclude.php';
$var_userid = $_SESSION['MYUSER'];
$whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);
$whsesel = $whssqlarray[0]['prodvisdb_users_PRIMDC'];

///  oldest putaway

$sql_datedput = $conn1->prepare("SELECT OPENPUTAWAY_TRANSDATE,
                                        OPENPUTAWAY_ITEM,
                                        OPENPUTAWAY_QUANTITY,
                                        OPENPUTAWAY_TRANS,
                                        OPENPUTAWAY_LOCATION,
                                        OPENPUTAWAY_LOG

                                        FROM printvis.openputaway 
                                    where openputaway_whse = $whsesel
                                    ORDER BY openputaway_transdate asc LIMIT 10");
$sql_datedput->execute();
$array_dateput = $sql_datedput->fetchAll(pdo::FETCH_ASSOC);


$output = array(
    "aaData" => array()
);
$row = array();

foreach ($array_dateput as $key => $value) {

    $datedtransdate = $array_dateput[$key]['OPENPUTAWAY_TRANSDATE'];
    $dateditem = $array_dateput[$key]['OPENPUTAWAY_ITEM'];
    $datedquantity = $array_dateput[$key]['OPENPUTAWAY_QUANTITY'];
    $datetrans = $array_dateput[$key]['OPENPUTAWAY_TRANS'];
    $datedlocation = $array_dateput[$key]['OPENPUTAWAY_LOCATION'];
    $datedlog = $array_dateput[$key]['OPENPUTAWAY_LOG'];
    
    $rowpush = array($datedtransdate, $dateditem, $datedquantity, $datetrans, $datedlocation, $datedlog);
    $row[] = array_values($rowpush);
}


$output['aaData'] = $row;
echo json_encode($output);







/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

