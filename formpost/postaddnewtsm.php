
<?php

include '../../connections/conn_printvis.php';


$addtsm_tsmid = intval($_POST['addtsm_tsmid']);
$addtsm_firstname = ($_POST['addtsm_firstname']);
$addtsm_lastname = ($_POST['addtsm_lastname']);
$addtsm_whse = intval($_POST['addtsm_whse']);
$addtsm_building = intval($_POST['addtsm_building']);
$addtsm_position = ($_POST['addtsm_position']);
$addtsm_stdhours = ($_POST['addtsm_stdhours']);
$addtsm_starttime = ($_POST['addtsm_starttime']);
$addtsm_endtime = ($_POST['addtsm_endtime']);
$addtsm_breaktime1 = ($_POST['addtsm_breaktime1']);
$addtsm_breaktime2 = ($_POST['addtsm_breaktime2']);
$addtsm_lunchtime = ($_POST['addtsm_lunchtime']);
$addtsm_othours = ($_POST['addtsm_othours']);
$addtsm_includehours = intval($_POST['addtsm_includehours']);
$addtsm_dept = ($_POST['addtsm_dept']);




$columns = 'SHIFT_TSMNUM,SHIFT_FIRSTNAME,SHIFT_LASTNAME,SHIFT_WHSE,SHIFT_BUILD,SHIFT_POSITION,SHIFT_STANDHOURS,SHIFT_STARTTIME  ,SHIFT_ENDTIME  ,SHIFT_BREAK1  ,SHIFT_BREAK2  ,SHIFT_LUNCH,SHIFT_OTHOURS,SHIFT_INCLUDEHOURS,SHIFT_CORL';
$values = "$addtsm_tsmid, '$addtsm_firstname', '$addtsm_lastname', $addtsm_whse, $addtsm_building, '$addtsm_position', '$addtsm_stdhours', '$addtsm_starttime', '$addtsm_endtime', '$addtsm_breaktime1', '$addtsm_breaktime2', '$addtsm_lunchtime', '$addtsm_othours', '$addtsm_includehours', '$addtsm_dept' ";


$sql = "INSERT IGNORE INTO printvis.tsmshift ($columns) VALUES ($values) ";
$query = $conn1->prepare($sql);
$query->execute();

