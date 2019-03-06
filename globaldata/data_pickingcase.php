<?php
include '../../CustomerAudit/connection/connection_details.php';
include '../sessioninclude.php';
include '../functions/functions_totetimes.php';

if (isset($_SESSION['MYUSER'])) {
    $var_userid = $_SESSION['MYUSER'];
    $whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
    $whssql->execute();
    $whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

    $var_whse = $whssqlarray[0]['prodvisdb_users_PRIMDC'];
    $whsesel = $var_whse;
    include '../timezoneset.php';
} else {
    $whsearray = array(7);
}

if(isset($_POST['building'])){
    $building = intval($_POST['building']);
} else{
    echo '';
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
    case 'tsm':
        $orderbysql = " ORDER BY starttime_tsm $asc_desc";
        break;
    case 'starttime':
        $orderbysql = " ORDER BY starttime_starttime $asc_desc";
        break;
    case 'batch':
        $orderbysql = " ORDER BY starttime_batch $asc_desc";
        break;
    case 'equip':
        $orderbysql = " ORDER BY casebatches_equipment $asc_desc";
        break;
    case 'lines':
        $orderbysql = " ORDER BY casebatches_lines $asc_desc";
        break;
    case 'hours_projected':
        $orderbysql = " ORDER BY casebatches_time_final $asc_desc";
        break;
    case 'boxesrel':
        $orderbysql = " ORDER BY boxrel_relcount $asc_desc";
        break;

    case 'percrel':
        $orderbysql = " ORDER BY boxrel_relcount/boxrel_boxcount  $asc_desc";
        break;
    case 'hours_elapsed':
        $orderbysql = " ORDER BY TIMESTAMPDIFF(MINUTE,
                                                            starttime_starttime,
                                                            NOW()) - (CASE
                                                            WHEN starttime_whse = 7 THEN 60
                                                            WHEN starttime_whse = 3 THEN 180
                                                            ELSE 0
                                                        END) $asc_desc";
        break;
    case 'hours_remain':
        $orderbysql = " ORDER BY casebatches_time_final - (TIMESTAMPDIFF(MINUTE,
                                                            starttime_starttime,
                                                            NOW()) - (CASE
                                                            WHEN starttime_whse = 7 THEN 60
                                                            WHEN starttime_whse = 3 THEN 180
                                                            ELSE 0
                                                        END)) $asc_desc";
        break;
    default:
        $orderbysql = " ORDER BY starttime_batch $asc_desc";
        break;
}


$casespicking = $conn1->prepare("SELECT 
                                                        starttime_tsm,
                                                        starttime_starttime,
                                                        starttime_batch,
                                                        casebatches_equipment,
                                                        casebatches_lines,
                                                        casebatches_time_final,
                                                        case when casebatches_equipment = 'REACH' then 0 else (
                                                        TIMESTAMPDIFF(MINUTE,
                                                            starttime_starttime,
                                                            NOW()) - (CASE
                                                            WHEN starttime_whse = 7 THEN 60
                                                            WHEN starttime_whse = 3 THEN 180
                                                            ELSE 0
                                                        END)) end AS MINUTES_ELAPSED,
                                                        case when casebatches_equipment = 'REACH' then 0 else ( casebatches_time_final - (TIMESTAMPDIFF(MINUTE,
                                                            starttime_starttime,
                                                            NOW()) - (CASE
                                                            WHEN starttime_whse = 7 THEN 60
                                                            WHEN starttime_whse = 3 THEN 180
                                                            ELSE 0
                                                        END))) end AS EST_MIN_REMAINING,
                                                        boxrel_relcount,
                                                        boxrel_relcount/boxrel_boxcount as PERC_RELEASED,
                                                        UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_BREAK1)) as SHIFT_BREAK1,
                                                        UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_BREAK2)) as SHIFT_BREAK2,
                                                        UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_LUNCH)) as SHIFT_LUNCH
                                                    FROM
                                                        printvis.casebatches_time
                                                            JOIN
                                                        printvis.casebatchstarttime A ON starttime_whse = casebatches_whse
                                                            AND starttime_build = casebatches_build
                                                            AND starttime_batch = casebatches_cart
                                                            LEFT JOIN printvis.case_boxesreleased on boxrel_batch = starttime_batch
                                                            LEFT JOIN printvis.casebatchdelete on starttime_batch = casedelete_batch
                                                            LEFT JOIN printvis.tsmshift ON SHIFT_TSMNUM = starttime_tsm
                                                    WHERE
                                                        (A.starttime_starttime) IN (SELECT 
                                                                MAX((B.starttime_starttime))
                                                            FROM
                                                                printvis.casebatchstarttime B
                                                            WHERE
                                                                A.starttime_batch = B.starttime_batch)
                                                            AND (A.starttime_starttime) IN (SELECT 
                                                                MAX((C.starttime_starttime))
                                                            FROM
                                                                printvis.casebatchstarttime C
                                                            WHERE
                                                                A.starttime_tsm = C.starttime_tsm)
                                                         AND casebatches_whse = $var_whse and casebatches_build = $building
                                                                AND(    casebatches_time_final - (TIMESTAMPDIFF(MINUTE,
                                                                starttime_starttime,
                                                                NOW()) - (CASE
                                                                WHEN starttime_whse = 7 THEN 60
                                                                WHEN starttime_whse = 3 THEN 180
                                                                ELSE 0
                                                            END)) > -35 and boxrel_relcount / boxrel_boxcount < .9) 
                                                            and casedelete_batch IS NULL
                                                            $orderbysql");
