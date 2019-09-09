<?php

//Update Ship Zone Badges
$update_shipzonebadge = $conn1->prepare("UPDATE printvis.badges A 
SET 
    A.shipzones = (SELECT DISTINCT
            COUNT(DISTINCT SUBSTRING(P.SHIP_ZONE, 1, 2)) AS badgecount
        FROM
            printvis.voicepicks P
                LEFT JOIN
            printvis.printcutoff S ON S.cutoff_DC = P.Whse
                AND SUBSTRING(P.SHIP_ZONE, 1, 2) = SUBSTRING(S.cutoff_zone, 1, 2)
        WHERE
            S.cutoff_zone IS NULL
                AND P.Whse = A.Whse
        GROUP BY P.Whse)");
$update_shipzonebadge->execute();
