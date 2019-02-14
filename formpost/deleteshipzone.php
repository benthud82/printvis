
<?php

include_once '../../connections/conn_printvis.php';
include_once '../sessioninclude.php';
$var_userid = strtoupper($_SESSION['MYUSER']);
$whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE UPPER(prodvisdb_users_ID = '$var_userid')");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

$var_whse = $whssqlarray[0]['prodvisdb_users_PRIMDC'];

$delete_shipzonerank = intval($_POST['currank']);

//delete ship zone
$sql = "DELETE FROM printvis.printcutoff WHERE cutoff_DC = $var_whse and cutoff_rank = $delete_shipzonerank";
$query = $conn1->prepare($sql);
$query->execute();

//re-rank shipzone with a greater rank than current ship zone
$sql_3 = "UPDATE printvis.printcutoff
              SET cutoff_rank = (cutoff_rank - 1)
              WHERE cutoff_rank > $delete_shipzonerank
             AND cutoff_DC = $var_whse";
$query_3 = $conn1->prepare($sql_3);
$query_3->execute();
