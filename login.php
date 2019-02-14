<?php

// Check if username and password are correct
$sessionuser = $_POST["username"];
$sessionpw = $_POST["password"];
include_once '../connections/conn_printvis.php';
include '../globalincludes/usa_asys_session.php';  //does a-system PW work?
include 'globaldata/inusertable.php';  //has the user registered?

if (count($usersetarray) == 0) {  //the user is not logged in redirect to registration page
    header('Location: registration.php');
} elseif (isset($aseriesconn)) {

    // If correct, we set the session to YES
    session_start();
    $_SESSION["Login"] = "YES";
    $_SESSION['LAST_ACTIVITY'] = time();
    $_SESSION['MYUSER'] = $_POST["username"];
    $_SESSION['MYPASS'] = $_POST["password"];

    //write to MySQL Database that user logged in:
    include_once 'connection/connection_details.php';
    date_default_timezone_set('America/New_York');
    $datetime = date('Y-m-d H:i:s');
    $usertsm = $_SESSION['MYUSER'];
    $result1 = $conn1->prepare("INSERT INTO printvis.prodvisdb_login (idprodvisdb_login, prodvisdb_login_TSM, prodvisdb_login_datetime) values (0,'$usertsm','$datetime')");
    $result1->execute();

    header('Location: dashboard.php');
} else {

    // If not correct, we set the session to NO
    //session_start();
    $_SESSION["Login"] = "NO";
    echo "<h1>Something happened, try again.</h1>";
}


