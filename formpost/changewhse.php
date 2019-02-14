
<?php

 include_once '../../connections/conn_printvis.php';


$var_newwhse = ($_POST['newwhse']);
$var_userid = ($_POST['userid']);

$sql = "UPDATE printvis.prodvisdb_users SET prodvisdb_users_PRIMDC = $var_newwhse WHERE prodvisdb_users_ID = '$var_userid';";
$query = $conn1->prepare($sql);
$query->execute();

