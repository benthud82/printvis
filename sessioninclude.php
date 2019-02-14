<?php

// Start up your PHP Session 
session_start();
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 30000)) {
    // last request was more than 30 minutes ago
    session_unset();     // unset $_SESSION variable for the run-time 
    session_destroy();   // destroy session data in storage
}

$_SESSION['LAST_ACTIVITY'] = time();


//         session_destroy();
// If the user is not logged in send him/her to the login form
if ($_SESSION["Login"] != "YES") {
    header("Location: signin.php");
}