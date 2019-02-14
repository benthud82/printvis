<?php


$userset = $conn1->prepare("SELECT prodvisdb_users_ID from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$sessionuser'");
$userset->execute();
$usersetarray = $userset->fetchAll(pdo::FETCH_ASSOC);

