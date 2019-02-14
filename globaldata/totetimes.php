
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





if (isset($_POST['sort_class'])) {
    $asc_desc = $_POST['sort_class'];
} else {
    $asc_desc = ' asc';
}

$orderbyvalue = $_POST['orderby'];
switch ($orderbyvalue) {
    case 'batch':
        $orderbysql = " ORDER BY totetimes_cart  $asc_desc";
        break;
    case 'start':
        $orderbysql = " ORDER BY batch_start_time  $asc_desc";
        break;
    case 'tsm':
        $orderbysql = " ORDER BY batch_start_TSM  $asc_desc";
        break;
    case 'packstation':
        $orderbysql = " ORDER BY batch_start_packstation $asc_desc";
        break;
    case 'speed':
        $orderbysql = " ORDER BY batch_start_speedpack  $asc_desc";
        break;
    case 'standardtime':
        $orderbysql = " ORDER BY    TIMESTAMPDIFF(MINUTE,
                                                                            batch_start_time,
                                                                            NOW()),
                                                                        SUM(totetimes_totalPFD) $asc_desc";
        break;
    case 'projtime':
        $orderbysql = " ORDER BY SUM(CASE
                                                                            WHEN tote_end_endtime IS NULL THEN totetimes_totalPFD
                                                                            ELSE 0
                                                                        END) $asc_desc";
        break;
    case 'ahead_behind':
        $orderbysql = " ORDER BY SUM(totetimes_totalPFD) - TIMESTAMPDIFF(MINUTE,
                                                                            batch_start_time,
                                                                            NOW()) + SUM(CASE
                                                                            WHEN tote_end_endtime IS NULL THEN totetimes_totalPFD
                                                                            ELSE 0
                                                                        END) + $mintosubtract  $asc_desc";
        break;
    case 'timeremain':
        $orderbysql = " ORDER BY SUM(CASE
                                                                            WHEN tote_end_endtime IS NULL THEN totetimes_totalPFD
                                                                            ELSE 0
                                                                        END)  $asc_desc";
        break;
    case 'perccomplete':
        $orderbysql = " ORDER BY   SUM(CASE
                                                                            WHEN tote_end_endtime > 0 THEN 1
                                                                            ELSE 0
                                                                        END) / COUNT(*)   $asc_desc";
        break;
    case 'status':
        $orderbysql = " ORDER BY (TIMESTAMPDIFF(MINUTE,
                                                                            batch_start_time,
                                                                            NOW()) -
                                                                        SUM(totetimes_totalPFD)  -  TIMESTAMPDIFF(MINUTE,
                                                                            batch_start_time,
                                                                            NOW()) + SUM(CASE
                                                                            WHEN tote_end_endtime IS NULL THEN totetimes_totalPFD
                                                                            ELSE 0
                                                                        END)) $asc_desc";
        break;
    default:
        $orderbysql = " ORDER BY  $asc_desc";
        break;
}









