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


} else {
    $whsearray = array(7);
}
$building = $_POST['building'];

if (isset($_POST['sort_class'])) {
    $asc_desc = $_POST['sort_class'];
} else {
    $asc_desc = ' asc';
}

$orderbyvalue = $_POST['orderby'];
switch ($orderbyvalue) {
    case 'batch':
        $orderbysql = " ORDER BY casebatches_cart $asc_desc";
        break;
    case 'equip':
        $orderbysql = " ORDER BY casebatches_equipment $asc_desc";
        break;
    case 'printdate':
        $orderbysql = " ORDER BY casebatches_printdate $asc_desc";
        break;
    case 'lines':
        $orderbysql = " ORDER BY casebatches_lines $asc_desc";
        break;
    case 'firstloc':
        $orderbysql = " ORDER BY casebatches_firstloc $asc_desc";
        break;
    case 'lastloc':
        $orderbysql = " ORDER BY casebatches_lastloc $asc_desc";
        break;
    case 'comptime':
        $orderbysql = " ORDER BY casebatches_time_final $asc_desc";
        break;

    case 'boxrel':
        $orderbysql = " ORDER BY boxrel_relcount $asc_desc";
        break;

    case 'percrel':
        $orderbysql = " ORDER BY boxrel_relcount / boxrel_boxcount $asc_desc";
        break;
    default:
        $orderbysql = " ORDER BY casebatches_cart $asc_desc";
        break;
}

