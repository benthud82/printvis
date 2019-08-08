
<?php

include '../../connections/conn_printvis.php';

$tsmid = intval($_POST['tsmid']);
$active = intval($_POST['active']);


$sql = "UPDATE printvis.tsmshift 
            SET 
                SHIFT_INCLUDEHOURS = $active
            WHERE
                SHIFT_TSMNUM = $tsmid";
$query = $conn1->prepare($sql);
$query->execute();

