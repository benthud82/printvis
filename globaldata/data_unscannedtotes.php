<?php
include_once '../../connections/conn_printvis.php';
include '../sessioninclude.php';
include '../functions/functions_totetimes.php';
if (isset($_SESSION['MYUSER'])) {
    $var_userid = $_SESSION['MYUSER'];
    $whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
    $whssql->execute();
    $whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

    $var_whse = $whssqlarray[0]['prodvisdb_users_PRIMDC'];

    $startdatesel = date("Y-m-d", strtotime($_POST['startdatesel']));  //pulled from heatmap.php
    $enddatesel = date("Y-m-d", strtotime($_POST['enddatesel']));  //pulled from heatmap.php

    if ($var_whse == 3) {
        $building = 2;
    } else {
        $building = 1;
    }
} else {
    $whsearray = array(7);
}


if (isset($_POST['sort_class'])) {
    $asc_desc = $_POST['sort_class'];
} else {
    $asc_desc = ' asc';
}



if (isset($_POST['orderby'])) {
    $orderbyvalue = $_POST['orderby'];
} else {
    $orderbyvalue = 'batch';
}

switch ($orderbyvalue) {
    case 'batch':
        $orderbysql = " ORDER BY totetimes_cart $asc_desc ";
        break;
    case 'tsm':
        $orderbysql = " ORDER BY cartstart_tsm $asc_desc ";
        break;
    case 'start':
        $orderbysql = " ORDER BY cartstart_starttime $asc_desc ";
        break;
    case 'station':
        $orderbysql = " ORDER BY cartstart_packstation $asc_desc ";
        break;
    case 'unscannedtime':
        $orderbysql = " ORDER BY SUM(CASE
                                                                    WHEN allscan_tsm > 0 THEN 0
                                                                    ELSE totetimes_totalPFD
                                                                END) $asc_desc ";
        break;
    case 'scanned':
        $orderbysql = " ORDER BY  SUM(CASE
                                                                    WHEN allscan_tsm > 0 THEN 1
                                                                    ELSE 0
                                                                END)  $asc_desc ";
        break;
    case 'notscanned':
        $orderbysql = " ORDER BY SUM(CASE
                                                                    WHEN allscan_tsm > 0 THEN 0
                                                                    ELSE 1
                                                                END) $asc_desc ";
        break;
    case 'percent':
        $orderbysql = " ORDER BY SUM(CASE
                                                                    WHEN allscan_tsm > 0 THEN 1
                                                                    ELSE 0
                                                                END) / COUNT(*) $asc_desc ";
        break;

    default:
        $orderbysql = " ORDER BY totetimes_cart $asc_desc ";
        break;
}

$unscannedsql = $conn1->prepare("SELECT DISTINCT
                                                                totetimes_cart,
                                                                cartstart_tsm,
                                                                cartstart_starttime,
                                                                cartstart_packstation,
                                                                SUM(CASE
                                                                    WHEN allscan_tsm > 0 THEN 1
                                                                    ELSE 0
                                                                END) AS TOTE_SCANNED,
                                                                SUM(CASE
                                                                    WHEN allscan_tsm > 0 THEN 0
                                                                    ELSE 1
                                                                END) AS TOTE_NOTSCANNED,
                                                                SUM(CASE
                                                                    WHEN allscan_tsm > 0 THEN 1
                                                                    ELSE 0
                                                                END) / COUNT(*) AS PERC_SCANNED,
                                                                SUM(CASE
                                                                    WHEN allscan_tsm > 0 THEN 0
                                                                    ELSE totetimes_totalPFD
                                                                END) AS USCANNED_TIME
                                                            FROM
                                                                printvis.alltote_history
                                                                    LEFT JOIN
                                                                printvis.scannedtote_history ON totelp = allscan_lp
                                                                    LEFT JOIN
                                                                printvis.allcart_history_hist A ON cartstart_whse = totetimes_whse
                                                                    AND totetimes_cart = cartstart_batch and date(cartstart_starttime) = date(totetimes_dateadded)
                                                            WHERE
                                                                DATE(cartstart_starttime) >= '$startdatesel' and 
                                                                DATE(cartstart_starttime) <= '$enddatesel' 
                                                                    AND cartstart_whse = $var_whse
                                                                    AND A.cartstart_starttime IN (SELECT 
                                                                        MAX(B.cartstart_starttime)
                                                                    FROM
                                                                        printvis.allcart_history B
                                                                    WHERE
                                                                        B.cartstart_batch = A.cartstart_batch)
                                                            GROUP BY totetimes_cart , cartstart_tsm , cartstart_starttime , cartstart_packstation
                                                            HAVING PERC_SCANNED < 1"
        . " $orderbysql  ");
$unscannedsql->execute();
$unscannedsql_array = $unscannedsql->fetchAll(pdo::FETCH_ASSOC);



