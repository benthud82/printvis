<?php

include '../../connections/conn_printvis.php';
$var_deletearray = ($_POST['deletearray']);
$today = date('Y-m-d');
foreach ($var_deletearray as $key => $value) {
    $intbatch = floatval($var_deletearray[$key][0]);
    $sql = "INSERT INTO printvis.packbatchdelete (idpackbatchdelete, packbatchdelete_date) VALUES ($intbatch, '$today');";
    $query = $conn1->prepare($sql);
    $query->execute();
}




