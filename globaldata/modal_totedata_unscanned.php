<?php
include '../sessioninclude.php';
include '../../CustomerAudit/connection/connection_details.php';
include '../functions/functions_totetimes.php';
$var_userid = $_SESSION['MYUSER'];
$whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);
$whsesel = $whssqlarray[0]['prodvisdb_users_PRIMDC'];

$batch = intval($_POST['batch']);
$totedate = ($_POST['totedate']);



if (isset($_POST['sort_class'])) {
    $asc_desc = $_POST['sort_class'];
} else {
    $asc_desc = ' asc';
}



if (isset($_POST['orderby'])) {
    $orderbyvalue = $_POST['orderby'];
} else {
    $orderbyvalue = 'bin';
}

switch ($orderbyvalue) {
    case 'lp':
        $orderbysql = " ORDER BY totelp $asc_desc ";
        break;
    case 'tsm':
        $orderbysql = " ORDER BY allscan_tsm $asc_desc ";
        break;
    case 'batch':
        $orderbysql = " ORDER BY totetimes_cart $asc_desc ";
        break;
    case 'bin':
        $orderbysql = " ORDER BY totetimes_bin $asc_desc ";
        break;
    case 'boxsize':
        $orderbysql = " ORDER BY totetimes_boxsize $asc_desc ";
        break;
    case 'lines':
        $orderbysql = " ORDER BY totetimes_linecount $asc_desc ";
        break;
    case 'units':
        $orderbysql = " ORDER BY totetimes_unitcount $asc_desc ";
        break;
    case 'time':
        $orderbysql = " ORDER BY totetimes_totalPFD $asc_desc ";
        break;
    case 'comptime':
        $orderbysql = " ORDER BY allscan_endtime $asc_desc ";
        break;

    default:
        $orderbysql = " ORDER BY totetimes_bin $asc_desc ";
        break;
}



$totedata = $conn1->prepare("SELECT 
                                                                totelp,
                                                                 allscan_tsm,
                                                                totetimes_cart,
                                                                totetimes_bin,
                                                                totetimes_boxsize,
                                                                totetimes_linecount,
                                                                totetimes_unitcount,
                                                                totetimes_totalPFD,
                                                                allscan_endtime
                                                            FROM
                                                                printvis.alltote_history
                                                                    LEFT JOIN
                                                                printvis.scannedtote_history ON allscan_lp = totelp
                                                            WHERE
                                                                totetimes_whse = $whsesel
                                                                    AND totetimes_cart = $batch
                                                                        AND (date(allscan_endtime) = '$totedate' or date(allscan_endtime) IS NULL)
                                                            $orderbysql");
$totedata->execute();
$totedata_modal = $totedata->fetchAll(pdo::FETCH_ASSOC);
?>


<!--start of div table-->
<div class="" id="divtablecontainer">
    <div  class='col-sm-12 col-md-12 col-lg-12 print-1wide'  style="float: none;">

        <div class='widget-content widget-table'  style="position: relative;">
            <div class='divtable'>
                <div class='divtableheader'>
                    <div class='divtabletitle width10 click_sort_modal  ' data-pull="modal_unscanned" name="lp" data-sort="<?php echo $asc_desc ?>">LP</div>
                    <div class='divtabletitle width10 click_sort_modal  ' data-pull="modal_unscanned" name="tsm" data-sort="<?php echo $asc_desc ?>">TSM</div>
                    <div class='divtabletitle width10 click_sort_modal  ' data-pull="modal_unscanned" name="batch" data-sort="<?php echo $asc_desc ?>">Batch</div>
                    <div class='divtabletitle width10 click_sort_modal  ' data-pull="modal_unscanned" name="bin" data-sort="<?php echo $asc_desc ?>">Bin</div>
                    <div class='divtabletitle width10 click_sort_modal  ' data-pull="modal_unscanned" name="boxsize" data-sort="<?php echo $asc_desc ?>">Box Size</div>
                    <div class='divtabletitle width10 click_sort_modal  ' data-pull="modal_unscanned" name="lines" data-sort="<?php echo $asc_desc ?>">Lines</div>
                    <div class='divtabletitle width10 click_sort_modal  ' data-pull="modal_unscanned" name="units" data-sort="<?php echo $asc_desc ?>">Units</div>
                    <div class='divtabletitle width10 click_sort_modal  ' data-pull="modal_unscanned" name="time" data-sort="<?php echo $asc_desc ?>">Time Required</div>
                    <div class='divtabletitle width20 click_sort_modal  ' data-pull="modal_unscanned" name="comptime" data-sort="<?php echo $asc_desc ?>">Completion Time</div>

                </div>
                <?php foreach ($totedata_modal as $key => $value) { ?>
                    <div class='divtablerow itemdetailexpand'>
                        <div class='divtabledata width10   ' > <?php echo $totedata_modal[$key]['totelp']; ?> </div>
                        <div class='divtabledata width10   ' > <?php echo $totedata_modal[$key]['allscan_tsm']; ?> </div>
                        <div class='divtabledata width10   ' >   <?php echo $totedata_modal[$key]['totetimes_cart']; ?> </div>
                        <div class='divtabledata width10   ' > <?php echo $totedata_modal[$key]['totetimes_bin']; ?> </div>
                        <div class='divtabledata width10   ' > <?php echo $totedata_modal[$key]['totetimes_boxsize']; ?> </div>
                        <div class='divtabledata width10   ' > <?php echo $totedata_modal[$key]['totetimes_linecount']; ?> </div>
                        <div class='divtabledata width10   ' > <?php echo $totedata_modal[$key]['totetimes_unitcount']; ?> </div>
                        <div class='divtabledata width10   ' > <?php echo _convertToHoursMins($totedata_modal[$key]['totetimes_totalPFD']); ?> </div>
                        <div class='divtabledata widtwidth20h17_5   ' > <?php echo $totedata_modal[$key]['allscan_endtime']; ?> </div>
                    </div>

                <?php } ?>
            </div>
        </div>

    </div>    
</div>    