$unscannedheader = $conn1->prepare("SELECT DISTINCT
                                                                            SUM(CASE
                                                                                WHEN allscan_tsm > 0 THEN 0
                                                                                ELSE 1
                                                                            END) AS TOTE_NOTSCANNED,
                                                                            SUM(CASE
                                                                                WHEN allscan_tsm > 0 THEN 1
                                                                                ELSE 0
                                                                            END) / COUNT(*) AS PERC_SCANNED,
                                                                            SUM(CASE
                                                                                WHEN allscan_tsm > 0 THEN 0
                                                                                ELSE totetimes_totalPFD
                                                                            END) AS USCANNED_TIME
                                                                        FROM
                                                                            printvis.alltote_history
                                                                                LEFT JOIN
                                                                            printvis.scannedtote_history ON totelp = allscan_lp
                                                                                LEFT JOIN
                                                                            printvis.allcart_history A ON cartstart_whse = totetimes_whse
                                                                                AND totetimes_cart = cartstart_batch and date(cartstart_starttime) = date(totetimes_dateadded)
                                                                        WHERE
                                                                            DATE(cartstart_starttime) >= '$startdatesel' and 
                                                                DATE(cartstart_starttime) <= '$enddatesel' 
                                                                                AND cartstart_whse = $var_whse
                                                                                AND A.cartstart_starttime IN (SELECT 
                                                                                    MAX(B.cartstart_starttime)
                                                                                FROM
                                                                                    printvis.allcart_history B
                                                                                WHERE
                                                                                    B.cartstart_batch = A.cartstart_batch)
                                                                        HAVING PERC_SCANNED < 1 ");
$unscannedheader->execute();
$unscannedheader_array = $unscannedheader->fetchAll(pdo::FETCH_ASSOC);
?>





<!--Header Stats-->
<div class="col-lg-12">
    <div class="row" style="padding-top: 25px">
        <div class="col-lg-4 " id="stat_losttime">
            <div class="dashboard-stat dashboard-stat-v2 red-intense">  
                <div class="visual">
                    <i class="fa fa-clock-o"></i>
                </div>
                <div class="details">
                    <div class="number">
                        <span data-counter="counterup" data-value="1349"><?php echo _convertToHoursMins($unscannedheader_array[0]['USCANNED_TIME']) ?></span>
                    </div>
                    <div class="desc"> Total Hours Unscanned </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4" id="stat_notscanned">
            <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
                <div class="visual">
                    <i class="fa fa-times"></i>
                </div>
                <div class="details">
                    <div class="number">
                        <span data-counter="counterup" data-value="1349"><?php echo ($unscannedheader_array[0]['TOTE_NOTSCANNED']) ?></span>
                    </div>
                    <div class="desc"> Total Totes not Scanned </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 " id="stat_percentscanned">
            <div class="dashboard-stat dashboard-stat-v2 blue-hoki">  
                <div class="visual">
                    <i class="fa fa-percent"></i>
                </div>
                <div class="details">
                    <div class="number">
                        <span data-counter="counterup" data-value="1349"><?php echo number_format(($unscannedheader_array[0]['PERC_SCANNED'] * 100), 2) . '%'; ?></span>
                    </div>
                    <div class="desc"> Percent Scanned</div>
                </div>
            </div>
        </div>
    </div>
</div>



<!--start of div table-->
<div class="" id="divtable_notprinted" style="padding-bottom: 51px">
    <div  class='col-sm-12 col-md-12 col-lg-12 print-1wide'  style="float: none;">
        <div class='widget-content widget-table'  style="position: relative;">
            <div class='divtable'>
                <div id="sticky-anchor"></div>
                <div style="padding-top: 51px;"></div>
                <div id="sticky" class='divtableheader' style="padding-top">
                    <div class='divtabletitle width12_5 click_sort  ' data-pull="unscanned" name="batch" data-sort="<?php echo $asc_desc ?>">Batch</div>
                    <div class='divtabletitle width12_5 click_sort ' data-pull="unscanned" name="tsm" data-sort="<?php echo $asc_desc ?>">TSM</div>
                    <div class='divtabletitle width12_5 click_sort ' data-pull="unscanned" name="start" data-sort="<?php echo $asc_desc ?>">Start Time</div>
                    <div class='divtabletitle width12_5 click_sort ' data-pull="unscanned" name="station" data-sort="<?php echo $asc_desc ?>">Pack Station</div>
                    <div class='divtabletitle width12_5 click_sort ' data-pull="unscanned" name="scanned" data-sort="<?php echo $asc_desc ?>" >Totes Scanned</div>
                    <div class='divtabletitle width12_5 click_sort ' data-pull="unscanned" name="notscanned" data-sort="<?php echo $asc_desc ?>" >Totes Not Scanned</div>
                    <div class='divtabletitle width12_5 click_sort ' data-pull="unscanned" name="percent" data-sort="<?php echo $asc_desc ?>" >Percent Scanned</div>
                    <div class='divtabletitle width12_5 click_sort ' data-pull="unscanned" name="unscannedtime" data-sort="<?php echo $asc_desc ?>" >Unscanned Time</div>
                </div>
                <?php
                foreach ($unscannedsql_array as $key => $value) {
                    ?>
                    <div id="<?php echo $unscannedsql_array[$key]['totetimes_cart']; ?>"class='divtablerow itemdetailexpand greyhover batchclick' data-date="<?php echo date('Y-m-d', strtotime($unscannedsql_array[$key]['cartstart_starttime'])); ?>">
                        <div id=""class='divtabledata width12_5 '><?php echo $unscannedsql_array[$key]['totetimes_cart']; ?></div>
                        <div class='divtabledata width12_5' ><?php echo $unscannedsql_array[$key]['cartstart_tsm']; ?></div>
                        <div class='divtabledata width12_5' ><?php echo $unscannedsql_array[$key]['cartstart_starttime']; ?></div>
                        <div class='divtabledata width12_5' ><?php echo $unscannedsql_array[$key]['cartstart_packstation']; ?></div>
                        <div class='divtabledata width12_5' ><?php echo $unscannedsql_array[$key]['TOTE_SCANNED']; ?></div>
                        <div class='divtabledata width12_5' ><?php echo $unscannedsql_array[$key]['TOTE_NOTSCANNED']; ?></div>
                        <div class='divtabledata width12_5' ><?php echo number_format(($unscannedsql_array[$key]['PERC_SCANNED'] * 100), 2) . '%'; ?></div>
                        <div class='divtabledata width12_5' ><?php echo _convertToHoursMins($unscannedsql_array[$key]['USCANNED_TIME']); ?></div>

                    </div>
                <?php } ?>
            </div>
        </div>
    </div>    
</div>    
