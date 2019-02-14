<?php

include '../../connections/conn_printvis.php';
$var_deletearray = ($_POST['deletearray']);
$today = date('Y-m-d');
foreach ($var_deletearray as $key => $value) {
    $intbatch = intval($var_deletearray[$key][0]);
    $sql = "INSERT INTO printvis.casebatchdelete (casedelete_batch, casedelete_table, casedelete_date) VALUES ($intbatch, 'LOOSE','$today');";
    $query = $conn1->prepare($sql);
    $query->execute();
}




