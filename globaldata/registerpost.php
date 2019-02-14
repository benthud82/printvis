<?php
//add the user to the slottingdb_users table
include '../../connections/conn_printvis.php';

$userid = $_POST["username"];
$userfirst = $_POST["firstname"];
$userlast = $_POST["lastname"];
$userDC = intval($_POST["whsesel"]);

$result1 = $conn1->prepare("INSERT INTO printvis.prodvisdb_users (prodvisdb_users_ID, prodvisdb_users_FIRSTNAME, prodvisdb_users_LASTNAME, prodvisdb_users_PRIMDC) values ('$userid','$userfirst','$userlast', $userDC)");
$result1->execute();

header('Location: ../signin.php');