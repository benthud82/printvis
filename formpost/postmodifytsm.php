
<?php

include '../../connections/conn_printvis.php';


$modifytsm_tsmid = intval($_POST['modifytsm_tsmid']);
$modifytsm_firstname = ($_POST['modifytsm_firstname']);
$modifytsm_lastname = ($_POST['modifytsm_lastname']);
$modifytsm_whse = intval($_POST['modifytsm_whse']);
$modifytsm_building = intval($_POST['modifytsm_building']);
$modifytsm_position = ($_POST['modifytsm_position']);
$modifytsm_stdhours = ($_POST['modifytsm_stdhours']);
$modifytsm_starttime = ($_POST['modifytsm_starttime']);
$modifytsm_endtime = ($_POST['modifytsm_endtime']);
$modifytsm_breaktime1 = ($_POST['modifytsm_breaktime1']);
$modifytsm_breaktime2 = ($_POST['modifytsm_breaktime2']);
$modifytsm_lunchtime = ($_POST['modifytsm_lunchtime']);
$modifytsm_othours = ($_POST['modifytsm_othours']);
$modifytsm_includehours = intval($_POST['modifytsm_includehours']);
$modifytsm_dept = ($_POST['modifytsm_dept']);




$columns = 'SHIFT_TSMNUM,SHIFT_FIRSTNAME,SHIFT_LASTNAME,SHIFT_WHSE,SHIFT_BUILD,SHIFT_POSITION,SHIFT_STANDHOURS,SHIFT_STARTTIME  ,SHIFT_ENDTIME  ,SHIFT_BREAK1  ,SHIFT_BREAK2  ,SHIFT_LUNCH,SHIFT_OTHOURS,SHIFT_INCLUDEHOURS,SHIFT_CORL';
$values = "$modifytsm_tsmid, '$modifytsm_firstname', '$modifytsm_lastname', $modifytsm_whse, $modifytsm_building, '$modifytsm_position', '$modifytsm_stdhours', '$modifytsm_starttime', '$modifytsm_endtime', '$modifytsm_breaktime1', '$modifytsm_breaktime2', '$modifytsm_lunchtime', '$modifytsm_othours', $modifytsm_includehours, '$modifytsm_dept' ";


$sql = "DELETE from printvis.tsmshift WHERE SHIFT_TSMNUM = $modifytsm_tsmid";
$query = $conn1->prepare($sql);
$query->execute();

$sql = "INSERT INTO printvis.tsmshift ($columns) VALUES ($values) ";
$query = $conn1->prepare($sql);
$query->execute();

