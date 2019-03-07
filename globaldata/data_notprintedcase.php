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
    if (isset($_POST['building'])) {
        $building = intval($_POST['building']);
    } else {
        echo '';
    }
} else {
    $whsearray = array(7);
}

include '../timezoneset.php';
$timenow = date('H:i');
$printcutoff = '17:07';

if ($timenow > $printcutoff) {
    include 'aftercutoff.php';
} else {  //not after print cutoff, proceed with time projection
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
            $orderbysql = " ORDER BY casebatches_cart $asc_desc";
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
        default:
            $orderbysql = " ORDER BY casebatches_cart $asc_desc";
            break;
    }



    $casesnotprinted = $conn1->prepare("SELECT 
                                                                                    casebatches_cart,
                                                                                    casebatches_equipment,
                                                                                    casebatches_lines,
                                                                                    casebatches_time_final 
                                                                                FROM printvis.notprintedcasebatches_time 
                                                                                WHERE casebatches_whse = $var_whse and casebatches_build = $building $orderbysql");
    $casesnotprinted->execute();
    $casesnotprintedarray = $casesnotprinted->fetchAll(pdo::FETCH_ASSOC);


    $notprintedbyequip = $conn1->prepare("SELECT 
                                                                                casebatches_equipment,
                                                                                sum(casebatches_time_final) as FINALTIME
                                                                            FROM
                                                                                printvis.notprintedcasebatches_time
                                                                            WHERE
                                                                                casebatches_whse = $var_whse
                                                                                    AND casebatches_build = $building
                                                                                    GROUP BY casebatches_equipment");
    $notprintedbyequip->execute();
    $notprintedbyequiparray = $notprintedbyequip->fetchAll(pdo::FETCH_ASSOC);
    ?>


    <!--time by equipment header-->
    <div class="row">
    <?php foreach ($notprintedbyequiparray as $key => $value) { ?>

            <div class="col-lg-4">
                <div class="widget-thumb widget-bg-color-white text-uppercase">
                    <div class="widget-thumb-wrap">
                        <i class="widget-thumb-icon bg-blue fa  fa-clock-o  fa-3x "></i>
                        <div class="widget-thumb-body">
                            <span class="widget-thumb-subtitle">Time Required for <?php echo $notprintedbyequiparray[$key]['casebatches_equipment'] ?> </span>
                            <span class="widget-thumb-body-stat" ><?php echo _convertToHoursMins($notprintedbyequiparray[$key]['FINALTIME']) ?> </span>
                        </div>
                    </div>
                </div>
            </div>

    <?php } ?>
    </div>  




    <!--activate modal to change number of equipment operators-->
    <div id="btn_equipmodal" class="btn btn-inverse">Modify Equipment Usage</div>

    <!--start of div table-->
    <div class="" id="divtable_notprinted" style="padding-bottom: 51px">
        <div  class='col-sm-12 col-md-12 col-lg-12 print-1wide'  style="float: none;">
            <div class='widget-content widget-table'  style="position: relative;">
                <div class='divtable'>
                    <div id="sticky-anchor"></div>
                    <div style="padding-top: 51px;"></div>
                    <div id="sticky" class='divtableheader' style="padding-top">
                        <div class='divtabletitle width25 click_sort' data-pull="notprinted"  name="batch" data-sort="<?php echo $asc_desc ?>" >Batch</div>
                        <div class='divtabletitle width25 click_sort' data-pull="notprinted"  name="equip" data-sort="<?php echo $asc_desc ?>" >Equipment Needed</div>
                        <div class='divtabletitle width25 click_sort' data-pull="notprinted"  name="lines" data-sort="<?php echo $asc_desc ?>">Total Lines</div>
                        <div class='divtabletitle width25 click_sort' data-pull="notprinted"  name="hours_projected" data-sort="<?php echo $asc_desc ?>" >Projected Completion Time</div>
                    </div>
    <?php
    foreach ($casesnotprintedarray as $key => $value) {
        ?>
                        <div id=""class='divtablerow itemdetailexpand'>
                            <div id="<?php echo $casesnotprintedarray[$key]['casebatches_cart']; ?>"class='divtabledata width25 batchclick_notprinted'  style="cursor: pointer; text-decoration: underline"><?php echo $casesnotprintedarray[$key]['casebatches_cart']; ?></div>
                            <div class='divtabledata width25' ><?php echo $casesnotprintedarray[$key]['casebatches_equipment']; ?></div>
                            <div class='divtabledata width25' ><?php echo $casesnotprintedarray[$key]['casebatches_lines']; ?></div>
                            <div class='divtabledata width25' ><?php echo _convertToHoursMins($casesnotprintedarray[$key]['casebatches_time_final']); ?></div>
                        </div>
    <?php } ?>
                </div>
            </div>
        </div>    
    </div>    
    <?php
}