
<?php

include '../../connections/conn_printvis.php';

$delete_tsmid = intval($_POST['delete_tsmid']);


$sql = "DELETE FROM printvis.tsmshift WHERE SHIFT_TSMNUM = $delete_tsmid";
$query = $conn1->prepare($sql);
$query->execute();

