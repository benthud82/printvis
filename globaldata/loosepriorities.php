
<?php
//pull in packing carts with batches that have been opened and totes remaining
include_once '../sessioninclude.php';
include_once '../../connections/conn_printvis.php';
include_once '../functions/functions_totetimes.php';

$var_userid = $_SESSION['MYUSER'];
$whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);
$whsesel = $whssqlarray[0]['prodvisdb_users_PRIMDC'];
include '../timezoneset.php';

$currenttime = date('H:i');
$buffermins = 90;  //change this back to 30
$displayarray = array();

$today = date('Y-m-d');
$dayofweek = date('w', strtotime($today));
if ($dayofweek == 1) {
    $yesterday = date('Y-m-d', strtotime("-3 days"));
} else {
    $yesterday = date('Y-m-d', strtotime("-1 day"));
}

$yesterdaytime = ('17:20:59');
$todaydatetime = date('Y-m-d H:i:s');
$printcutoff = date('Y-m-d H:i:s', strtotime("$yesterday $yesterdaytime"));

$prioritysql = $conn1->prepare("SELECT 
                                                                batchtime_cart,
                                                                min(cutoff_zone) as cutoff_zone,
                                                                @REM_PICKTIME:=CASE
                                                                    WHEN boxrel_relcount > 0 THEN 0
                                                                    WHEN voice_userid = 0 THEN batchtime_time_totaltime
                                                                    ELSE (SELECT 
                                                                            cartpick_remaintime
                                                                        FROM
                                                                            printvis.looselines_cartsinprocess
                                                                        WHERE
                                                                            batchtime_cart = cartpick_cart)
                                                                END AS REM_PICKTIME,
                                                                @REM_PACKTIME:=(SELECT DISTINCT
                                                                    SUM(CASE
                                                                            WHEN tote_end_endtime IS NULL THEN totetimes_totalPFD
                                                                            ELSE 0
                                                                        END)
                                                                FROM
                                                                    printvis.totetimes A
                                                                        LEFT JOIN
                                                                    printvis.tote_end ON tote_end_whse = totetimes_whse
                                                                        AND totetimes_cart = tote_end_batch
                                                                        AND tote_end_tote = totetimes_bin
                                                                        JOIN
                                                                    printvis.pm_packtimes ON loosepm_function = totetimes_packfunction
                                                                        AND totetimes_whse = loosepm_whse
                                                                        LEFT JOIN
                                                                    printvis.printcutoff B ON SUBSTR(A.totetimes_shipzone, 1, 2) = B.cutoff_zone
                                                                        AND A.totetimes_whse = B.cutoff_DC
                                                                WHERE
                                                                    A.totetimes_whse = $whsesel
                                                                        AND A.totetimes_cart = batchtime_cart
                                                                        AND B.cutoff_time = (SELECT 
                                                                            MIN(Z.cutoff_time)
                                                                        FROM
                                                                            printvis.totetimes Y
                                                                                LEFT JOIN
                                                                            printvis.printcutoff Z ON SUBSTR(Y.totetimes_shipzone, 1, 2) = Z.cutoff_zone
                                                                                AND Y.totetimes_whse = Z.cutoff_DC
                                                                        WHERE
                                                                            Y.totetimes_whse = $whsesel
                                                                                AND A.totetimes_cart = Y.totetimes_cart
                                                                        GROUP BY Y.totetimes_cart)
                                                                GROUP BY totetimes_cart) AS REM_PACKTIME,
                                                                boxrel_relcount / boxrel_boxcount AS PERCMAN,
                                                                cutoff_truck AS TRUCK_PULL_TIME,
                                                                @PRIORITY_COUNT:=(SELECT DISTINCT
                                                                        COUNT(B.cutoff_time)
                                                                    FROM
                                                                        printvis.totetimes A
                                                                            LEFT JOIN
                                                                        printvis.printcutoff B ON SUBSTR(A.totetimes_shipzone, 1, 2) = B.cutoff_zone
                                                                            AND A.totetimes_whse = B.cutoff_DC
                                                                    WHERE
                                                                        A.totetimes_whse = $whsesel
                                                                            AND A.totetimes_cart = batchtime_cart
                                                                            AND B.cutoff_time = (SELECT 
                                                                                MIN(Z.cutoff_time)
                                                                            FROM
                                                                                printvis.totetimes Y
                                                                                    LEFT JOIN
                                                                                printvis.printcutoff Z ON SUBSTR(Y.totetimes_shipzone, 1, 2) = Z.cutoff_zone
                                                                                    AND Y.totetimes_whse = Z.cutoff_DC
                                                                            WHERE
                                                                                Y.totetimes_whse = $whsesel
                                                                                    AND A.totetimes_cart = Y.totetimes_cart
                                                                            GROUP BY Y.totetimes_cart)
                                                                    GROUP BY totetimes_cart) AS PRIORITY_COUNT,
                                                                        CASE
                                                                                WHEN @REM_PICKTIME IS NULL THEN 0
                                                                                ELSE @REM_PICKTIME
                                                                            END + CASE
                                                                                WHEN @REM_PACKTIME IS NULL THEN 0
                                                                                ELSE @REM_PACKTIME
                                                                        END AS TOTAL_TIME
                                                            FROM
                                                                printvis.looselines_batchtime
                                                                    LEFT JOIN
                                                                printvis.case_boxesreleased ON boxrel_batch = batchtime_cart
                                                                    LEFT JOIN
                                                                printvis.printcutoff ON cutoff_DC = batchtime_whse
                                                                    AND cutoff_rank = batchtime_shipzone
                                                                    LEFT JOIN
                                                                printvis.voice_batchespicked ON voice_batch = batchtime_cart
                                                                LEFT JOIN
                                                                printvis.casebatchdelete ON casedelete_batch = batchtime_cart
                                                            WHERE
                                                                batchtime_whse = $whsesel
                                                                    AND batchtime_printdatetime >= '$printcutoff'
                                                                    AND batchtime_count_ice / batchtime_count_line < .5
                                                                    AND batchtime_colgcount / batchtime_count_line < .5
                                                                    AND boxrel_relcount / boxrel_boxcount < 1
                                                                    AND casedelete_batch IS NULL
                                                                    and DATE(voice_startdatetime)  > (NOW() - INTERVAL 7 DAY)
                                                                       GROUP BY batchtime_cart");
$prioritysql->execute();
$priorityarray = $prioritysql->fetchAll(pdo::FETCH_ASSOC);

foreach ($priorityarray as $key => $value) {
    //adjust for time zone differences
    $TOTAL_TIME = intval($priorityarray[$key]['TOTAL_TIME']);
    if ($TOTAL_TIME == 0) {
        //Do not display if total time remainins is 0
        continue;
    }
    $TRUCK_PULL_TIME = date('H:i', strtotime($priorityarray[$key]['TRUCK_PULL_TIME']));
    $completetime = date('H:i', time() + ($TOTAL_TIME * 60));
    $completetime_buffer = date('H:i', strtotime($completetime) + ($buffermins * 60));
    //will batch be completed before truck pull time minus buffer time
    if ($completetime_buffer >= $TRUCK_PULL_TIME) {
        //push data to display array so sorting can occur
        $displayarray[] = array("batch" => $priorityarray[$key]['batchtime_cart'],
            "zone" => $priorityarray[$key]['cutoff_zone'],
            "pri_count" => $priorityarray[$key]['PRIORITY_COUNT'],
            "time_pick" => intval($priorityarray[$key]['REM_PICKTIME']),
            "time_pack" => intval($priorityarray[$key]['REM_PACKTIME']),
            "time_truck" => $TRUCK_PULL_TIME,
            "time_complete" => $completetime,
            "time_buffer" => intval((strtotime($TRUCK_PULL_TIME) - strtotime($completetime)) / 60));
    }
}

//sort by time_complete
array_multisort(array_column($displayarray, "time_buffer"), SORT_ASC, $displayarray);
?>

<div id="container_deletebtn">
    <button id="btn_delete_batch" class="btn btn-danger">Delete Selected Batches</button>
</div>


<!--start of div table-->
<div class="" id="divtable_priorities" style="padding-bottom: 51px">
    <div  class='col-sm-12 col-md-12 col-lg-12 print-1wide'  style="float: none;">


        <div class='widget-content widget-table'  style="position: relative;">
            <div class='divtable'>
                <div id="sticky-anchor"></div>
                <div style="padding-top: 51px;"></div>
                <div id="sticky" class='divtableheader' style="padding-top">
                    <div class='divtabletitle width5' style="cursor: default">Delete?</div>
                    <div class='divtabletitle width8_33' style="cursor: default">Batch</div>
                    <div class='divtabletitle width8_33' style="cursor: default">Highest Priority</div>
                    <div class='divtabletitle width8_33' style="cursor: default">Priority Count</div>
                    <div class='divtabletitle width8_33' style="cursor: default">Pick Minutes Rem.</div>
                    <div class='divtabletitle width8_33' style="cursor: default">Pack Minutes Rem.</div>
                    <div class='divtabletitle width8_33' style="cursor: default">Truck Pull Time</div>
                    <div class='divtabletitle width8_33' style="cursor: default">Est. Completion Time</div>

                </div>
                <?php foreach ($displayarray as $key => $value) { ?>
                    <div id="<?php echo $displayarray[$key]['batch']; ?>"class='divtablerow itemdetailexpand' style="cursor: pointer">
                        <div class='divtabledata width5' style="vertical-align: text-top; cursor: pointer"> <input type="checkbox" class="chkbox_deletebatch noclick" name="checkbox" id="<?php echo $displayarray[$key]['batch']; ?>"  /></div>
                        <div class='divtabledata width8_33' ><?php echo $displayarray[$key]['batch']; ?></div>
                        <div class='divtabledata width8_33' ><?php echo $displayarray[$key]['zone']; ?></div>
                        <div class='divtabledata width8_33' ><?php echo $displayarray[$key]['pri_count']; ?></div>
                        <div class='divtabledata width8_33' ><?php echo $displayarray[$key]['time_pick']; ?></div>
                        <div class='divtabledata width8_33' ><?php echo $displayarray[$key]['time_pack']; ?></div>
                        <div class='divtabledata width8_33' ><?php echo $displayarray[$key]['time_truck']; ?></div>
                        <div class='divtabledata width8_33' ><?php echo $displayarray[$key]['time_complete']; ?></div>

                    </div>
                <?php } ?>

            </div>
        </div>





    </div>
</div>    

