
<?php

include '../sessioninclude.php';
include_once '../../connections/conn_printvis.php';


$var_userid = $_SESSION['MYUSER'];
$whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);
$whsesel = $whssqlarray[0]['prodvisdb_users_PRIMDC'];


$desiredrank = intval($_POST['desired_position']) + 1; //add 1 for 0 based indexing
$currentrank = intval($_POST['current_position']) + 1; //add 1 for 0 based indexing

if ($desiredrank > $currentrank) {
    $move = 'down';
} else {
    $move = 'up';
}



// Set the display_order for the dragged item to be 0 so we can update this record later by display_order = 0
$sql_1 = "UPDATE printvis.printcutoff
                            SET cutoff_rank = 0
                            WHERE cutoff_rank = $currentrank
                  AND cutoff_DC = $whsesel";
$query_1 = $conn1->prepare($sql_1);
$query_1->execute();

// Move down: Update the items between the current position and the desired position, decreasing each item by 1 to make space for the new item
if ($move == 'down') {
    $sql_2 = "UPDATE printvis.printcutoff
              SET cutoff_rank = (cutoff_rank - 1)
              WHERE cutoff_rank > $currentrank
              AND cutoff_rank <= $desiredrank
              AND cutoff_DC = $whsesel";
    $query_2 = $conn1->prepare($sql_2);
    $query_2->execute();
}


// Move up: Update the items between the desired position and the current position, increasing each item by 1 to make space for the new item
if ($move == 'up') {
    $sql_3 = "UPDATE printvis.printcutoff
              SET cutoff_rank = (cutoff_rank + 1)
              WHERE cutoff_rank >= $desiredrank
              AND cutoff_rank < $currentrank
             AND cutoff_DC = $whsesel";
    $query_3 = $conn1->prepare($sql_3);
    $query_3->execute();
}

// Update the item that was dragged and set it to be the desired position now that the slot is opend up
$sql_4 = "UPDATE printvis.printcutoff
          SET cutoff_rank = $desiredrank
          WHERE cutoff_rank = 0
AND cutoff_DC = $whsesel";
$query_4 = $conn1->prepare($sql_4);
$query_4->execute();