$casesprinted = $conn1->prepare("SELECT 
                                                            casebatches_cart,
                                                            casebatches_equipment,
                                                            casebatches_printdate,
                                                            casebatches_lines,
                                                            casebatches_firstloc,
                                                            casebatches_lastloc,
                                                            casebatches_time_final,
                                                            boxrel_relcount,
                                                            boxrel_relcount / boxrel_boxcount AS PERC_RELEASED
                                                        FROM
                                                            printvis.casebatches_time
                                                                LEFT JOIN
                                                            printvis.casebatchstarttime A ON starttime_whse = casebatches_whse
                                                                AND starttime_build = casebatches_build
                                                                AND starttime_batch = casebatches_cart
                                                                        LEFT JOIN
                                                             printvis.case_boxesreleased ON boxrel_batch = casebatches_cart
                                                             LEFT JOIN printvis.casebatchdelete on casebatches_cart = casedelete_batch
                                                        WHERE
                                                            starttime_tsm IS NULL
                                                            and casedelete_batch IS NULL
                                                                AND casebatches_whse = $var_whse
                                                                AND casebatches_build = $building
                                                          $orderbysql");
$casesprinted->execute();
$casesprintedarray = $casesprinted->fetchAll(pdo::FETCH_ASSOC);

$printedtimebyequip = $conn1->prepare("SELECT 
                                                                casebatches_equipment,
                                                                SUM(casebatches_time_final * CASE
                                                                                    WHEN (1 - (boxrel_relcount / boxrel_boxcount)) IS NULL THEN 1
                                                                                    ELSE (1 - (boxrel_relcount / boxrel_boxcount))
                                                                                END) as casebatches_time_final,
                                                                                count(*) as EQUIPCOUNT
                                                            FROM
                                                                printvis.casebatches_time
                                                                    LEFT JOIN
                                                                printvis.casebatchstarttime A ON starttime_whse = casebatches_whse
                                                                    AND starttime_build = casebatches_build
                                                                    AND starttime_batch = casebatches_cart
                                                                            LEFT JOIN
                                                                    printvis.case_boxesreleased ON boxrel_batch = casebatches_cart
                                                                     LEFT JOIN printvis.casebatchdelete on casebatches_cart = casedelete_batch
                                                            WHERE
                                                                starttime_tsm IS NULL
                                                                and casedelete_batch IS NULL
                                                                    AND casebatches_whse = $var_whse
                                                                    AND casebatches_build = $building
                                                                    GROUP BY casebatches_equipment");
$printedtimebyequip->execute();
$printedtimebyequiparray = $printedtimebyequip->fetchAll(pdo::FETCH_ASSOC);
?>

<!--time by equipment header-->
<div class="row">
    <?php foreach ($printedtimebyequiparray as $key => $value) { ?>

        <div class="col-lg-3">
            <div class="widget-thumb widget-bg-color-white text-uppercase">
                <div class="widget-thumb-wrap">
                    <?php
                    $equipment = $printedtimebyequiparray[$key]['casebatches_equipment'];
                    if ($equipment === 'REACH') {
                        ?>
                        <i class = "widget-thumb-icon bg-blue fa  fa-clock-o  fa-3x "></i>
                        <div class = "widget-thumb-body">
                            <span class = "widget-thumb-subtitle">Count of Reach Batches</span>
                            <span class="widget-thumb-body-stat" ><?php echo intval($printedtimebyequiparray[$key]['EQUIPCOUNT']) ?> </span>
                        </div>
                    <?php } else { ?>

                        <i class = "widget-thumb-icon bg-blue fa  fa-clock-o  fa-3x "></i>
                        <div class = "widget-thumb-body">
                            <span class = "widget-thumb-subtitle">Time Required for<strong> <?php echo $printedtimebyequiparray[$key]['casebatches_equipment'] ?></strong> </span>
                            <span class="widget-thumb-body-stat" ><?php echo _convertToHoursMins($printedtimebyequiparray[$key]['casebatches_time_final']) ?> </span>
                        </div>

                    <?php } ?>


                </div>
            </div>
        </div>

    <?php } ?>
</div>  










<div id="container_deletebtn">
    <button id="btn_delete_printed" class="btn btn-danger">Delete Selected Batches</button>
</div>





<!--start of div table-->
<div class="" id="divtable_notprinted" style="padding-bottom: 51px">
    <div  class='col-sm-12 col-md-12 col-lg-12 print-1wide'  style="float: none;">
        <div class='widget-content widget-table'  style="position: relative;">
            <div class='divtable'>
                <div id="sticky-anchor"></div>
                <div style="padding-top: 51px;"></div>
                <div id="sticky" class='divtableheader' style="padding-top">
                    <div class='divtabletitle width10' style="cursor: default">Delete?</div>
                    <div class='divtabletitle width10 click_sort ' data-pull="printed" name="batch" data-sort="<?php echo $asc_desc ?>" data-toggle='tooltip' title='Click on batch pick detail' data-placement='top' data-container='body' >Batch</div>
                    <div class='divtabletitle width10 click_sort ' data-pull="printed" name="equip" data-sort="<?php echo $asc_desc ?>" >Equipment Needed</div>
                    <div class='divtabletitle width10 click_sort ' data-pull="printed" name="printdate" data-sort="<?php echo $asc_desc ?>" >Print Date</div>
                    <div class='divtabletitle width10 click_sort ' data-pull="printed" name="lines" data-sort="<?php echo $asc_desc ?>" >Total Lines</div>
                    <div class='divtabletitle width10 click_sort ' data-pull="printed" name="firstloc" data-sort="<?php echo $asc_desc ?>" >First Location</div>
                    <div class='divtabletitle width10 click_sort ' data-pull="printed" name="lastloc" data-sort="<?php echo $asc_desc ?>" >Last Location</div>
                    <div class='divtabletitle width10 click_sort ' data-pull="printed" name="comptime" data-sort="<?php echo $asc_desc ?>" >Projected Completion Time</div>
                    <div class='divtabletitle width10 click_sort ' data-pull="printed" name="boxrel" data-sort="<?php echo $asc_desc ?>" >Boxes Released</div>
                    <div class='divtabletitle width10 click_sort ' data-pull="printed" name="percrel" data-sort="<?php echo $asc_desc ?>" >Percent Released</div>
                </div>
                <?php
                foreach ($casesprintedarray as $key => $value) {
                    $timeclass = '';

                    if ($casesprintedarray[$key]['boxrel_relcount'] > 0) {
                        $timeclass = 'redbackground';
                    }
                    ?>
                    <div id=""class='divtablerow itemdetailexpand'>
                        <div class='divtabledata width10' style="vertical-align: text-top; cursor: pointer"> <input type="checkbox" class="chkbox_deletebatch" name="checkbox" id="<?php echo $casesprintedarray[$key]['casebatches_cart']; ?>"  /></div>
                        <div id="<?php echo $casesprintedarray[$key]['casebatches_cart']; ?>"class='divtabledata width10 batchclick_printed'  style="cursor: pointer; text-decoration: underline"><?php echo $casesprintedarray[$key]['casebatches_cart']; ?></div>
                        <div class='divtabledata width10' ><?php echo $casesprintedarray[$key]['casebatches_equipment']; ?></div>
                        <div class='divtabledata width10' ><?php echo $casesprintedarray[$key]['casebatches_printdate']; ?></div>
                        <div class='divtabledata width10' ><?php echo $casesprintedarray[$key]['casebatches_lines']; ?></div>
                        <div class='divtabledata width10' ><?php echo $casesprintedarray[$key]['casebatches_firstloc']; ?></div>
                        <div class='divtabledata width10' ><?php echo $casesprintedarray[$key]['casebatches_lastloc']; ?></div>
                        <div class='divtabledata width10' ><?php echo _convertToHoursMins($casesprintedarray[$key]['casebatches_time_final'] * (1 - $casesprintedarray[$key]['PERC_RELEASED'])); ?></div>
                        <div class='divtabledata width10 <?php echo $timeclass ?>' ><?php echo ($casesprintedarray[$key]['boxrel_relcount']); ?></div>
                        <div class='divtabledata width10 <?php echo $timeclass ?>' ><?php echo number_format(($casesprintedarray[$key]['PERC_RELEASED']) * 100, 2) . '%'; ?></div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>    
</div>    