$casespicking->execute();
$casespickingarray = $casespicking->fetchAll(pdo::FETCH_ASSOC);



$timebyequip = $conn1->prepare("SELECT 
                                                                        casebatches_equipment,
                                                                        SUM(CASE
                                                                            WHEN
                                                                                casebatches_time_final - (TIMESTAMPDIFF(MINUTE,
                                                                                    starttime_starttime,
                                                                                    NOW()) - (CASE
                                                                                    WHEN starttime_whse = 7 THEN 60
                                                                                    WHEN starttime_whse = 3 THEN 180
                                                                                    ELSE 0
                                                                                END)) < 0
                                                                            THEN
                                                                                0
                                                                            ELSE casebatches_time_final - (TIMESTAMPDIFF(MINUTE,
                                                                                starttime_starttime,
                                                                                NOW()) - (CASE
                                                                                WHEN starttime_whse = 7 THEN 60
                                                                                WHEN starttime_whse = 3 THEN 180
                                                                                ELSE 0
                                                                            END))
                                                                        END) AS EST_MIN_REMAINING,
                                                                        count(*) as EQUIPCOUNT
                                                                    FROM
                                                                        printvis.casebatches_time
                                                                            JOIN
                                                                        printvis.casebatchstarttime A ON starttime_whse = casebatches_whse
                                                                            AND starttime_build = casebatches_build
                                                                            AND starttime_batch = casebatches_cart
                                                                            LEFT JOIN printvis.casebatchdelete on starttime_batch = casedelete_batch
                                                                            LEFT JOIN printvis.case_boxesreleased on boxrel_batch = starttime_batch
                                                                    WHERE  casedelete_batch IS NULL 
                                                                                    AND (casebatches_time_final - (TIMESTAMPDIFF(MINUTE,
                                                                                    starttime_starttime,
                                                                                    NOW()) - (CASE
                                                                                    WHEN starttime_whse = 7 THEN 60
                                                                                    WHEN starttime_whse = 3 THEN 180
                                                                                    ELSE 0
                                                                                END)) > - 35
                                                                                    AND boxrel_relcount / boxrel_boxcount < .9)                                                                    
                                                                            and
                                                                        (A.starttime_starttime) IN (SELECT 
                                                                                MAX((B.starttime_starttime))
                                                                            FROM
                                                                                printvis.casebatchstarttime B
                                                                            WHERE
                                                                                A.starttime_batch = B.starttime_batch)
                                                                            AND (A.starttime_starttime) IN (SELECT 
                                                                                MAX((C.starttime_starttime))
                                                                            FROM
                                                                                printvis.casebatchstarttime C
                                                                            WHERE
                                                                                A.starttime_tsm = C.starttime_tsm)
                                                                            AND casebatches_whse = $var_whse
                                                                            AND casebatches_build = $building
                                                                    GROUP BY casebatches_equipment");
$timebyequip->execute();
$timebyequiparray = $timebyequip->fetchAll(pdo::FETCH_ASSOC);
?>

<!--time by equipment header-->
<div class="row">
    <?php foreach ($timebyequiparray as $key => $value) { ?>

        <div class="col-lg-3">
            <div class="widget-thumb widget-bg-color-white text-uppercase">
                <div class="widget-thumb-wrap">
                    <?php
                    $equipment = $timebyequiparray[$key]['casebatches_equipment'];
                    if ($equipment === 'REACH') {
                        ?>
                        <i class = "widget-thumb-icon bg-blue fa  fa-clock-o  fa-3x "></i>
                        <div class = "widget-thumb-body">
                            <span class = "widget-thumb-subtitle">Count of Reach Batches</span>
                            <span class="widget-thumb-body-stat" ><?php echo intval($timebyequiparray[$key]['EQUIPCOUNT']) ?> </span>
                        </div>
                    <?php } else { ?>

                        <i class = "widget-thumb-icon bg-blue fa  fa-clock-o  fa-3x "></i>
                        <div class = "widget-thumb-body">
                            <span class = "widget-thumb-subtitle">Time Required for<strong> <?php echo $timebyequiparray[$key]['casebatches_equipment'] ?></strong> </span>
                            <span class="widget-thumb-body-stat" ><?php echo _convertToHoursMins($timebyequiparray[$key]['EST_MIN_REMAINING']) ?> </span>
                        </div>

                    <?php } ?>


                </div>
            </div>
        </div>

    <?php } ?>
</div>  

<div id="container_deletebtn">
    <button id="btn_delete_picking" class="btn btn-danger">Delete Selected Batches</button>
</div>


<!--start of div table-->
<div class="" id="divtable_notprinted" style="padding-bottom: 51px">
    <div  class='col-sm-12 col-md-12 col-lg-12 print-1wide'  style="float: none;">
        <div class='widget-content widget-table'  style="position: relative;">
            <div class='divtable'>
                <div id="sticky-anchor"></div>
                <div style="padding-top: 51px;"></div>
                <div id="sticky" class='divtableheader' style="padding-top">
                    <div class='divtabletitle width8_33' style="cursor: default">Delete?</div>
                    <div class='divtabletitle width8_33 click_sort' data-pull="picking"  name="tsm" data-sort="<?php echo $asc_desc ?>">TSM#</div>
                    <div class='divtabletitle width8_33 click_sort' data-pull="picking" name="starttime" style="cursor: default" data-sort="<?php echo $asc_desc ?>">Start Time</div>
                    <div class='divtabletitle width8_33 click_sort' data-pull="picking" name="batch" data-sort="<?php echo $asc_desc ?>">Batch</div>
                    <div class='divtabletitle width8_33 click_sort' data-pull="picking" name="equip" data-sort="<?php echo $asc_desc ?>">Equipment Needed</div>
                    <div class='divtabletitle width8_33 click_sort' data-pull="picking" name="lines" data-sort="<?php echo $asc_desc ?>">Total Lines</div>
                    <div class='divtabletitle width8_33 click_sort' data-pull="picking" name="lunchorbreak" data-sort="<?php echo $asc_desc ?>">Lunch or Break?</div>
                    <div class='divtabletitle width8_33 click_sort' data-pull="picking" name="hours_projected" data-sort="<?php echo $asc_desc ?>">Projected Hours/Mins</div>
                    <div class='divtabletitle width8_33 click_sort' data-pull="picking" name="hours_elapsed" data-sort="<?php echo $asc_desc ?>">Hours/Mins Elapsed</div>
                    <div class='divtabletitle width8_33 click_sort' data-pull="picking" name="hours_remain" data-sort="<?php echo $asc_desc ?>">Estimated Hours/Mins Remaining</div>
                    <div class='divtabletitle width8_33 click_sort' data-pull="picking" name="boxesrel" data-sort="<?php echo $asc_desc ?>">Boxes Released</div>
                    <div class='divtabletitle width8_33  click_sort' data-pull="picking" name="percrel" data-sort="<?php echo $asc_desc ?>">Percent Released</div>
                </div>
                <?php
                foreach ($casespickingarray as $key => $value) {
                    $timeclass = '';
                    if ($casespickingarray[$key]['EST_MIN_REMAINING'] < 0) {
                        $timeclass = 'redbackground';
                    }

                    //is the TSM on break or lunch?
                    $curtime = date('U') - ($mintosubtract * 60);
                    $lunchorbreak = '-';

                    //Break1?
                    $start_break1 = intval($casespickingarray[$key]['SHIFT_BREAK1']);
                    $end_break1 = $start_break1 + 900;
                    if ($curtime >= $start_break1 && $curtime <= $end_break1) {
                        $lunchorbreak = 'On Break1';
                    }

                    //Break2?
                    $start_break2 = intval($casespickingarray[$key]['SHIFT_BREAK2']);
                    $end_break2 = $start_break2 + 900;
                    if ($curtime >= $start_break2 && $curtime <= $end_break2) {
                        $lunchorbreak = 'On Break2';
                    }

                    //Break1?
                    $start_lunch = intval($casespickingarray[$key]['SHIFT_BREAK1']);
                    $end_lunch = $start_lunch + 900;
                    if ($curtime >= $start_lunch && $curtime <= $end_lunch) {
                        $lunchorbreak = 'On Lunch';
                    }

                    //if projected time to complete eclipses a break or lunch, account for lost time
                    $startutc = date('U', strtotime($casespickingarray[$key]['starttime_starttime']));
                    $endutc = ceil($startutc + ($casespickingarray[$key]['casebatches_time_final'] * 60));
                    ?>
                    <div id=""class='divtablerow itemdetailexpand'>
                        <div class='divtabledata width8_33' style="vertical-align: text-top; cursor: pointer"> <input type="checkbox" class="chkbox_deletebatch" name="checkbox" id="<?php echo $casespickingarray[$key]['starttime_batch']; ?>"  /></div>
                        <div class='divtabledata width8_33' ><?php echo $casespickingarray[$key]['starttime_tsm']; ?></div>
                        <div class='divtabledata width8_33' ><?php echo $casespickingarray[$key]['starttime_starttime']; ?></div>
                        <div id="<?php echo $casespickingarray[$key]['starttime_batch']; ?>"class='divtabledata width8_33 batchclick_picking'  style="cursor: pointer; text-decoration: underline"><?php echo $casespickingarray[$key]['starttime_batch']; ?></div>
                        <div class='divtabledata width8_33' ><?php echo $casespickingarray[$key]['casebatches_equipment']; ?></div>
                        <div class='divtabledata width8_33' ><?php echo $casespickingarray[$key]['casebatches_lines']; ?></div>
                        <div class='divtabledata width8_33' ><?php echo $lunchorbreak; ?></div>
                        <div class='divtabledata width8_33' ><?php echo _convertToHoursMins($casespickingarray[$key]['casebatches_time_final']); ?></div>
                        <div class='divtabledata width8_33' ><?php echo _convertToHoursMins($casespickingarray[$key]['MINUTES_ELAPSED']); ?></div>
                        <div class='divtabledata width8_33 <?php echo $timeclass ?>' ><?php echo _convertToHoursMins($casespickingarray[$key]['EST_MIN_REMAINING']); ?></div>
                        <div class='divtabledata width8_33 <?php echo $timeclass ?>' ><?php echo ($casespickingarray[$key]['boxrel_relcount']); ?></div>
                        <div class='divtabledata width8_33 <?php echo $timeclass ?>' ><?php echo number_format(($casespickingarray[$key]['PERC_RELEASED']) * 100, 2) . '%'; ?></div>
                    </div>
<?php } ?>
            </div>
        </div>
    </div>    
</div>    