$openpack = $conn1->prepare("SELECT 
                                                                totetimes_cart AS BATCH,
                                                                batch_start_time AS BATCH_START,
                                                                batch_start_packstation AS PACK_STATION,
                                                                batch_start_TSM AS TSM,
                                                                batch_start_speedpack AS SPEED,
                                                                TIMESTAMPDIFF(MINUTE,
                                                                    batch_start_time,
                                                                    NOW()) AS MINUTES_ELAPSED,
                                                                SUM(totetimes_totalPFD) + loosepm_cartprep + loosepm_cartcomplete AS CART_TIME,
                                                                SUM(CASE
                                                                    WHEN tote_end_endtime IS NULL THEN totetimes_totalPFD
                                                                    ELSE 0
                                                                END) + loosepm_cartcomplete AS CART_TIME_REMAINING,
                                                                TIMESTAMPDIFF(MINUTE,
                                                                    batch_start_time,
                                                                    NOW()) + SUM(CASE
                                                                    WHEN tote_end_endtime IS NULL THEN totetimes_totalPFD
                                                                    ELSE 0
                                                                END) + loosepm_cartcomplete AS PROJ_COMPLETE_MIN,
                                                                SUM(totetimes_linecount) AS LINE_COUNT,
                                                                SUM(totetimes_unitcount) AS UNIT_COUNT,
                                                                COUNT(*) AS TOTE_COUNT,
                                                                SUM(CASE
                                                                    WHEN tote_end_endtime > 0 THEN 1
                                                                    ELSE 0
                                                                END) AS COMPLETED_TOTES
                                                            FROM
                                                                printvis.totetimes
                                                                    LEFT JOIN
                                                                printvis.tote_end ON tote_end_whse = totetimes_whse
                                                                    AND totetimes_cart = tote_end_batch
                                                                    AND tote_end_tote = totetimes_bin
                                                                    LEFT JOIN
                                                                printvis.batch_start A ON batch_start_whse = totetimes_whse
                                                                    AND batch_start_batch = totetimes_cart
                                                                    LEFT JOIN
                                                                printvis.packbatchdelete ON CONCAT(totetimes_cart, batch_start_TSM) = idpackbatchdelete
                                                                    JOIN
                                                                printvis.pm_packtimes ON loosepm_function = totetimes_packfunction
                                                                    AND totetimes_whse = loosepm_whse
                                                            WHERE
                                                                totetimes_whse = $whsesel
                                                                    AND batch_start_time IS NOT NULL
                                                                    AND idpackbatchdelete IS NULL
                                                                    AND (A.batch_start_time) IN (SELECT 
                                                                        MAX((B.batch_start_time))
                                                                    FROM
                                                                        printvis.batch_start B
                                                                    WHERE
                                                                        A.batch_start_batch = B.batch_start_batch)
                                                                    AND (A.batch_start_time) IN (SELECT 
                                                                        MAX((C.batch_start_time))
                                                                    FROM
                                                                        printvis.batch_start C
                                                                    WHERE
                                                                        A.batch_start_TSM = C.batch_start_TSM)
                                                            GROUP BY totetimes_cart , batch_start_time , batch_start_TSM
                                                            HAVING COUNT(*) <> SUM(CASE
                                                                WHEN tote_end_endtime > 0 THEN 1
                                                                ELSE 0
                                                            END)
                                                                    $orderbysql;");
$openpack->execute();
$openpackarray = $openpack->fetchAll(pdo::FETCH_ASSOC);

$cartcount = count($openpackarray);

$sum_remainingtime = 0;
foreach ($openpackarray as $item) {
    $sum_remainingtime += $item['CART_TIME_REMAINING'];
}

$sum_standardpacktime = 0;
foreach ($openpackarray as $item) {
    $sum_standardpacktime += $item['CART_TIME'];
}

$sum_projectedtime = 0;
foreach ($openpackarray as $item) {
    $sum_projectedtime += $item['PROJ_COMPLETE_MIN'] - $mintosubtract;
}
?>

<!--Header stats-->
<div class="row" style="padding: 25px;">
    <!--win loss percentage stat-->
    <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12 " id="stat_percent">
        <div class="dashboard-stat dashboard-stat-v2 green">  
            <div class="visual">
                <i class="fa fa-check-square"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span data-counter="counterup" data-value="1349"><?php echo $cartcount ?></span>
                </div>
                <div class="desc"> Total Carts in Packing </div>
            </div>
        </div>
    </div>
    <!--Total profit stat-->
    <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12 " id="stat_profit" >
        <div class="dashboard-stat dashboard-stat-v2 green">  
            <div class="visual">
                <i class="fa fa-line-chart"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span ><?php echo _convertToHoursMins($sum_remainingtime) ?></span>
                </div>
                <div class="desc"> Total Hours Remaining to Pack </div>
            </div>
        </div>
    </div>
    <!--total $ bet-->
    <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12 " id="stat_bettotal" >
        <div class="dashboard-stat dashboard-stat-v2 purple-plum">  
            <div class="visual">
                <i class="fa fa-dollar"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span ><?php echo _convertToHoursMins($sum_standardpacktime) ?></span>
                </div>
                <div class="desc"> Total Standard Pack Hours</div>
            </div>
        </div>
    </div>
    <!--total $ pending-->
    <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12 " id="stat_betpending" >
        <div class="dashboard-stat dashboard-stat-v2 purple-plum">  
            <div class="visual">
                <i class="fa fa-dollar"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span ><?php echo _convertToHoursMins($sum_projectedtime) ?></span>
                </div>
                <div class="desc"> Total Projected Pack Hours</div>
            </div>
        </div>
    </div>
</div>




<!--start of div table-->
<div class="" id="divtable_packtimes" style="padding-bottom: 51px">
    <div  class='col-sm-12 col-md-12 col-lg-12 print-1wide'  style="float: none;">
        <div id="container_deletebtn">
            <button id="btn_delete" class="btn btn-danger">Delete Selected Batches</button>
        </div>

        <div class='widget-content widget-table'  style="position: relative;">
            <div class='divtable'>
                <div id="sticky-anchor"></div>
                <div style="padding-top: 51px;"></div>
                <div id="sticky" class='divtableheader' style="padding-top">
                    <div class='divtabletitle width8_33' style="cursor: default">Delete?</div>
                    <div class='divtabletitle width8_33 click_sort ' data-pull="printed" name="batch" data-sort="<?php echo $asc_desc ?>" data-toggle='tooltip' title='Click on batch for tote level detail' data-placement='top' data-container='body' style="cursor: default">Batch</div>
                    <div class='divtabletitle width8_33 click_sort ' data-pull="printed" name="start" data-sort="<?php echo $asc_desc ?>" style="cursor: default">Start Time</div>
                    <div class='divtabletitle width8_33 click_sort ' data-pull="printed" name="tsm" data-sort="<?php echo $asc_desc ?>" style="cursor: default">TSM</div>
                    <div class='divtabletitle width8_33 click_sort ' data-pull="printed" name="packstation" data-sort="<?php echo $asc_desc ?>" style="cursor: default">Pack Station</div>
                    <div class='divtabletitle width8_33 click_sort ' data-pull="printed" name="speed" data-sort="<?php echo $asc_desc ?>" style="cursor: default">Speed Pack?</div>
                    <div class='divtabletitle width8_33 click_sort ' data-pull="printed" name="standardtime" data-sort="<?php echo $asc_desc ?>" style="cursor: default">Standard Pack Time</div>
                    <div class='divtabletitle width8_33 click_sort ' data-pull="printed" name="projtime" data-sort="<?php echo $asc_desc ?>" style="cursor: default">Projected Completion Time</div>
                    <div class='divtabletitle width8_33 click_sort ' data-pull="printed" name="ahead_behind" data-sort="<?php echo $asc_desc ?>" style="cursor: default">Minutes Ahead/Behind</div>
                    <div class='divtabletitle width8_33 click_sort ' data-pull="printed" name="timeremain" data-sort="<?php echo $asc_desc ?>" style="cursor: default">Est. Time Remaining</div>
                    <div class='divtabletitle width8_33 click_sort ' data-pull="printed" name="perccomplete" data-sort="<?php echo $asc_desc ?>" style="cursor: default">Percent Complete</div>
                    <div class='divtabletitle width8_33 click_sort ' data-pull="printed" name="status" data-sort="<?php echo $asc_desc ?>" style="cursor: default">Status</div>
                </div>
                <?php
                foreach ($openpackarray as $key => $value) {
                    //adjust for time zone differences
                    $completemin_adj = intval($openpackarray[$key]['PROJ_COMPLETE_MIN']) - $mintosubtract;
                    $elapsedmin_adj = intval($openpackarray[$key]['MINUTES_ELAPSED']) - $mintosubtract;
                    if ($elapsedmin_adj + intval($openpackarray[$key]['CART_TIME_REMAINING']) <= intval($openpackarray[$key]['CART_TIME'])) {
                        $batchstatus = 'ON_TIME';
                    } else {
                        $batchstatus = 'LATE';
                    }
                    if ($batchstatus == 'LATE') {
                        $packclass = 'packlate';
                    } else {
                        $packclass = 'packontime';
                    }
                    
                    //have to add back $mintosubtract because previously subtracted out.
                       $minutecalc = intval(($openpackarray[$key]['CART_TIME'] - $openpackarray[$key]['PROJ_COMPLETE_MIN']) + $mintosubtract);
                    
                    ?>

                    <div id=""class='divtablerow itemdetailexpand  <?php echo $packclass ?>'>
                        <div class='divtabledata width8_33' style="vertical-align: text-top; cursor: pointer"> <input type="checkbox" class="chkbox_deletebatch" name="checkbox" id="<?php echo $openpackarray[$key]['BATCH'] . $openpackarray[$key]['TSM']; ?>"  /></div>
                        <div id="<?php echo $openpackarray[$key]['BATCH']; ?>"class='divtabledata width8_33 batchclick'  style="cursor: pointer; text-decoration: underline"><?php echo $openpackarray[$key]['BATCH']; ?></div>
                        <div class='divtabledata width8_33' ><?php echo $openpackarray[$key]['BATCH_START']; ?></div>
                        <div class='divtabledata width8_33' ><?php echo $openpackarray[$key]['TSM']; ?></div>
                        <div class='divtabledata width8_33' ><?php echo $openpackarray[$key]['PACK_STATION']; ?></div>
                        <div class='divtabledata width8_33' ><?php echo $openpackarray[$key]['SPEED']; ?></div>
                        <div class='divtabledata width8_33' ><?php echo _convertToHoursMins($openpackarray[$key]['CART_TIME']); ?></div>
                        <div class='divtabledata width8_33' ><?php echo _convertToHoursMins(intval($openpackarray[$key]['PROJ_COMPLETE_MIN']) - $mintosubtract); ?></div>
                        <div class='divtabledata width8_33' ><?php echo _convertToHoursMins($minutecalc); ?></div>
                        <div class='divtabledata width8_33' ><?php echo _convertToHoursMins($openpackarray[$key]['CART_TIME_REMAINING']); ?></div>
                        <div class='divtabledata width8_33' ><?php echo number_format($openpackarray[$key]['COMPLETED_TOTES'] / $openpackarray[$key]['TOTE_COUNT'] * 100, 2) . '%'; ?></div>
                        <div class='divtabledata width8_33' ><?php echo $batchstatus; ?></div>
                    </div>

                <?php } ?>
            </div>
        </div>

    </div>    
</div>    

